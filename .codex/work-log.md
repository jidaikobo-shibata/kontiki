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

## 2026-08-30: preview rendererのpath解決を分離

- PreviewTraitにあったPROJECT_PATH参照とview directory選択を
  `PreviewRendererFactory`へ移した
- サイト側の`app/views/{admin directory}`を優先し、存在しなければpackage側の
  `src/views/{admin directory}`へfallbackする従来の互換動作を維持した
- admin directoryは英数字、underscore、hyphenとslash区切りだけを許可し、path traversalを
  factory境界で拒否するようにした
- PostControllerへfactoryをDIし、従来constructorを直接利用する場合のfallbackも残した
- app優先、src fallback、末尾slash、path traversal拒否を単体テストで固定した
- ホストPHPUnitは108 tests・272 assertions、Docker PHPUnitは108 tests・311 assertionsが
  成功し、対象コードのPSR-12とPHPStan level 6も成功した
- previewの保存済み・未保存経路を含むclean install E2Eは全16件成功した
- 未完了: APPLANGやBASEPATHなど、bootstrap由来の暗黙のenvironment参照が残る
- 次にやるとよいこと: URL生成のbase path、またはhelp表示のlocale path解決を境界として
  切り出せるか調査する

## 2026-08-30: help表示のlocale path解決を分離

- HelpControllerに直書きされていたAPPLANG参照、locale path組み立て、`require`と
  `file_get_contents`を`HelpContentService`へ移した
- 言語名は英数字、underscore、hyphenだけを許可し、environment値を介したpath traversalを
  service境界で拒否するようにした
- 読み取れないhelp fileは曖昧なwarningやfalseのまま渡さず、例外として明示するようにした
- HelpControllerへserviceをDIし、従来constructorを直接利用する場合のfallbackも残した
- PHP helpの描画、Markdown helpの読取、末尾slash、path traversal、欠落fileを単体テストで
  固定した
- ホストPHPUnitは112 tests・276 assertions（15 skipped）、Docker PHPUnitは
  112 tests・315 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean install E2Eは全16件成功した。ただしhelp専用E2Eは現時点では存在しない
- 未完了: viewやcontroller、rendererにBASEPATHの直接参照が広く残る
- 次にやるとよいこと: URL生成を一括変更せず、まずPHP側のbase path結合規則を小さなvalue
  objectまたはserviceとして固定できるか調査する

## 2026-08-30: 管理URLのbase path結合規則を集約

- `AdminUrlGenerator`を追加し、BASEPATHと管理画面内pathの結合規則を一箇所へ集約した
- 空文字と`/`はrootとして扱い、base pathの先頭・末尾slashとtarget先頭slashを正規化する
- FormServiceのform actionとRoutesServiceの収集routeへ適用した
- 両serviceへgeneratorをDIし、従来constructorを直接利用する場合はenvironmentから生成する
  fallbackを残した
- root、nested path、slash有無、空targetの規則と、form action・Slim routeへの適用を
  単体テストで固定した
- RoutesServiceのroute配列shapeをPHPDocで明示した
- ホストPHPUnitは121 tests・290 assertions（15 skipped）、Docker PHPUnitは
  121 tests・329 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- form送信、一覧操作、previewを含むclean install E2Eは全16件成功した
- 未完了: BaseController、TableRenderer、各trait、view、locale fileにBASEPATH直接参照が残る
- 次にやるとよいこと: HTTP redirectとTableRendererのaction URLへ同じgeneratorを適用し、
  controllerの後方互換constructorを維持できるか確認する

## 2026-08-30: redirectと一覧操作URLへgeneratorを適用

- BaseControllerのpath指定redirectとTableRendererのedit・delete・trash・restore・preview URLへ
  `AdminUrlGenerator`を適用した
- BaseControllerは既存必須依存のRoutesServiceから同じgeneratorを受け取るため、派生controllerの
  公開constructor signatureを変更せず後方互換性を維持した
- TableRendererはgeneratorをDIし、従来constructorを直接利用する場合のfallbackも残した
- nested base pathでのredirect Locationと一覧action linkを単体テストで固定した
- ホストPHPUnitは123 tests・293 assertions（15 skipped）、Docker PHPUnitは
  123 tests・332 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- login、form送信、一覧操作、previewを含むclean install E2Eは全16件成功した
- 未完了: trait、file controller、view、locale fileにBASEPATH直接参照が残る
- 次にやるとよいこと: PHP側traitのpagination・index redirect・file asset設定へgeneratorを適用し、
  viewへは文字列attributeとして渡す境界を検討する

## 2026-08-30: controller traitの管理URL生成を集約

- 記事・user一覧のpagination、保存成功message内の一覧URL、file list pagination、
  file管理JavaScriptへ渡すbase pathを`AdminUrlGenerator`経由へ移した
- generatorへ正規化済みbase pathそのものを返す`basePath()`を追加し、root環境でJavaScriptの
  URLがdouble slashにならない従来挙動を維持した
- ControllerとそのtraitからBASEPATHの直接参照を除去した
- 併せて対象traitに残っていたPSR-12の長い行を動作変更なしで整形した
- ホストPHPUnitは124 tests・295 assertions（15 skipped）、Docker PHPUnitは
  124 tests・334 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- clean installの初回起動が一度だけ早期終了したが、再作成時はinstaller・migration・healthcheckが
  正常終了し、同環境でE2E全16件が成功した。PHP例外は記録されていない
- 未完了: view、locale file、AuthMiddleware、ApplicationFactoryにBASEPATH直接参照が残る
- 次にやるとよいこと: viewへadmin base pathを共通attributeとして渡し、template内のenv参照を
  表示dataへ置き換える

## 2026-08-30: layoutとsidebarのbase path参照を表示dataへ移行

- BaseControllerとDashboardControllerが正規化済みbase pathをPhpRendererの共通attributeとして
  渡すようにした
- layoutとsidebarからBASEPATHの直接参照を除去し、受け取った`basePath`だけでasset、navigation、
  account、help、logout URLを生成するようにした
- DashboardControllerのroute配列、RoutesService引数、Slim App generic型を明示した
- ホストPHPUnitは124 tests・295 assertions（15 skipped）、Docker PHPUnitは
  124 tests・334 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- navigationとfile modalを含むclean install E2Eは全16件成功した
- 未完了: locale file、AuthMiddleware、ApplicationFactoryにBASEPATH直接参照が残る
- 次にやるとよいこと: AuthMiddlewareへAdminUrlGeneratorをDIし、未認証redirect pathの
  base path除去を文字列長依存から明示的なmethodへ移す

## 2026-08-30: 未認証redirect pathのbase path処理を分離

- `AdminUrlGenerator::withoutBasePath()`を追加し、未認証時の戻り先からbase pathを除く規則を
  AuthMiddlewareの文字列長計算から分離した
- base path全体またはslash境界付きprefixだけを除去し、`/admin`と`/administrator`のような
  部分一致ではpathを変更しない
- AuthMiddlewareへgeneratorをDIし、従来constructorを直接利用する場合のfallbackも残した
- exact match、nested path、類似prefix、範囲外pathを単体テストで固定した
- AuthMiddlewareのexcluded route配列へ要素型を明記した
- ホストPHPUnitは125 tests・299 assertions（15 skipped）、Docker PHPUnitは
  125 tests・338 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 外部未認証404と内部login redirectを含むclean install E2Eは全16件成功した
- 未完了: ApplicationFactoryのSlim base path設定、locale file内のhelp URLにBASEPATH参照が残る
- 確認事項: locale fileには翻訳文字列とHTML・URL生成が混在している。次は単純置換ではなく、
  翻訳層からHTMLを分ける範囲を決めてから進めるのが安全

## 2026-08-30: localeのURL依存を軽量なplaceholder方式へ移行

- 日本語を事実上の主対応言語とするが、既存互換のためi18n機構と英語localeは当面残す方針にした
- 今回は翻訳文字列からHTMLを全面分離せず、URLだけを外部から渡す軽量な施策を採用した
- 記事入力欄の説明文は`:help_url` placeholderを使い、PostModelがAdminUrlGeneratorで生成・
  HTML attribute escapeしたURLを渡すようにした
- HelpContentServiceはdashboard、記事作成、Markdown helpの完成URLをhelp viewへ渡し、
  日本語・英語viewは受け取ったURLをescapeして利用する
- locale fileからBASEPATHの直接参照を除去した
- PostModelのfield定義はparent constructor内で作られるため、generatorをparent呼び出し前に
  初期化する順序をDocker DBテストで検出・修正した
- ホストPHPUnitは126 tests・300 assertions（15 skipped）、Docker PHPUnitは
  126 tests・339 assertionsが成功した。変更したservice・controller・DIのPHPStan level 6と
  対象コードのPSR-12も成功した
- 記事編集とhelpへのnavigationを含むclean install E2Eは全16件成功した
- 未完了: PostModel全体には既存のfield配列PHPDoc不足が16件残る。HTML付き翻訳文字列の
  本格分離は、必要性を再評価する将来施策とする
- 次にやるとよいこと: PostModelのfield配列型を動作変更なしで明示した後、Phase 3の
  environment依存残件を再監査する

## 2026-08-30: PostModelのfield配列型を明示

- taxonomy、field処理data、各field factoryの配列key・value型をPHPDocで明示した
- 実行時コードは変更せず、PostModel単体のPHPStan level 6で既存16件をすべて解消した
- ホストPHPUnitは126 tests・300 assertions（15 skipped）、Docker PHPUnitは
  126 tests・339 assertionsが成功した
- 直前と同一実装でE2E成功済みのため、PHPDocだけのこの区切りではE2Eを再実行していない

## 2026-08-30: 認証Refererのhost判定を厳密化

- AuthMiddlewareに直書きされていたHTTP_HOSTの部分一致判定を`RequestOriginService`へ分離した
- RefererをURLとして解析し、request hostと大文字小文字を無視した完全一致の場合だけ
  内部遷移としてloginへredirectする
- `example.test.evil.test`やURL path内に正規hostを含む外部Refererを拒否する
- URIにhostがない場合だけHost headerを安全に解析するfallbackを残した
- 同一host、case差、port差、悪意あるsuffix、path埋め込み、欠落・相対Refererを単体テストで固定した
- ホストPHPUnitは134 tests・308 assertions（15 skipped）、Docker PHPUnitは
  134 tests・347 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 外部未認証404と内部login redirectを含むclean install E2Eは全16件成功した
- 未完了: same-originではなくsame-host判定であり、scheme・port差は従来互換のため許容している
- 次にやるとよいこと: reverse proxy運用情報を確認できるまではorigin判定を厳格化しすぎず、
  TIMEZONE依存の集約またはPhase 3完了条件の再評価へ進む

## 2026-08-30: ApplicationClockを導入

- application timezoneの検証、現在時刻、local時刻parse、localからUTC、UTCからlocalへの変換を
  `ApplicationClock`へ集約した
- 不正なtimezone名はDateTimeZone生成時に早期拒否する
- PostModelの記事slug日付と予約日時初期値、TableRendererの予約・期限判定へ適用した
- DIから同じclockを注入し、従来constructorを直接利用する場合はTIMEZONEから生成するfallbackを残した
- UTC固定時刻を使い、Asia/Tokyoでの現在時刻とUTC往復を単体テストで固定した
- 一覧statusがapplication timezoneに基づいてreserved・expiredを判定することを固定した
- ホストPHPUnitは138 tests・314 assertions（15 skipped）、Docker PHPUnitは
  138 tests・353 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 予約・期限一覧を含むclean install E2Eは全16件成功した
- 未完了: CRUDTraitのDB保存・取得時変換はTIMEZONEを直接参照している
- 次にやるとよいこと: CRUDTraitを利用する全Modelへclockを安全に渡す共通境界を設計し、
  local入力→UTC保存→local表示のcharacterization testを先に追加する

## 2026-08-30: CRUDのDB日時変換へApplicationClockを適用

- BaseModelがApplicationClockを保持し、User・File・PostなどCRUDTrait利用Modelへ共通提供するようにした
- 既存のBaseModel constructor呼び出しは第3引数省略時にTIMEZONEからclockを作るため互換維持した
- CRUDTraitの`save_as_utc` fieldをlocal入力からUTC保存、UTC値からlocal取得へ変換する処理を
  ApplicationClockへ移した
- Asia/Tokyoの`12:34`がSQLiteへUTC`03:34`で保存され、取得時に`12:34`へ戻るDBテストを追加した
- metadata能力判定を`method_exists`からLegacyMetadataModelInterfaceへ変更し、interfaceへ
  `getAllMetaData()`能力を明示した
- CRUDTraitとBaseModelのdata配列型を`array<string, mixed>`として明示した
- ホストPHPUnitは139 tests・314 assertions（16 skipped）、Docker PHPUnitは
  139 tests・356 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 記事保存、予約・期限一覧を含むclean install E2Eは全16件成功した
- 未完了: Published・Expired・SoftDelete traitはUTC現在時刻を直接取得している
- 次にやるとよいこと: UTC DB比較用の現在時刻もApplicationClockへ集約し、固定時刻でquery条件を
  characterization testできるようにする

## 2026-08-30: queryとsoft deleteの現在時刻をApplicationClockへ集約

- ApplicationClockへUTC現在時刻を返す`nowUtc()`を追加した
- Published・Expired traitのDB比較時刻をUTC clock経由へ移した
- SoftDelete traitはapplication localの現在時刻をCRUD共通変換へ渡すようにした
- 従来はUTC文字列をlocal入力として再解釈していたため、UTC以外のtimezoneでは削除時刻が
  二重変換されていた。Asia/Tokyo固定時刻のDBテストで正しいUTC保存を固定した
- ホストPHPUnitは139 tests・315 assertions（16 skipped）、Docker PHPUnitは
  139 tests・359 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- trash・restore、予約・期限一覧を含むclean install E2Eは全16件成功した
- 未完了: TIMEZONE参照はDI composition rootと後方互換fallbackに限定されたか再監査が必要
- 次にやるとよいこと: environment参照全体を再監査し、Phase 3として残すべきcomposition rootと
  除去すべき実行時依存を分類する

## 2026-08-30: presentation設定をview attributeへ集約

- DependenciesのPhpRenderer生成時にlang、view URL、favicon、copyright、home URLを共通attribute化した
- BaseControllerとDashboardControllerはproductionでは共通attributeを引き継ぎ、直接constructorを
  利用する従来経路だけenvironment fallbackを使う
- layout、simple layout、sidebar、login、preview viewから`env()`直接参照をすべて除去した
- home URL、copyright、faviconなどを表示時にescapeする境界を明確にした
- PreviewTraitはmain rendererのlang・copyrightをsite preview rendererへ明示的に渡す
- ホストPHPUnitは139 tests・315 assertions（16 skipped）、Docker PHPUnitは
  139 tests・359 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- login、sidebar、previewを含むclean install E2Eは全16件成功した
- 残るenvironment参照の分類:
  - composition root: ApplicationFactory、Dependencies、RuntimeInitializer
  - 後方互換fallback: BaseModel、FormService、RoutesService、TableRenderer、各Controller
  - 設定由来model構成: PostModelのfield表示切替
  - 未分離の実行時依存: AdminControllerのtheme・favicon、FileControllerのupload path fallback
- 次にやるとよいこと: AdminControllerのasset設定をDIへ移し、PROJECT_PATHを使ったfavicon解決が
  portability上正しいか確認する。FileControllerは既にUploadPathMapper注入経路が主経路なので、
  fallbackだけを互換性として残せる

## 2026-08-30: 管理asset設定をAdminAssetConfigへ分離

- 管理CSSのtheme色・背景色と解決済みfavicon pathを`AdminAssetConfig`へ集約した
- Dependenciesが従来と同じPROJECT_PATH配下のfavicon pathを組み立て、AdminControllerへDIする
- AdminControllerのproduction経路からenvironment参照を除去し、直接constructor利用時のfallbackを残した
- faviconが欠落・読取不能の場合はPHP warningやfalseをresponseへ渡さず、明示的な例外にした
- theme値とfavicon bytes、欠落fileの例外を単体テストで固定した
- AdminControllerのSlim App generic型を明示した
- ホストPHPUnitは141 tests・320 assertions（16 skipped）、Docker PHPUnitは
  141 tests・364 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- 管理CSS・favicon routeを含むclean install E2Eは全16件成功した
- 未完了: FileControllerのupload path生成はDI経路では未使用だが、fallback method内にenv参照が残る
- 次にやるとよいこと: fallbackが実際に必要な互換経路かテストで確認し、残す場合は小さなconfigへ
  閉じ込める。あわせてPhase 3の完了判定を行う

## 2026-08-30: upload path設定を分離しPhase 3を完了候補化

- BASEURL、upload URL、PROJECT_PATH、UPLOADDIRを`UploadPathMapperFactory`へ集約した
- Dependenciesは明示的な設定値からfactoryとmapperを構築し、FileControllerの通常経路から
  environment参照とpath組立を除去した
- 旧constructorを直接呼ぶ互換経路は、引数を末尾へ追加したまま
  `UploadPathMapperFactory::fromEnvironment()`で従来動作を維持した
- URLとfilesystem pathの各設定が末尾slashを含む場合も従来どおり結合できることを単体テストで固定した
- environment参照を再監査し、通常経路はApplicationFactory・Dependencies・RuntimeInitializerの
  composition/初期化境界、残りは旧constructor互換fallbackとPostModelのfield構成に分類できた
- ホストPHPUnitは142 tests・322 assertions（16 skipped）、Docker PHPUnitは
  142 tests・366 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- login、記事CRUD、権限、preview、公開画面、file modalを含むclean install E2Eは全16件成功した
- Phase 3のロードマップ項目は実装・自動検証上の完了条件を満たしたため、ブラウザ確認後に完了とする
- 未完了: ユーザーによる既存のv1 install環境でのブラウザ確認とコードリーディング
- 次にやるとよいこと: ブラウザ確認後、主要な責務境界をコードリーディングし、問題がなければ
  Phase 3を閉じてPhase 4のセキュリティ基盤を読み取り専用で再点検する

## 2026-08-30: 標準のpost・user routingを明示化

- 標準Route登録で行っていたController traitのReflection、trait名からRoutesクラス名への文字列変換、
  存在時だけ登録する暗黙規約を外した
- 記事Route全19件を`Config/PostRoutes.php`、ユーザーRoute全7件を
  `Config/UserRoutes.php`へURL・HTTP method・handlerの形で明示した
- sidebar等が利用する既存route nameと、管理者だけUserRoutesを登録する既存条件を維持した
- 独自Controllerが旧`BaseController::registerRoutes()`へ依存している可能性を考慮し、Reflection経路は
  deprecatedな後方互換入口として残した。標準動作からは利用しない
- 明示RouteのpatternとHTTP method集合をcharacterization testで固定した
- ホストPHPUnitは144 tests・324 assertions（16 skipped）、Docker PHPUnitは
  144 tests・368 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- login、記事全操作、管理者・編集者の権限、公開画面を含むPlaywright E2E全16件が成功した
- 未完了: ユーザーによるRoute定義のコードリーディングと、8083番での任意のブラウザ確認
- 次にやるとよいこと: 明示Route一覧を使い、Phase 4で各Routeの認証・認可条件を監査する

## 2026-08-30: account所有権とsessionを強化

- Phase 4の最初の読み取り専用監査で、AccountControllerのGETは本人IDへ固定する一方、POSTは
  URLのIDをそのまま使い、editorが別userを更新できる経路を確認した
- account GET・POSTの対象をsession内の本人IDへ固定し、不正または欠落したIDは403とした
- Authはpassword検証後、認証情報の保存前にAura Session IDを再生成し、失敗時は認証しない
- sessionへuser row全体を保存せず、id・username・roleだけを保存するようにした
- `SecureSessionFactory`を追加し、strict mode・cookie-only mode、HttpOnly・SameSite=Laxを
  session生成境界で適用した。Secure属性はHTTPS/reverse proxy設計まで保留した
- 公開Frontendは同一requestでBootstrapを複数回初期化するため、開始済みsessionには設定を
  再適用せず、Fatalを起こさない互換経路を追加した
- ホストPHPUnitは147 tests・337 assertions（16 skipped）、Docker PHPUnitは
  147 tests・381 assertionsが成功し、対象コードのPSR-12とPHPStan level 6も成功した
- E2Eはeditorがform actionをadmin IDへ改変してPOSTしても本人だけが更新され、admin資格情報が
  維持されること、browser cookieがHttpOnly・SameSite=Laxであることを固定した
- login、権限、記事、file、公開Frontendを含むPlaywright E2E全16件が成功した
- 8083/8087番は既存データを保持したまま修正済みコミットへ再起動しhealthyを確認した
- 未完了: login CSRF、POST logout、Secure cookie、専用role middleware、session role再検証
- 次にやるとよいこと: login CSRFとPOST logoutを一単位で実装し、続いてUserRoutesへ
  admin認可middlewareを適用する

## 2026-08-30: login・logoutをCSRF保護

- login formへCSRF tokenを追加し、token欠落・不正時は認証処理へ進まないようにした
- logoutの状態変更をPOST＋CSRFへ限定し、既存のGET `/logout`は確認画面として維持した
- 管理画面ナビゲーションのlogoutをPOST formへ変更し、キーボード操作可能なbuttonにした
- session ID再生成後も最新tokenを描画時に取得し、長寿命controllerの古いtokenを使わないようにした
- 共通BaseControllerを継承しないDashboardにも最新tokenを明示的に渡した
- ホストPHPUnitは147 tests・337 assertions（16 skipped）、対象コードのPSR-12と
  PHPStan level 6が成功した
- CSRFなしのlogin・logout拒否、通常login・logout、記事・file・公開Frontendを含む
  clean install Playwright E2E全17件が成功した
- 未完了: production HTTPSでのSecure cookie、専用admin認可middleware、session role再検証
- 次にやるとよいこと: UserRoutesを常時登録し、admin認可をhandler直前のmiddlewareへ移す

## 2026-08-30: ユーザー管理のadmin認可をmiddleware化

- UserRoutesを起動時のsession roleにかかわらず常時登録するようにした
- 全user Routeへ`AdminAuthorizationMiddleware`を適用し、handler直前にadmin roleを検査する
- editorや未認可userには従来どおり404を返し、Routeの存在を権限外へ明示しない
- admin専用の表示metadataをRoute名へ追加し、RoutesServiceがdashboard・sidebar項目をroleで絞るようにした
- RoutesServiceへのAuth依存はDependenciesで明示注入し、省略可能引数の自動解決に依存しないようにした
- middlewareのadmin許可・editor拒否と、admin専用ナビゲーションの表示・非表示を単体テストで固定した
- ホストPHPUnitは151 tests・346 assertions（16 skipped）、対象コードのPSR-12と
  PHPStan level 6が成功した
- editorのリンク非表示・直アクセス404、adminのユーザーCRUDを含むclean install E2E全17件が成功した
- 未完了: sessionに保存したroleの再検証、production HTTPSでのSecure cookie、guest Route判定の明示化
- 次にやるとよいこと: session userを各requestでDBと照合し、削除・role変更を即時反映する

## 2026-08-30: session identityをDBと同期

- AuthMiddlewareを通る保護Routeごとに、session user IDをUserModelで再取得するようにした
- username・roleはDBの現在値でsessionへ上書きし、role変更時はsession IDも再生成する
- DBから削除済みのuser、または不正なsession user IDはsessionを破棄して未認証に戻す
- DB照合で例外が起きた場合は握りつぶさず、古い権限で処理を続けないfail-closedな経路を維持した
- 同期・role変更・削除・不正IDを単体テストで固定した
- ホストPHPUnitは154 tests・362 assertions（16 skipped）、対象コードのPSR-12と
  PHPStan level 6が成功した
- 二つのbrowser sessionでrole昇格・降格・user削除を再現し、navigation・認可・session破棄を含む
  clean install E2E全18件が成功した
- 未完了: production HTTPSでのSecure cookie、AuthMiddlewareのguest Route判定明示化
- 次にやるとよいこと: reverse proxyを含むHTTPS判定を設計し、production cookieへSecureを付ける

## 2026-08-30: session cookieのSecure設定を明示化

- `SessionCookieConfig`を追加し、`SESSION_COOKIE_SECURE`の明示値を最優先するようにした
- 設定がない既存サイトは`BASEURL`のschemeがHTTPSならSecureを有効にし、後方互換性を保った
- reverse proxyの`X-Forwarded-Proto`等は偽装境界が曖昧なため自動信用せず、明示設定で対応する
- 曖昧な値は安全でないfalseへ倒さず例外にし、`true`・`false`・`1`・`0`だけを受理する
- installerはbase URLから判断した`SESSION_COOKIE_SECURE=true|false`を新規`.env`へ出力する
- HTTP・HTTPS・proxy明示設定・不正値と、実際のAura cookie paramsを単体テストで固定した
- ホストPHPUnitは162 tests・371 assertions（16 skipped）、対象コードのPSR-12と
  PHPStan level 6が成功した
- HTTP clean installでcookieのSecure=falseと全管理・公開機能を含むE2E全18件が成功した
- 未完了: AuthMiddlewareのguest Route判定をbasename比較から明示Route metadataへ変更する
- 次にやるとよいこと: login・favicon等のguest可否をroute単位で明示し、同名末尾pathの誤除外を防ぐ

## 2026-08-30: guest許可をRoute identifierで明示

- `GuestRouteRegistry`を追加し、Route登録時にlogin GET・POSTとfaviconだけをguest許可した
- AuthMiddlewareのURL basename比較を削除し、routing済みのSlim Route identifierで判定するようにした
- `/nested/login`のように末尾が同じ別Routeはguest扱いされず、未認証外部アクセスへ404を返す
- routing情報をAuthMiddlewareより先に確定するためmiddleware順を整理し、ErrorMiddlewareを最外周に保った
- custom Routeもregistryへ明示登録すればguest公開でき、未登録Routeはdefault denyとなる
- registryのidentifier一致とAuthMiddlewareの同名末尾拒否を単体テストで固定した
- ホストPHPUnitは165 tests・378 assertions（16 skipped）、対象コードのPSR-12と
  PHPStan level 6が成功した
- login・favicon・未認証404・管理・公開機能を含むPlaywright E2E全18件が成功した
- 未完了: Phase 4セキュリティ基盤の完了判定と、CSPのinline依存・外部CDN依存の監査
- 次にやるとよいこと: 現行SecurityHeadersMiddlewareとtemplateを監査し、nonce/hashまたは
  self-hosted assetへ段階的に移せるCSP改善計画を作る

## 2026-08-30: CSPとsecurity headerの初期監査・安全な強化

- templateを監査し、inline script・inline style・event handler属性が現状ないことを確認した
- 強制CSPへ`object-src 'none'`、`base-uri 'self'`、`form-action 'self'`、
  `frame-ancestors 'self'`を追加した
- `Referrer-Policy`を`strict-origin-when-cross-origin`へ更新し、same-originの内部遷移判定を維持した
- Host headerから生成して全responseへ付けていた不要な`Access-Control-Allow-Origin`を削除した
- HSTSはHTTPへ無条件送信せず、外部HTTPSを示すSessionCookieConfigがtrueのときだけ送信する
- CSP・CORS非付与・HTTP/HSTS非付与・HTTPS/HSTS付与を単体テストとbrowser responseで固定した
- ホストPHPUnitは167 tests・386 assertions（16 skipped）、対象コードのPSR-12と
  PHPStan level 6が成功した
- 管理・公開・file modal・previewを含むclean install E2E全18件が成功した
- 監査で残った課題: main layoutはBootstrap 5.3.3・AdminLTE 4 RC、simple layoutは
  Bootstrap 5.3.0・AdminLTE 3.2で、jQuery・Font Awesomeを含め外部CDNへ依存している
- previewのpackage fallback templateにはCSP未許可の外部style URLがあるが、通常はサイト所有templateへ
  差し替える境界である。fallbackの責務と見た目を別途整理する
- 未完了: 外部assetのversion統一・self-host化、CSP source縮小、SRIまたは供給経路の固定
- 次にやるとよいこと: AdminLTE 3/4のどちらを基準にするか決め、UI回帰を伴うasset統一を別作業にする

## 2026-08-30: 管理画面をAdminLTE 4.0.0正式版へ統一

- main layoutのAdminLTE 4.0.0-rc4を4.0.0正式版へ更新した
- login・logout用simple layoutをAdminLTE 3.2から4.0.0へ更新し、Bootstrapと
  Font Awesomeもmain layoutと同じversionへ揃えた
- Bootstrap 5で廃止された`input-group-append`と`btn-block`を現行markupへ置き換え、
  装飾iconへ`aria-hidden`を付けた
- simple layoutはJavaScriptを使わないため、jQuery・Bootstrap JS・AdminLTE JSを読み込まない
  構成にした。管理画面本体の既存jQuery依存は今回維持した
- PHPUnit 167 tests・386 assertions（16 skipped）、変更4ファイルのPHPCS、clean installからの
  Playwright E2E全18件が成功した
- 未完了: 管理画面本体の自作JavaScriptが持つjQuery依存の棚卸し、assetのself-host化、
  CSPの外部source縮小、preview fallback templateのasset境界整理
- 次にやるとよいこと: 自作JavaScriptごとにjQuery利用箇所と置換難度を読み取り専用で分類し、
  小さな単位からvanilla JavaScriptへ移行できる計画を作る

## 2026-08-30: 自作JavaScriptのjQuery依存を棚卸し

- `src/views/js/*.js.php` 7ファイル・1,184行を調査し、`$(`・`$.`等のjQuery参照を
  合計108箇所確認した
- `kontiki-file-utils`はjQuery非依存で、`kontiki-file-lightbox`もイベント委譲2箇所以外は
  vanilla JavaScriptになっている。ここを最初の移行単位とする
- `kontiki-file-csrf`はAjax 1件とtoken反映だけで独立性が高く、`fetch()`へ移しやすい
- `kontiki-file`は動的button生成と各managerの起動処理、`kontiki-admin`はsidebar・details・
  公開状態表示を担うため、中程度のUI回帰リスクがある
- `kontiki-file-uploader`と`kontiki-file-index`はAjax、動的HTML、event delegation、modalの
  focus制御が集中する。ファイル管理の安定性とアクセシビリティに直結するため最後に移行する
- 推奨順序は lightbox、CSRF helper、file起動処理、admin一般処理、uploader、file index とする
- 未完了: JavaScript単体テスト基盤、通信失敗・JSON不正・連打時のE2E、jQuery CDN削除
- 次にやるとよいこと: lightboxの2つのdelegated click handlerをnative event delegationへ置換し、
  既存のkeyboard・focus E2Eを回して最初の小さな移行単位を完了する

## 2026-08-30: lightboxをjQuery非依存化

- lightbox本体の背景・閉じるbuttonと、動的file一覧のpreview triggerに対するclick delegationを
  native `addEventListener()`・`closest()`へ置き換えた
- Escape、focus trap、`inert`、代替テキスト、triggerへのfocus復帰処理は変更していない
- file modal E2Eへ画像previewの表示・alt・Escape終了・focus復帰を追加し、全3件が成功した
- 未完了: 残るjQuery参照106箇所。次の独立した移行候補は`kontiki-file-csrf`
- 次にやるとよいこと: CSRF token取得を`fetch()`へ置換し、HTTP失敗と不正responseでは
  tokenを書き換えないfail-closedな実装にする

## 2026-08-30: file CSRF helperをjQuery非依存化

- `kontiki-file-csrf`の`$.ajax()`をsame-origin credentials付きの`fetch()`へ置き換えた
- HTTP失敗、JSON parse失敗、空または欠落tokenを例外として扱い、検証完了前にはDOM上の
  既存tokenを書き換えないようにした
- token反映はnative DOM APIで行い、このhelperのjQuery依存4箇所を除去した
- file modal E2Eは正常なupload・update・deleteを含む3件に加え、不正JSONで既存tokenを
  保持してエラー通知する失敗系を追加し、全4件が成功した
- 未完了: 残るjQuery参照102箇所。次の候補は`kontiki-file`の起動・button生成処理
- 次にやるとよいこと: `kontiki-file`をnative DOMへ移し、動的buttonの挿入順と
  target field切り替えをE2Eで固定する

## 2026-08-30: file manager起動処理をjQuery非依存化

- `kontiki-file`のDOMContentLoaded、field走査、button生成、modal triggerのevent delegationを
  native DOM APIへ置き換え、jQuery依存9箇所を除去した
- button HTMLの文字列連結をやめ、属性とlabelをDOM APIで設定した。翻訳labelは`json_encode()`で
  JavaScript文字列として安全に出力するようにした
- textarea直後の画像挿入button、その次のfile管理buttonという既存順序と、対象field IDの
  managerへの引き渡しを維持した
- file modal E2E全4件と、button生成順の重点再検査1件が成功した
- 未完了: 残るjQuery参照93箇所。`kontiki-admin`は29箇所、uploaderは38箇所、indexは26箇所
- 次にやるとよいこと: 中程度のUI範囲を持つ`kontiki-admin`を機能群ごとに分け、まずskip link・
  create button移動・sidebar current itemなど同期DOM処理からnative化する

## 2026-08-30: 管理画面共通処理をjQuery非依存化

- `kontiki-admin`の初期化、skip link、sidebar ARIA同期、create button移動、current menu、flash表示、
  `details`開閉とfocus、記事公開状態表示をnative DOM APIへ置き換えた
- AdminLTEによるbody class変更の監視、Spaceでのsidebar操作、日時・statusに応じたbutton文言と
  公開URL要素の切り替えは維持した
- jQuery依存29箇所を除去し、残る参照はfile uploader 38箇所とfile index 26箇所の計64箇所になった
- sidebarのARIA、公開日時detailsの開閉とfocus、pending・draftのbutton文言同期をE2Eへ追加した
- clean installへ未コミットadmin fileだけを反映し、管理・公開・preview・file modalを含む
  Playwright E2E全20件が成功した
- 未完了: uploaderとfile indexのnative化、管理layoutからのjQuery CDN削除
- 次にやるとよいこと: uploaderをDOM操作・modal lifecycle・upload通信の順で分離し、
  `fetch()`のresponse検証と二重submit防止を含めて段階的に置き換える

## 2026-08-30: file uploaderをjQuery非依存化

- file inputとbutton状態、upload成功・失敗表示、insert画面遷移、validation ARIA、modal resetと
  focus制御をnative DOM APIへ置き換えた
- uploadのmultipart POSTとdescription更新のform-urlencoded POSTをsame-origin credentials付き
  `fetch()`へ移し、HTTP失敗とJSON parse失敗を成功扱いしない共通処理にした
- upload・description更新の処理中フラグを追加し、連打やEnterによる二重submitを拒否するようにした
- upload・insert・deleteを含むfile modal正常系4件が成功し、遅延した不正JSON responseに対する
  二重submitでもrequestが1回だけで成功画面へ進まない失敗系E2Eも成功した
- uploaderのjQuery依存38箇所を除去し、残りはfile indexの26箇所だけになった
- 未完了: file indexのnative化、全機能のclean install再検証、管理layoutからのjQuery CDN削除
- 次にやるとよいこと: file indexをevent delegation、一覧取得、更新・削除通信、modal focusの順で
  native化し、完了後にjQuery CDNを削除して全E2Eを実行する

## 2026-08-30: file indexをnative化しjQuery依存を解消

- file indexのpagination、URL copy、説明form表示、Markdown挿入をnative event delegationと
  DOM APIへ置き換えた
- 一覧HTML取得をsame-origin `fetch()`へ、説明更新・削除をJSON検証付き`fetch()`へ移した
- 動的再描画後の操作、validation ARIA、modalのfocus移動・復帰、lightbox連携を維持した
- file indexのjQuery参照26箇所を除去し、自作JavaScript全体のjQuery参照をゼロにした
- 管理layoutからjQuery CDNを削除し、CSPの`code.jquery.com`許可も削除した
- clean installへ未コミット差分を反映し、管理・公開・preview・upload・insert・deleteを含む
  Playwright E2E全21件が成功した。CSP縮小後の認証E2E 3件も成功した
- PHPUnitは167 tests・387 assertions（16 skipped）が成功した
- 未完了: Bootstrap・AdminLTE・Font Awesomeのself-host化、JavaScript文字列の翻訳値出力統一、
  file indexの通信失敗・二重削除を直接固定する追加E2E
- 次にやるとよいこと: ユーザーのブラウザ確認後、CDN assetのself-host化とSRIのどちらを採るかを
  設計し、CSP sourceをさらに縮小する

## 2026-08-30: file modalのエラー表示と画像focus ringを修正

- upload失敗時、client側の赤いalert container内へserver側の装飾済みwarning HTMLを入れていたため、
  警告枠が二重になっていた
- serverが装飾済みmessageを返した場合は外側へalert classを付けず、通信失敗・不正JSONなど
  messageを得られない場合だけclient側のdanger alertを使うようにした
- 画像preview linkと内側imgの両方へ`img-thumbnail`が付いていたため、inline linkの断片的な
  focus outlineが描かれていた。linkを専用`file-preview-link`へ変更し、`inline-block`と矩形の
  `:focus-visible` outlineを定義した
- E2Eで装飾済みwarningが外側のdanger classを持たないこと、preview linkが旧thumbnail classを
  持たず、keyboard focus時にinline-block・solid outlineとなることを固定した
- clean E2E環境でfile modal全6件、PHPUnit 167 tests・387 assertions（16 skipped）が成功した
- 未完了: ユーザーによる実ブラウザでの表示確認
- 次にやるとよいこと: 8083番のupload失敗表示と画像linkのTab focusを確認する

## 2026-08-30: file uploadの失敗理由とbutton間隔を改善

- textarea直後の「画像挿入」と「画像/ファイル管理」が密着しないよう、画像挿入buttonへ
  Bootstrapの`me-2`を付けた。既存DOM順序とselectorは維持した
- PSR-7のupload errorを一律の「fileなし」へ変換せず、容量超過・部分upload・未選択・
  server内部失敗をControllerで区別するようにした
- FileServiceの不許可MIMEとKontiki側容量超過を安定したerror codeで返し、Controllerで
  許可形式（JPEG・PNG・PDF）と設定上限を含む利用者向け日本語messageへ変換した
- MIME判定は引き続き一時fileの実内容を基準とし、client申告の拡張子・MIMEを信用しない
- PHPUnit 170件・390 assertions、PSR-12検査、file modal E2E 8件、全E2E 24件が成功した
- 未完了: PHP自身の上限を超えたuploadではclient fileの実sizeをserverが保持しないため、
  現時点の表示は「serverのupload上限超過」まで。数値表示はKontiki側5 MB制限時に行う
- 次にやるとよいこと: 8083番でbutton間隔、不許可形式、容量超過messageを実ブラウザ確認する
