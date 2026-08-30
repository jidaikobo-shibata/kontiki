# Kontiki 管理画面の権限方針

## 現行role

- `admin`（管理者）はユーザー管理を含むすべての管理機能を利用できる
- `editor`（編集者）はユーザー管理を除く記事管理機能を利用できる
- `editor` は記事の作成者を問わず、すべての記事を編集できる

この方針は、編集者を管理者に次ぐ運用担当者とするための意図した仕様である。
記事所有者単位の制限は、必要になった時点で別roleとして検討する。既存の
`editor` の権限を後から暗黙に狭めない。

## Route別の期待権限

| Route群 | 未ログイン | editor | admin |
| --- | --- | --- | --- |
| login | 許可 | 許可（dashboardへ移動） | 許可（dashboardへ移動） |
| dashboard・help | 拒否 | 許可 | 許可 |
| post・preview | 拒否 | 全記事を操作可能 | 全記事を操作可能 |
| file | 拒否 | 許可 | 許可 |
| account | 拒否 | 本人のみ | 本人のみ |
| user | 拒否 | 拒否 | 許可 |

## 2026-08-30 認証・認可監査

対応済み:

- account POSTはURLのuser IDを信用せず、常にsession内の本人IDへ固定する
- ログイン成功時は認証情報を保存する前にsession IDを再生成する
- sessionへ保存するuser情報はid・username・roleだけとし、password hashを保存しない
- session cookieへHttpOnly・SameSite=Laxを付け、strict modeとcookie-only modeを有効にする
- 公開側で1リクエスト中に複数回初期化されても、開始済みsessionへcookie設定を再適用しない
- login formをCSRF保護する
- logoutはPOST＋CSRFを状態変更の入口とし、GETは互換性のある確認画面にする

未完了:

- production HTTPSでのSecure cookie（reverse proxy条件を含めて設計する）
- UserRoutesを常時登録し、admin認可を専用middlewareでhandler直前に検査する
- sessionに保存したroleがuser変更・削除後に古くならない仕組み
- AuthMiddlewareのguest route判定をbasename比較から明示route属性へ変更する
