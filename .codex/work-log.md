# Work Log

## 2026-08-29

- installerが保持する `display_updated_at` マイグレーションをframework側と
  同一の冪等実装に変更。
- カラムが既に存在して履歴だけが欠けたサイトでも、安全にPhinx履歴を
  正常化できるようにした。
- Docker上の一時SQLite DBで、新規DB、未適用DB、不整合DB、適用済みDBを検証。
- 未完了: installerとframeworkで重複するマイグレーション所有権の統合。
- 次にやるとよいこと: v0.9系の互換修正版を公開後、統合ブランチで所有元を
  CMS本体へ一本化する。
