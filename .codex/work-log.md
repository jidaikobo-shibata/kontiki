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
- 不整合を再現していた非公開テストサイトでも、カラムを重複させずPhinx履歴を
  正常化できることを確認。
- 未完了: framework v0.9.64を取り込んだlock更新とinstaller v0.9.14の公開。
- 公開されたframework v0.9.64へlockを更新し、installer側のマイグレーションと
  vendor側が同一内容であること、Composer auditで勧告0件を確認。
- 未完了: clean installの再検証とinstaller v0.9.14の公開。
- commit済みinstaller候補から隔離Docker volumeへ `composer create-project` を
  実行し、framework v0.9.64、全9 migration up、SQLite整合性ok、ログイン画面、
  Composer audit勧告0件を確認。
- 未完了: installer v0.9.14のタグ公開。

### Maintenance branch

- 安全な公開版 `v0.9.14` から `0.9-maintenance` ブランチを作成し、originへ公開。
- `0.9-maintenance` は既存installerの互換修正・セキュリティ修正に限定する。
- `main` は統合後のv1開発に使用し、既存サイトへ破壊的変更を配布しない。
- v0.9タグはclean installと既存サイト更新を検証できた修正にだけ付ける。

### V1 explicit installer foundation

- v1を単一のupdate可能なComposer libraryとし、`vendor/bin/kontiki install` を
  明示実行する設計を `.codex/v1-architecture.md` に記録。
- 自動 `post-create-project-cmd` を廃止し、library metadata、CLI entry point、
  対話・非対話オプション、dry-run、上書き拒否を追加。
- 初期管理者の既知パスワードをそのまま使わず、migration後に暗号学的乱数で
  生成した一回表示の資格情報へ置換する処理を追加。
- framework v0.9.64はCLI境界の検証中だけ一時依存として維持する。
- Composer audit 0件、PHPCS、PHPStan level 8、Composer定義を確認。
- 未完了: 別consumer projectでの導入試験、framework本体コードの移管、
  `migrate` と `status` コマンド。
