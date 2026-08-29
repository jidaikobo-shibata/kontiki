# Kontiki DB・model 監査

監査日: 2026-08-29

## 目的と範囲

v1 のDB再設計やリファクタリングに先立ち、現在のスキーマ、マイグレーション、
model、主要な読み書き経路を読み取り専用で確認した。

この文書は現状の事実とリスクの棚卸しであり、新しい正規スキーマの決定ではない。
本番データ、認証情報、記事本文などは記録していない。

## 確認したもの

- `db/migrations/` の9マイグレーション
- `src/Models/` と各 trait
- `ValidationService` と記事保存処理
- installer の初期ユーザー更新処理
- リポジトリ内に残るローカルSQLite DBのスキーマ、件数、整合性
- `kontiki-dev` のv1 clean installとv0.9更新済み環境のスキーマ

SQLiteの確認は `mode=ro` または接続直後の `PRAGMA query_only = ON` で読み取り専用にし、
スキーマ、件数、`integrity_check`、外部キー設定だけを取得した。

## 現在の論理構造

| テーブル | 主な責務 | 主な識別・制約 |
| --- | --- | --- |
| `users` | 管理ユーザー | `id`、`username` unique |
| `rate_limit` | ログイン試行制限 | `ip_address` primary key |
| `posts` | 複数のコンテンツ種別 | `id`、`post_type + slug` unique |
| `files` | アップロードファイル情報 | `id`、`path` unique |
| `terms` | taxonomyごとの語 | `id`、`taxonomy + slug` unique |
| `term_relationships` | 記事と語の関連 | `post_id + term_id` primary key |
| `meta_data` | model固有の追加値 | `target + target_id + meta_key` primary key |
| `phinxlog` | 適用済みマイグレーション | `version` primary key |

`posts.post_type` によって複数のコンテンツ種別を共有テーブルへ保存する構造は、
modelごとのアドホックなテーブルを増やさないという今後の方針と整合する。

## 環境間で確認できた差

### clean installとv0.9更新済みテスト環境

- 9件のマイグレーションが適用済み
- `users.role` が存在する
- `posts.display_updated_at` が存在する
- `PRAGMA integrity_check` は `ok`
- `PRAGMA foreign_keys` は `0`

### リポジトリに残るローカルDB

- 8件のマイグレーションが適用済み
- `users.role` が存在しない
- `posts.display_updated_at` が存在しない
- `PRAGMA integrity_check` は `ok`
- `PRAGMA foreign_keys` は `0`

このローカルDBは `.gitignore` 対象で、配布パッケージには含まれない。ただし、
「同じマイグレーションversionが記録されていても、作成時期によって実スキーマが
異なり得る」ことを示す資料になる。

### 既存の非公開確認環境

実運用に近い確認環境を集計したところ、次の状態だった。

- 9件のマイグレーションが適用済みで、`integrity_check` は `ok`
- コンテンツは `post` typeだけを利用している
- published、draft、pendingの各状態を利用している
- `terms`、`term_relationships`、`meta_data` は空
- `sort_order` は初期値以外を利用していない
- 全記事で `updated_at` と `created_at` が同値だった
- SQLiteの外部キー検査は無効

この結果はtaxonomyやmetadataを直ちに削除できる根拠にはならないが、少なくともこの
確認環境では現行コンテンツの必須機能ではない。実運用サイトについては、別途許可を
得て同じ集計だけを行う必要がある。

公開済みマイグレーションのうち `20250101000100` は、`v0.9.13` と
`v1.0.0-alpha.1` で内容が異なる。現在は冪等性確認が追加された版になっている。
今後は公開済みファイルを変更せず、新しいversionで補正する必要がある。

## 実装上の重要な発見

### 1. taxonomyのコードとスキーマが一致しない

`TaxonomyTrait` は存在しない `term_taxonomy` テーブルへjoinし、`terms.term_id` を
参照する。一方、現在のマイグレーションは `terms.id` と `terms.taxonomy`、および
`term_relationships` を作る。

さらに `CategoryModel::defineFieldDefinitions()` はローカル変数 `$fields` を作るだけで、
`$this->fieldDefinitions` へ代入していない。taxonomy機能は未完成または現在未使用の
可能性が高いが、利用実態を確認するまでは削除しない。

### 2. 本体レコードとmetadataが原子的に保存されない

記事本体のcreate/update後に、controllerがmetadataを1件ずつ保存する。共通の
トランザクションがないため、途中失敗すると本体だけ、またはmetadataの一部だけが
保存され得る。

### 3. metadataの所有者表現がPHPクラス名に依存する

`meta_data.target` には `static::class` が保存される。クラス名やnamespaceの変更、
継承modelの構成変更がデータ互換性へ直接影響する。

外部キーやcascadeもないため、本体をhard deleteしても関連metadataが孤児として
残る可能性がある。

### 4. metadataは現在の検索・並べ替えと整合しない

- 検索対象は通常カラムのfield定義だけで、metadataを検索しない
- 並べ替えの許可一覧にはmetadataも入り得る
- metadata名で並べ替えると、実テーブルに同名カラムがなくSQLエラーになる可能性がある
- metadata値はJSON文字列で、型、索引、一意性の契約がない

したがって、現在の `meta_data` をそのまま将来のmodel固有フィールド基盤とみなすのは
危険である。

### 5. 参照整合性がDBで保証されない

`posts.parent_id`、`posts.creator_id`、`terms.parent_id`、
`term_relationships.post_id`、`term_relationships.term_id`、
`meta_data.target_id` に外部キーがない。接続時のSQLite外部キー検査も無効である。

既存データを先に検査せず外部キーを追加すると、移行に失敗する可能性がある。

### 6. スキーマ制約とアプリケーション上の必須条件に差がある

マイグレーションから生成されたSQLiteでは、`posts.title`、`posts.slug`、
`users.username`、`users.password` などがnullableである。必須性の多くをform validation
だけに依存している。

statusやroleにもDB側の値域制約はない。既存データの値域調査後に、どこまでDBで保証
するか決める必要がある。

### 7. `updated_at` は自動更新されない可能性が高い

マイグレーションには `update => CURRENT_TIMESTAMP` があるが、生成されたSQLiteの
DDLに自動更新triggerはない。通常のupdate処理も `updated_at` を明示更新しない。

表示や静的生成で更新日時を信用する前に、現行サイトでの値の変化を特性テストする。

### 8. installerは初期管理者を `id = 1` と仮定する

初期ユーザーマイグレーションとinstallerの資格情報置換は `users.id = 1` を前提にする。
clean installでは成立するが、再実行、既存DB、将来のseed方式変更では脆い。

## 優先度別リスク

### 高

- 本体とmetadataの部分保存
- クラス名変更によるmetadata参照不能
- taxonomyのコードとスキーマの不一致
- 歴史的な同一version・異スキーマを識別できないこと

### 中

- 外部キーなしによる孤児データ
- metadataの検索・並べ替え・一意性・型の欠如
- DBのnullable/value domainとform validationの不一致
- `updated_at` の意味が実装と一致しない可能性

### 低または要利用実態確認

- `sort_order` がPostModelの編集fieldに含まれない
- `parent_id` の循環参照を防ぐ仕組みがない
- `terms.parent_id = 0` と `posts.parent_id = NULL` でroot表現が異なる
- app配下のSample実装が現在のDI APIと一致しない

## ここでは行わないこと

- 既存マイグレーションの修正
- 新しいDB方式の決め打ち
- taxonomyやmetadataの削除
- 外部キーやNOT NULL制約の即時追加
- ローカルDBの更新・削除

## 次に追加する特性テスト

1. clean install後の正規スキーマを固定するテスト
2. 古いスキーマ差を検出し、黙って正常扱いしないテスト
3. 記事の作成・更新とmetadata保存の現行挙動テスト
4. post typeごとのslug一意性テスト
5. 公開・予約・期限切れ・下書き・ごみ箱の抽出条件テスト
6. `updated_at` の現行挙動テスト
7. metadataを含む検索・並べ替えの失敗条件テスト
8. taxonomy経路が未完成であることを再現するテスト

## 設計前に確認したい利用実態

- 既存サイトで `terms` またはtaxonomy UIを使っているか
- `PostModel` 以外の独自model / post typeを実運用しているか
- `excerpt` と `eyecatch` 以外のmetadata keyが存在するか
- 記事の親子関係と `sort_order` を実際に使っているか
- 更新日時を公開画面や外部連携で利用しているか

これらは、テストサイトや本番サイトに対する読み取り専用の件数・値域調査で確認する。
実データ値そのものは記録しない。
