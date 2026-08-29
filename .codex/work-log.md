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
## 2026-08-29: DB・modelの読み取り専用監査

- `.codex/database-audit.md` に現行スキーマ、model、保存経路の監査結果を記録した
- clean installとv0.9更新済み環境は9マイグレーション適用済みで整合性が正常だった
- リポジトリに残る非追跡DBは8マイグレーション時代の異なるスキーマだった
- taxonomyのコードとスキーマの不一致、metadataの非原子的保存、クラス名依存、
  外部キー無効などを主要リスクとして整理した
- 非公開の確認環境を読み取り専用で集計し、taxonomy、metadata、sort_orderが現在は
  使われておらず、`updated_at` も作成時から変化していないことを確認した
- 既存マイグレーションやDBは変更していない
- 次は正規スキーマを決める前に、現行動作と古いスキーマ差の特性テストを追加する

## 2026-08-29: 実運用DBの読み取り専用監査

- ユーザーの許可を得て、本番SQLiteを `PRAGMA query_only = ON` にして集計した
- 本文、ユーザー名、パスワードなどの実データ値は取得・記録していない
- 全9マイグレーション適用済み、整合性正常、孤児参照なしを確認した
- 独自model、taxonomy、metadata、記事親子関係、独自sort順は利用されていなかった
- 空の `parent_id` がTEXTで保存され、`updated_at` が更新されない現象を確認した
- 公開用監査には匿名化した事実だけを記録し、運用情報は非公開摘録へ分離した
## 2026-08-29: DBスキーマ特性テストの開始

- PHPUnit 11用の最小構成と `composer test` scriptを追加した
- テストごとに `/tmp` へ空のSQLite DBを作り、公開済み9マイグレーションを適用する
  `SchemaCharacterizationTest` を追加した
- 期待テーブル、`users.role`、`posts.display_updated_at`、全migration versionを固定した
- 現状の外部キーなし、`updated_at` 非更新、空の `parent_id` のTEXT保存も、改善前の
  基準として明示的にテストへ固定した
- ホストはPDO_SQLITE不足のため5件skip、Dockerでは5件・10 assertionsが成功した
- テストは一時DBだけを書き換え、既存サイトと既存ローカルDBには触れていない
- 次は記事・metadata保存と公開状態の抽出条件をmodelレベルの特性テストにする
## 2026-08-29: 記事modelの特性テスト

- DBテストの一時プロジェクト生成処理を `DatabaseTestCase` に共通化した
- metadataの作成、読取、更新、削除と、hard delete後に孤児が残る現状を固定した
- `meta_data.target` がPHPクラス名に依存する現状を固定した
- metadata並べ替えは `meta_data` をjoinせず、SQLiteで黙って無視されると確認した
- all、published、reserved、expired、pending、draft、trashの抽出条件を固定した
- `post_type + slug` の一意制約が、同じtype内だけに適用されることを固定した
- Dockerの一時DBで11 tests・30 assertionsが成功した
- アプリケーション実装、既存DB、既存マイグレーションは変更していない
- 次は記事本体とmetadataの保存を同一トランザクションへ移すための境界を検討する
## 2026-08-29: transactionalな保存境界を導入

- `.codex/adr-001-persistence-boundary.md` に旧metadataの互換方針を記録した
- `meta_data` は当面残すが、v1の正規保存形式として機能追加しないと決定した
- 通常保存用 `PersistableModelInterface` と旧metadata用
  `LegacyMetadataModelInterface` を分離した
- `RecordPersistenceService` に通常値とmetadataの分割、create/update、metadata保存、
  transaction管理を移した
- Post、User、Account controllerは同じ保存サービスを利用するようにした
- controllerからmetadataの一時状態と保存順序の実装を削除した
- metadata保存失敗時にcreate/updateの本体変更がrollbackされるテストを追加した
- Dockerで15 tests・39 assertionsが成功した
- v1 DockerサイトでPost、User、Account controllerのDI生成とログイン画面を確認した
- DBスキーマ、既存マイグレーション、既存サイトのDBは変更していない
- 次は管理画面で記事とユーザーの実保存を手動確認した後、正規フィールド保存方式の
  要件整理へ進む

## 2026-08-29: Bootstrapの動作不変リファクタリングを開始

- `editor` はユーザー管理を除く記事管理を担当し、作成者を問わず全記事を編集可能と
  する現行権限を `.codex/authorization.md` に明文化した
- Bootstrap内に埋め込まれていたプロジェクトパス解決を `ProjectPathResolver` へ分離した
- 明示パス、Composer root、development fallback、従来のvendor配置fallbackを単体テストで固定した
- パス解決の優先順位とfallback結果は変更しておらず、既存サイトとの互換性を維持する
- ホストPHPUnitは4件成功し、PDO_SQLITE依存の15件は環境要因でskipした
- 新設クラスのPSR-12検査とPHPStan level 6は成功した
- Dockerで19 tests・43 assertions、clean install後の管理・公開E2E 16件が成功した
- `.env` のディレクトリ解決と読込を `EnvironmentLoader` へ分離した
- 指定環境の読込と、ファイル不存在時に従来のDotenv例外を保つテストを追加した
- ホストPHPUnitは設定系6件・7 assertionsが成功し、DB系15件は環境要因でskipした
- 設定クラス2件のPSR-12検査とPHPStan level 6は成功した
- EnvironmentLoader追加後もDockerで21 tests・46 assertionsが成功した
- 管理・公開E2Eは並列検証時にChromiumが一度終了したが、単独再実行では全16件が成功した
- clean install、Composer rootの検出、ログイン、記事・ユーザー・公開画面に回帰なしと確認した
- 未完了: DI構築、middleware登録、routing登録がBootstrapに残っている
- 次にやるとよいこと: DI構築を専用のapplication factoryへ分離する

## 2026-08-29: Slimアプリケーション構築を分離

- DIコンテナ、Slim App、error middleware、base path、依存登録を
  `ApplicationFactory` へ移した
- middlewareの登録順序を `MiddlewareRegistrar`、サイト固有Routesの選択と登録を
  `RouteRegistrar` へ移した
- Slimの静的container状態に依存せず、`createFromContainer()` で明示的にDIを渡す形にした
- サイト固有 `App\Config\Routes` の従来のduck typingは維持し、interface実装を強制していない
- factoryがbase pathと主要DI定義を持つことを単体テストで固定した
- ホストPHPUnit 22件中、設定系7件・11 assertionsが成功し、DB系15件はskipした
- 分離した3クラスのPSR-12検査とPHPStan level 6は成功した
- Docker PHPUnitは22 tests・50 assertions、管理・公開E2Eは全16件が成功した
- clean install、DI解決、middleware、認証・権限、記事・ユーザー、公開画面に回帰なしと確認した
- Bootstrapは設定準備と各registrarを順に呼ぶ起動オーケストレーターまで薄くなった
- 未完了: グローバル関数読込、ENV設定、言語初期化、実行時間計測がBootstrapに残る
- 次にやるとよいこと: 互換用グローバル関数を維持しながらruntime初期化を分離する

## 2026-08-29: runtime初期化と計測を分離

- 開始時刻、互換関数読込、ENV・PROJECT_PATH、言語初期化を `RuntimeInitializer` へ移した
- 実行時間の計算とログ出力を `PerformanceReporter` へ移した
- `Bootstrap::performance()` とグローバル `performance()` は互換APIとして維持した
- runtime内部は `setenv()` や `jlog()` の定義順に依存せず、互換関数は外部向けに残した
- 無効時にログを出さないことと、有効時の従来フォーマットを単体テストで固定した
- ホストPHPUnitは設定・runtime系10件・20 assertionsが成功し、DB系15件はskipした
- runtime実装とテストのPSR-12検査、PHPStan level 6は成功した
- 未完了: Docker全PHPUnitと管理・公開E2Eによる統合確認
- 次にやるとよいこと: 全回帰後、Bootstrapの型と公開APIを整理して責務分離を完了する
