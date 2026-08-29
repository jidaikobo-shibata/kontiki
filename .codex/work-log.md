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
