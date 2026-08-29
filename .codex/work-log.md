# Work Log

## 2026-08-29

- `jidaikobo/log` の廃止準備として、Composer の実行時依存から削除。
- `Jidaikobo\Log` の直接参照を除去し、互換用 `jlog()` は PHP 標準の
  `error_log()` を利用する実装として維持。
- 独自ロガーによる PHP エラー・例外ハンドラー登録を廃止し、PHP および
  Slim の標準的なエラー処理に委譲。
- Composer ロックと `vendor/` から `jidaikobo/log` v2.0.8 を削除。
- Composer定義、変更PHPファイルの構文、PHPCSは正常。
- PHPStanは今回と無関係な既存9件で失敗。PHPUnitはSQLite PDOドライバー不足と
  既存ルート期待値の不一致により3件失敗。
- `composer audit` で既存依存に3件の勧告を確認: Slim（medium）、PHPUnitと
  PHP_CodeSniffer（high）。本作業では後方互換に影響する依存更新は未実施。
- 未完了: 上記既存検証エラーとセキュリティ勧告への対応。
- 次にやるとよいこと: 既存問題を別作業で解消後、新しい framework バージョンを
  公開し、利用側で更新試験を行う。

### Security dependency update

- Slimを4.15.2、PHPUnitを11.5.56、PHP_CodeSnifferを3.13.6へ更新し、
  `composer.json` に各修正版の下限を明記。
- `composer audit` は勧告0件。
- Composer定義と変更箇所のPHPCSは正常。
- PHPStanの既存9件、SQLite PDOドライバー不足を含むPHPUnitの既存3件は継続。
- 未完了: 既存の静的解析・テスト環境の問題。
- 次にやるとよいこと: SQLite対応環境で回帰テストを再実行する。

### php-markdown v1.0.8

- `jidaikobo/php-markdown` をv1.0.7からv1.0.8へロック更新。
- 同版では廃止予定の `jidaikobo/log` が開発依存から除去済み。
- Composer更新時のセキュリティ勧告は0件。
- v0.9.63公開準備として、Composer監査と差分確認を完了。
- 次にやるとよいこと: 利用側でv0.9.63への更新試験を行う。

### Idempotent display-updated migration

- `display_updated_at` カラムが存在する一方でPhinx履歴がない既存サイトを確認。
- 公開済みマイグレーションを明示的な `up()` / `down()` に変更し、カラムの
  存在確認によって新規DB、正常な既存DB、不整合DBのすべてで安全に実行可能にした。
- Docker上の一時SQLite DBで、カラム・履歴の組み合わせとclean installを検証。
- PHP構文、PHPStan level 8、DB整合性は正常。PHPCSのグローバル名前空間警告と
  既存PHPUnitのルート期待値不一致は今回の変更外として継続。
- 未完了: 修正版のタグ公開と既存テストサイトのPhinx履歴正常化。
- 次にやるとよいこと: 修正版を公開後、既存サイトでmigrateを実行する。
- 不整合を再現していた非公開テストサイトへ限定バックアップ後に適用し、
  カラムを重複させずPhinx履歴のみ正常化できることを確認。
- 適用後は全9 migrationがup、SQLite integrity checkはok、公開画面と
  ログイン画面はいずれもHTTP 200。
- 未完了: v0.9.64のタグ公開。
