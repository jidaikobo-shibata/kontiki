# ADR-001: 保存処理をcontrollerから分離する

状態: 採用

決定日: 2026-08-29

## 背景

現在の `CreateEditTrait` は、通常カラムと `meta_data` を分割し、記事またはユーザーを
保存した後にmetadataを1件ずつ保存している。この処理はcontroller内にあり、全体を
囲むtransactionがない。

監査では、確認環境と実運用環境のどちらも `meta_data` を利用していなかった。また、
現在のmetadataにはPHPクラス名への依存、型・索引・一意性の欠如、検索と並べ替えの
不整合がある。

一方、公開APIと既存サイトの未知の利用を考慮すると、テーブルや読取処理を直ちに
削除することはできない。

## 決定

- `meta_data` は旧形式との互換用保存方式として当面残す
- `meta_data` をv1の正規的なmodel固有フィールド保存方式とは位置づけない
- controllerは保存順序、metadata分割、transactionを管理しない
- 通常レコードと追加フィールドの保存を `RecordPersistenceService` へ集約する
- 通常保存可能なmodelと旧metadata対応modelを別インターフェースで表す
- 通常レコードと旧metadataは、同じDB transaction内で保存する
- 将来の正規保存方式は、controllerを変更せず保存サービス内で切り替えられるようにする
- 既存マイグレーションと既存テーブルは、この段階では変更しない

## 境界

`PersistableModelInterface` は通常レコードのcreate/updateを表す。

`LegacyMetadataModelInterface` は現在の `meta_data` CRUDだけを表す。名前に
`Legacy` を含め、将来のmodelフィールドAPIと誤認しないようにする。

`RecordPersistenceService` は次を担当する。

1. model定義を使って通常値と旧metadata値を分ける
2. createまたはupdateを実行する
3. 旧metadataを作成、更新、削除する
4. 以上を1 transactionとしてcommitまたはrollbackする

validation、HTTP redirect、flash messageは引き続きcontroller側の責務とする。

## 互換性

- metadata field定義がないUserModelとAccountModelも同じサービスを利用する
- 空文字のmetadataを削除する既存挙動を維持する
- metadataが送信されなかった場合は既存値を変更しない
- create/update後のredirectとメッセージを変更しない
- 既存の `MetaDataTrait` と `meta_data` テーブルを残す

## 結果

### 良い結果

- 本体だけが保存される部分失敗を防げる
- controllerの責務が減る
- 旧metadataと将来の正規保存形式を区別できる
- 新しい保存形式の検討前に、安全な差し替え境界ができる

### 注意点

- transactionは同じ `Database` connectionを共有する必要がある
- metadataのクラス名依存や検索問題そのものは、この決定だけでは解消しない
- 独自modelが公開インターフェースを実装していない場合、v1向けの調整が必要になる

## 今後の判断

新しい正規保存形式は、フィールド型、検索、並べ替え、一意性、索引、移行方法を
別ADRで決める。それまでは新しい機能を `meta_data` に積み増さない。
