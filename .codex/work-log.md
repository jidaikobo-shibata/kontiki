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
- 空のconsumer projectからpath repository経由で `composer require` し、
  `vendor/bin/kontiki install`、全9 migrations、ログイン画面を確認。
- 再installは既存ファイルを検出して拒否。初期既知パスワードは無効化され、
  `.env` とSQLite DBは0600、composer/config/dbへのHTTPアクセスは403。
- 未完了: framework本体コードの移管、`migrate` と `status` コマンド、
  v0.9既存サイトからの移行試験。
- 対話installerの各質問へデフォルト値を明示し、Enterで採用可能にした。
- timezoneは `TZ`、PHP設定、実行時timezone、OS設定、UTCの順で安全な候補を
  提案し、Base URL等は専用環境変数からも候補を受け取れる。
- 選択式質問は番号と値のどちらを入力できるかプロンプトに明示した。
  Base URLは特定の検証ポートに依存しない `http://localhost` を既定値とし、
  必要な環境では引き続きオプションまたは環境変数で上書きできる。
- frameworkのmain履歴をv1の履歴へ接続したうえで、CMS実装、組み込みview、
  既存の9 migrationsを `kontiki` へ取り込んだ。
- Composer依存を直接定義し、`jidaikobo/kontiki-framework` をlockから削除。
  生成するPhinx設定も `jidaikobo/kontiki/db/migrations` を参照するよう変更した。
- 全PHPファイルの構文、Composer定義、依存解決を確認。ネットワーク制限下の
  再auditだけはDNS解決に失敗したが、依存更新時のauditは0件だった。
- 未完了: 単一package状態でのclean installと、v0.9既存サイト相当の更新試験。
- 単一packageのpath repositoryがsymlinkになる環境で、`Bootstrap` がpackageの
  実体パスからサイトルートを逆算し `/config` を参照する不具合を確認。
- 新規installerが生成するentry pointからサイトルートを明示して解消した。
  `Bootstrap::init()` の従来の1引数呼び出しは互換性のため維持している。
- 既存サイトの公開側entry pointは1引数呼び出しのまま残るため、Composerの
  root package `install_path` を標準のサイトルート判定元に追加した。明示指定と
  従来のディレクトリ計算もフォールバックとして維持する。
- v1へ明示的な `status` と `migrate` コマンドを追加。サイト側の旧
  `phinx.php` には依存せず、package所有のmigrationと `.env` のDB設定から
  実行時Phinx設定を構築する。
- `status` は読み取り専用で、未適用・履歴欠落・適用済みを区別する。
  `migrate` は対象project、environment、DBを表示し、対話時は確認を必須とする。
- DBファイルと環境ファイルが実在しない場合は、暗黙に新規作成せず失敗する。
- 未完了: clean installとv0.9更新後環境で両コマンドを統合試験する。
- 8083新規環境と8084既存更新環境で `status` / `migrate` を検証し、未適用の
  検知と冪等な復旧も確認したため、最初のprereleaseを `v1.0.0-alpha.1` とする。
- alpha利用者が意図せず後続版へ更新しないようREADMEは完全固定指定とし、CLIの
  version表示はComposerが認識する実際のpackage versionから取得する。
- commit `6b9e6de` にannotated tag `v1.0.0-alpha.1` を付け、GitHubでは
  Pre-releaseとして公開。Packagistでも同じsource commitの版を確認した。
- Packagistのzipを完全固定でDocker内の空projectへ導入し、install、全9
  migrations、status、CLI version表示、Composer audit 0件まで確認した。
- ホストPHPにPDO_SQLITEがない失敗試験で、migration前に生成した途中ファイルが
  残り再試行を妨げることを確認。alpha.2候補では事前要件検査または安全な
  rollbackを最優先で追加する。
- installerは書き込み前にPDO_SQLITEとpackage migrationの可読性を検査する。
  書き込み開始後に失敗した場合は、事前に不存在を確認した生成対象だけを逆順で
  削除し、既存ファイルや既存ディレクトリには触れず再試行可能な状態へ戻す。
- PDO_SQLITEのないホストではcomposer.json以外を一切生成せず停止することを確認。
  Dockerでは書き込み途中の権限エラーとmigration後の資格情報更新エラーで、
  生成対象がrollbackされることを確認した。
- Composer vendor配置を再現したDocker環境では、修正後も全9 migrations、
  ランダム資格情報生成、statusまで正常に完了した。
- 事前確認後の競合でも他者のファイルを上書き・rollback削除しないよう、生成は
  排他的な新規作成とし、実際に作成完了したファイルだけを追跡して削除する。
- 排他的作成後も、拡張不足の事前停止、権限エラー時rollback、正常installと
  statusの3経路を再検証した。
## 2026-08-29: 開発ロードマップを記録

- `.codex/development-roadmap.md` を追加した
- 既存サイトを介護しながら改善することを全体の前提として明文化した
- セキュリティ、管理画面のアクセシビリティ、安定動作、ポータビリティ、
  model 駆動 UI、前身 CMS の移行、静的生成を長期目標として整理した
- DB は既存の問題を永久保存せず、アドホックなテーブルも増やさない方針とした
- DB の具体形は決め打ちせず、監査、特性テスト、ADR、追加移行、照合、互換期間の
  順で安全に決めることとした
- 次は現行 DB スキーマ、マイグレーション、model、SQL の読み取り専用監査を行う
