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
- runtimeクラスへ移した後も `require`、`setenv()`、`jlog()` の従来の初期化順と
  呼出規則を維持し、既存サイトの暗黙の互換性を優先した
- 無効時にログを出さないことと、有効時の従来フォーマットを単体テストで固定した
- ホストPHPUnitは設定・runtime系10件・20 assertionsが成功し、DB系15件はskipした
- runtime実装とテストのPSR-12検査、PHPStan level 6は成功した
- Docker PHPUnitは25 tests・59 assertionsが成功した
- E2E初回確認で認証失敗が続いたため、E2E用DB資格情報とBootstrap経由認証を
  値を表示せず診断し、どちらも正常であることを確認した
- 診断環境のeditor・auth E2Eと、再作成した空環境の全16件が成功した
- runtimeのPHPStan level 6は互換関数をautoloadする実際の初期化条件で成功した
- 未完了: Bootstrap::init()の戻り型など、公開APIの型整理
- 次にやるとよいこと: 後方互換を確認しながらBootstrapの型を明示する

## 2026-08-29: Bootstrap責務分離の仕上げ

- BootstrapとFrontend Bootstrapへstrict typesを追加した
- `Bootstrap::init()` が `App<DI\Container>` を返し、`run()` が同じ型を受け取ることを
  PHPDoc genericで明示した
- 実行時return typeは追加せず、既存の継承・呼出コードとの互換性を優先した
- Frontend Bootstrapの既存void APIもPHPDocで明示した
- ホストPHPUnit、PSR-12、対象BootstrapのPHPStan level 6が成功した
- clean installからの管理・公開E2E全16件が成功した
- Bootstrap責務分離は、既存呼出APIとruntime helperの互換性を維持した状態で完了とする
- 未完了: controller trait群にフォーム構築、validation、redirect、実行処理が混在している
- 次にやるとよいこと: CreateEditTraitからフォームページ構築責務を小さく分離する

## 2026-08-29: CreateEditTraitのフォーム構築を分離

- modelのfields取得、FormServiceによるHTML生成、flash message合成を
  `FormPageService` へ移した
- CreateEditTraitはcreate/edit固有のdata、文言、action URLを決める責務だけを残した
- Post、User、Account controllerの既存constructor引数は変更せず、独自controllerや
  手動生成コードの後方互換を維持した
- create/editのaction、button ID、errors、successの渡し方は変更していない
- FormPageServiceの呼出契約をmockによる単体テストで固定した
- ホストPHPUnitは11件・33 assertionsが成功し、DB系15件は環境要因でskipした
- 新サービスのPHPStan level 6と対象コードのPSR-12検査は成功した
- Docker PHPUnitは26 tests・72 assertions、管理・公開E2Eは全16件が成功した
- clean install後のcreate/edit、metadata空値、account、user、previewに回帰なしと確認した
- E2E側の資格情報設定競合はkontiki-dev専用entrypointで解消し、製品認証は変更していない
- 未完了: CreateEditTraitにpreview分岐、CSRF、validation、redirect判断が残る
- 次にやるとよいこと: validation結果と保存先redirectを決めるworkflowを小さく分離する

## 2026-08-29: CreateEditTraitの保存遷移判断を分離

- preview、create/edit form、保存後edit、indexの各target生成を
  `SaveRedirectService` へ移した
- previewは従来どおり文字列`'1'`の場合だけ選択する挙動を単体テストで固定した
- context・admin directory・IDから生成される既存URLをdata providerで固定した
- controllerの既存constructor引数は変更せず、後方互換を維持した
- CreateEditTrait内のCSRF、validation、保存、flash messageの実行順は変更していない
- ホストPHPUnitは15件・40 assertionsが成功し、DB系15件は環境要因でskipした
- 新サービスとテストのPHPStan level 6、対象コードのPSR-12検査は成功した
- Docker PHPUnitは30 tests・79 assertions、管理・公開E2Eは全16件が成功した
- preview、create/edit validation error、保存後redirect、account、userに回帰がないことを
  clean install環境で確認した
- 未完了: CreateEditTraitにmodel validationとflash error登録の責務が残る
- 次にやるとよいこと: validation結果の扱いを小さなサービスへ分離する

## 2026-08-29: CreateEditTraitのvalidation結果処理を分離

- model validationの呼出、結果判定、失敗時のflash error登録を
  `ModelValidationService` へ移した
- modelが返す既存のerror構造と、`id`・`context`のvalidation contextは変更していない
- Post、User、Account controllerの既存constructor引数は変更せず、後方互換を維持した
- 成功時にerrorを登録しないこと、失敗時に既存形式をそのまま登録することを
  単体テストで固定した
- ホストPHPUnitは32 tests・51 assertionsが成功し、DB系15件は環境要因でskipした
- 対象コードのPSR-12とPHPStan level 6が成功した
- Docker PHPUnitは32 tests・90 assertions、管理・公開E2Eは全16件が成功した
- 必須項目エラー、記事・ユーザー・アカウント保存、previewに回帰がないことを確認した
- 未完了: CreateEditTraitにCSRF分岐、保存処理、成功・例外flash登録が残る
- 次にやるとよいこと: 保存成功・失敗時のmessage生成と登録を小さく分離する

## 2026-08-29: CreateEditTraitの保存結果messageを分離

- 保存成功文の翻訳・placeholder展開とflash登録、例外messageのflash登録を
  `SaveMessageService` へ移した
- 既存のsuccess種別、例外errorの二重配列形式、一覧URL、controllerごとの文言上書きを
  維持した
- Post、User、Account controllerの既存constructor引数は変更していない
- 成功・例外時にFlashManagerへ渡す値を単体テストで固定した
- ホストPHPUnitは34 tests・56 assertionsが成功し、DB系15件は環境要因でskipした
- 対象コードのPSR-12と、runtime helper読込条件でのPHPStan level 6が成功した
- Docker PHPUnitは34 tests・95 assertions、管理・公開E2Eは全16件が成功した
- 未完了: CreateEditTraitにCSRF分岐と保存workflowの制御が残る
- 次にやるとよいこと: traitをさらに薄くする前に、CSRF処理を含む保存workflowの
  適切な分離境界を検討する

## 2026-08-29: 共通CSRF検証処理を分離

- BaseControllerに混在していたtoken抽出、検証、失敗時flash error登録、成功時再生成を
  `CsrfValidationService` へ移した
- redirect responseとJSON 403 responseの生成は、HTTP contextを持つBaseControllerに残した
- create/editだけでなく、削除・復元・ファイル操作も従来と同じ共通検証を利用する
- 欠落、空文字、文字列以外、無効tokenを拒否し、正常時だけtokenを再生成する契約を
  単体テストで固定した
- BaseControllerの既存constructor引数とprotected CSRF helper APIは変更していない
- ホストPHPUnitは40 tests・81 assertionsが成功し、DB系15件は環境要因でskipした
- 対象コードのPSR-12と、runtime helper読込条件でのPHPStan level 6が成功した
- Docker PHPUnitは40 tests・120 assertions、管理・公開E2Eは全16件が成功した
- 未完了: CreateEditTraitが保存workflow全体の制御とHTTP redirectを担っている
- 次にやるとよいこと: workflow全体を一度に抽象化せず、入力保持とpreview分岐から
  分離可能性を検討する

## 2026-08-29: アップロードファイルの実行可能拡張子を防止

- FileServiceがclient指定の拡張子を保存名へ引き継いでいたため、許可された内容MIMEに
  対応する固定拡張子（jpg、png、pdf）を使用するよう変更した
- 画像内容に`.php`などの実行可能拡張子を付けたファイルが公開uploads配下へ
  その名前で保存される経路を閉じた
- MIMEは一時ファイルの内容から検出し、許可リストにあっても安全な拡張子mappingが
  定義されていない種類は拒否する
- 既存の保存済みファイルやDB recordは変更せず、新規アップロードだけを対象とした
- 空になるファイル名には`upload`を使い、MIME検出不能な一時ファイルも拒否する
- ホストPHPUnitは44 tests・85 assertions、Docker PHPUnitは44 tests・124 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean install E2EではPNG内容を`.php`名で送信し、`.png`として保存・Markdown挿入され、
  全16件が成功した
- 未完了: ファイル配置後にDB登録が失敗すると孤立ファイルが残り、削除では物理削除後の
  DB失敗でrecordと実体が不整合になる可能性がある
- 次にやるとよいこと: upload・DB登録とdelete・DB削除の補償処理を設計し、失敗系を
  自動テストで固定する

## 2026-08-29: ファイルとDB recordの補償処理

- upload後にvalidationまたはDB登録が失敗した場合、新規配置した物理ファイルを
  uploadsルート境界を確認して撤去するようにした
- deleteでは物理ファイルを同一ディレクトリの一時名へrenameしてからDB recordを削除し、
  DB失敗・例外時は元の名前へrenameして復元する
- DB削除成功後にだけ一時ファイルをunlinkし、既に物理ファイルがない場合は従来どおり
  冪等な削除として扱う
- realpathでuploadsルート外やuploads内symlinkから外部を指すパスを拒否し、補償処理が
  任意ファイル操作にならないようにした
- uploads外の拒否、孤立ファイル撤去、staging・復元・確定削除、missing fileを
  FileService単体テストで固定した
- ホストPHPUnitは48 tests・104 assertions、Docker PHPUnitは48 tests・143 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean install E2Eでupload、Markdown挿入、一覧からの削除、物理URLの404まで確認し、
  全16件が成功した
- 未完了: DB削除成功後に一時ファイルのunlinkだけが失敗した場合、DB recordなしの
  staging fileが残る。この状態は公開される元ファイルや壊れたDB参照より安全側とする
- 次にやるとよいこと: FileControllerTraits\\CRUDTraitのresponse生成とfile lifecycle制御を
  分離し、controller失敗経路をmockで直接テスト可能にする

## 2026-08-30: upload pathと公開URLの変換を分離

- FileControllerにあったfilesystem path・公開URLの相互変換を`UploadPathMapper`へ移した
- FileControllerの既存protected methodはmapperへの委譲として残し、traitや独自継承側の
  互換性を維持した
- directory・URLとも単純な文字列prefixではなくpath segment境界でuploads配下を判定する
- uploadsと同じprefixを持つ別directory・URL、`..`、percent encoded traversal、別host・
  scheme、userinfo付きURL、相対pathを拒否する
- queryとfragmentはfilesystem pathへ含めず、安全な既存URLを正規化できるようにした
- ホストPHPUnitは69 tests・125 assertions、Docker PHPUnitは69 tests・164 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean installからの管理・公開E2Eは全16件成功し、upload・挿入・削除にも回帰なし
- 未完了: FileControllerTraits\\CRUDTraitがfile lifecycleとHTTP response生成を制御している
- 次にやるとよいこと: FileLifecycleServiceへDB・物理fileの補償workflowを移し、
  controller失敗経路を直接単体テストできるようにする

## 2026-08-30: file lifecycle workflowを分離

- upload、path変換、model validation、DB登録、失敗時物理file撤去を
  `FileLifecycleService`へ移した
- delete時のrecord取得、物理file staging、DB削除、失敗時復元、成功時確定削除も
  同serviceへ移した
- `FileLifecycleResult`でvalidation、storage、database、not foundの失敗理由を表し、
  HTTP statusと既存表示文言の選択はcontrollerに残した
- FileControllerの既存constructor引数は変更していない
- upload DB例外時の撤去、delete DB失敗時の復元、成功時の確定削除をmockで固定した
- ホストPHPUnitは74 tests・157 assertions、Docker PHPUnitは74 tests・196 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- readiness強化後のclean install管理・公開E2Eは全16件成功した
- 未完了: CRUDTraitにuploaded fileのHTTP request変換、update validation、response分岐が残る
- 次にやるとよいこと: `prepareUploadedFile`をPSR-7 upload adapterへ分離し、client MIMEを
  lifecycle入力から除いてrequest境界を明確にする

## 2026-08-30: PSR-7 uploaded file変換を分離

- PSR-7 requestからstorage入力へ変換する処理を`UploadedFileAdapter`へ移した
- upload error、空のclient filename、欠落・負数のsize、空の一時pathを境界で拒否する
- ブラウザが申告するclient MIMEは信頼せず、lifecycleへ渡す配列から除外した
- 実際の内容MIMEをFileService側で検出する既存の安全な処理は維持した
- FileControllerのpublic constructorは変更せず、既存の生成側との互換性を保った
- 正常系、upload error、各種欠落値、field欠落を単体テストで固定した
- ホストPHPUnitは82 tests・168 assertions、Docker PHPUnitは82 tests・207 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean installからの管理・公開E2Eは全16件成功し、file upload・挿入・削除にも回帰なし
- 未完了: CRUDTraitにfile description更新の入力検証、model更新、HTTP response分岐が残る
- 次にやるとよいこと: file metadata更新workflowを小さなserviceへ分離し、controllerを
  HTTP入力・responseの組み立てへ寄せる

## 2026-08-30: file description更新workflowを分離

- FileControllerのtraitにあったrecord読込、description差替え、validation、DB更新を
  `FileLifecycleService::updateDescription()`へ移した
- HTTP status、既存文言、validation errorの入力欄ID変換はcontroller側に残した
- descriptionが欠落またはnullの場合に既存値を保持する従来動作をテストで固定した
- record欠落、validation失敗、DB例外を結果オブジェクトへ変換し、workflow単体で検証した
- ホストPHPUnitは87 tests・188 assertions、Docker PHPUnitは87 tests・227 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean installからの管理・公開E2Eは全16件成功し、file description更新にも回帰なし
- 未完了: CRUDTraitにはCSRF検証と各operation結果からJSON responseへの変換が残る
- 次にやるとよいこと: traitを無理に空にせずHTTP adapterとして評価し、FileControllerの
  service生成責務をcontainer側へ移せるか依存性注入構成を調査する

## 2026-08-30: file workflow依存関係をDI containerへ移行

- FileModel、UploadPathMapper、UploadedFileAdapter、FileLifecycleService、FileControllerを
  `Dependencies`へ明示登録した
- 通常のSlim route解決では、file workflowのserviceをcontainerからControllerへ注入する
- FileControllerの従来の6引数constructor呼出しは、末尾のoptional依存関係とfallback生成で
  維持し、既存の独自生成コードを急に壊さない移行形にした
- upload URLとfilesystem pathの環境設定解決をcontainer factoryへ集約した
- Slim Appのgeneric container型と、traitから読むController propertyの可視性も整理した
- ホストPHPUnitは87 tests・191 assertions、Docker PHPUnitは87 tests・230 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean installからの管理・公開E2Eは全16件成功した
- 未完了: BaseControllerはCsrfValidationServiceを内部生成しており、他Controllerにも同じ
  transitional DI方針を広げるか検討が必要
- 次にやるとよいこと: Controller constructor互換性を維持したまま、CSRF serviceの生成を
  containerへ移し、Dependenciesのfactory肥大化を避ける構成も併せて検討する

## 2026-08-30: CSRF validation serviceをDI containerへ移行

- CsrfValidationServiceをDependenciesへ明示登録し、CsrfManagerとFlashManagerをcontainerから
  注入するようにした
- BaseControllerと全派生Controllerのconstructor末尾へoptional依存関係として追加した
- 従来のconstructor引数だけで生成した場合はBaseController内のfallbackを使うため、既存の
  独自Controller生成コードとの互換性を維持した
- FileControllerの明示factoryではCsrfValidationServiceも確実に注入する
- 注入したinstanceがBaseControllerで保持・利用されることを単体テストで固定した
- BaseControllerに残っていたSlim App generic、route配列、request・response配列の型注釈も
  実態に合わせて補完した
- ホストPHPUnitは88 tests・193 assertions、Docker PHPUnitは88 tests・232 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- login、記事・user・fileのCSRF経路を含むclean install E2Eは全16件成功した
- 未完了: Post・User・Account ControllerにはFormPageServiceなどの内部生成が残る
- 次にやるとよいこと: 3 Controllerで重複するform保存用service群の組立てを比較し、共有可能な
  dependency bundleではなく、責務ごとのservice注入へ段階的に移せるか調査する

## 2026-08-30: view serviceのmodel状態を明示化

- FormServiceとTableServiceの各operationへModelInterfaceを明示的に渡せるようにした
- FormPageService、一覧、削除・復元確認、login formの内部呼出しは対象modelを毎回渡す
- Post・User・Auth Controllerによる共有serviceへの事前`setModel()`を撤去した
- 旧`setModel()`とmodel省略時のfallbackは残し、既存の独自呼出しとの互換性を維持した
- modelを内部状態へ設定せずFormPageServiceが描画できることを単体テストで固定した
- ホストPHPUnitは88 tests・193 assertions、Docker PHPUnitは88 tests・232 assertionsが成功し、
  対象コードのPSR-12とPHPStan level 6も成功した
- clean install E2Eは全16件成功した

## 2026-08-30: 保存workflow serviceをDI containerへ移行

- PHP-DIはoptional constructor引数を自動配線しないことを実装から確認し、FileController以外の
  ControllerにもCsrfValidationServiceを明示配線した
- FormPageService、ModelValidationService、SaveRedirectService、SaveMessageServiceをcontainerへ
  登録し、Post・User・Account Controllerへ責務ごとに明示注入した
- 各Controllerの従来constructor呼出しでは内部fallbackを使えるため互換性を維持した
- dependency bundleは作らず、個別serviceを型で差し替え・検証できる構成にした
- ホストPHPUnitは88 tests・197 assertions、Docker PHPUnitは88 tests・236 assertionsが成功し、
  対象コードのPSR-12とPHPStan level 6も成功した
- login、記事・user・account操作を含むclean install E2Eは全16件成功した
- 未完了: FormService・TableService自身とrenderer・handlerは可変状態を持ち、互換APIも残る
- 次にやるとよいこと: renderer・handlerの状態保持範囲を調査し、1回のrender operation内へ
  閉じ込められる部分から段階的にstateless化する

## 2026-08-30: view renderer・handlerの操作順をカプセル化

- FormRendererに`renderFields()`、TableRendererに`renderForModel()`を追加し、通常経路では
  setterとrenderの呼出し順をservice外へ漏らさないようにした
- FormHandlerとTableHandlerに`decorate()`を追加し、HTML・model・error・successの設定から
  結果取得までを1回のoperationへ閉じ込めた
- FormServiceとTableServiceは新しいoperation APIだけを使うよう変更した
- 旧setter・render APIは後方互換用に残した
- model未指定時は曖昧なTypeErrorではなくLogicExceptionで契約違反を明示するようにした
- Form・Tableそれぞれのrender・message処理が1 operationとして委譲されることを4件の
  単体テストで固定した
- TableServiceの未使用に見えていたPhpRenderer依存はconstructor互換性のためprotected property
  として維持し、既存の派生classでも参照可能にした
- ホストPHPUnitは92 tests・224 assertions、Docker PHPUnitは92 tests・263 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean installからの管理・公開E2Eは全16件成功した
- 未完了: Renderer内部は1 operation中の作業状態をpropertyに保持している
- 次にやるとよいこと: FormRendererからfields propertyをなくし、render内部で引数として
  受け渡す。続いてTableRendererのdata・routes・contextを小さなrender contextへ整理する

## 2026-08-30: FormRendererの通常描画をstateless化

- `renderFields()`はfieldsをpropertyへ保存せず、引数からgroup化・描画まで直接処理するようにした
- 旧`setFields()`と引数なし`render()`は後方互換用として残した
- 新しいrender operationが旧fields状態を上書きしないことを単体テストで固定した
- field定義、group、attributesのarray型をPHPDocで明確にした
- ホストPHPUnitは93 tests・226 assertions、Docker PHPUnitは93 tests・265 assertionsが成功し、
  対象コードのPSR-12とPHPStan level 6も成功した
- clean install E2Eは全16件成功した

## 2026-08-30: TableRendererのoperation状態を分離

- TableRendererのfields、data、routes、context、modelなどの作業状態へ具体的な型と初期値を付けた
- 未使用だったtable・postType propertyを削除した
- `renderForModel()`開始前の互換状態を保存し、成功・例外のどちらでも`finally`で復元するようにした
- 通常operationが旧model状態を汚さないことと、例外時にも復元することを単体テストで固定した
- 一覧data、field、route、row、status値のarray型を明確にした
- ホストPHPUnitは95 tests・230 assertions、Docker PHPUnitは95 tests・269 assertionsが成功し、
  対象コードのPSR-12とPHPStan level 6も成功した
- clean install E2Eは全16件成功した
- 未完了: TableRendererはoperation実行中には複数propertyへ作業状態を保持している
- 次にやるとよいこと: 削除・ゴミ箱・復元traitに残る重複workflowを先に整理し、その後
  TableRendererの完全なcontext object化が有益か再評価する

## 2026-08-30: record削除・状態変更workflowを分離

- hard delete可能なmodelの`DeletableModelInterface`と、trash・restore可能なmodelの
  `SoftDeletableModelInterface`を追加した
- PostModelとUserModelが実際に持つ削除能力をinterfaceで明示した
- `RecordMutationService`へdelete validation、delete実行、trash・restore実行を移した
- 成功、modelがfalseを返した失敗、例外、validation失敗を`RecordMutationResult`で区別する
- TrashRestoreTraitの動的な`$model->$actionType()`を、許可された2操作のmatch分岐へ置き換えた
- HTTP redirect、既存文言、例外時だけerrorをflashへ積む従来動作はtrait側に維持した
- Post・User ControllerへserviceをDIし、従来constructorではfallback生成する互換性も残した
- validation、delete成功・false・例外、trash、restore、不正actionを単体テストで固定した
- ホストPHPUnitは100 tests・246 assertions、Docker PHPUnitは100 tests・285 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 記事のtrash・restore・完全削除、user削除を含むclean install E2Eは全16件成功した
- 未完了: DeleteTraitとTrashRestoreTraitには確認form構築とflash message・redirect分岐が残る
- 次にやるとよいこと: 確認formの共通構築を小さなserviceへ移し、traitごとの文言とbutton指定を
  dataとして渡せるようにする

## 2026-08-30: record操作の確認form構築を分離

- 削除・trash・restore確認画面の設定を表す`ConfirmationFormConfig`を追加した
- field取得、form変数構築、FormService描画、error装飾を`ConfirmationFormService`へ移した
- action URL、説明文、button class・ID・文言は各traitが型付きconfigとして指定する
- DeleteTraitとTrashRestoreTraitから重複していたfields・formVars・message処理を除去した
- Post・User ControllerへserviceをDIし、旧constructorではFormServiceからfallback生成する
- fields、action、CSRF、button設定、error、modelが従来どおりFormServiceへ渡ることを
  単体テストで固定した
- ホストPHPUnitは101 tests・259 assertions、Docker PHPUnitは101 tests・298 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 削除・trash・restore確認と実行を含むclean install E2Eは全16件成功した
- 未完了: 2 traitには成功・失敗messageとredirectの似た分岐が残る
- 次にやるとよいこと: message文言の差を保持したまま、record mutation結果からflash登録と
  redirect先を決める小さなpresenterを検討する

## 2026-08-30: record操作結果のfeedback判断を分離

- 操作別の成功・失敗文言とredirect先を表す`RecordMutationFeedbackConfig`を追加した
- `RecordMutationResult`からflash message登録とredirect target選択を行う
  `RecordMutationFeedbackService`を追加した
- 成功時はsuccess message、例外時だけerror、modelがfalseを返した場合はmessageなしという
  従来の細かな挙動を維持した
- DeleteTraitとTrashRestoreTraitは操作結果とconfigをserviceへ渡し、返されたtargetへ
  PSR-7 redirect responseを作るだけに寄せた
- Post・User ControllerへserviceをDIし、旧constructorではFlashManagerからfallback生成する
- 成功・例外・false失敗のmessageとtargetを単体テストで固定した
- ホストPHPUnitは104 tests・268 assertions、Docker PHPUnitは104 tests・307 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 記事・userの削除系を含むclean install E2Eは全16件成功した
- 未完了: traitにはrecord取得失敗時のredirectやCSRF前後のHTTP orchestrationが残る
- 次にやるとよいこと: ここはHTTP adapterとして妥当な責務か評価し、過剰にservice化せず、
  Phase 3の残件である暗黙のglobal・environment依存の調査へ移る
