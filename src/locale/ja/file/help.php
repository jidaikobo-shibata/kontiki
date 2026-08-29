<div class="container my-4">
    <h2 class="fs-2 fw-bold mt-5 mb-4">できること</h2>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">記事の作成</h3>

    <p>
        <a href="<?= env('BASEPATH', '') ?>/dashboard">管理項目一覧</a> の
        「<a href="<?= env('BASEPATH', '') ?>/post/create">記事の作成</a>」から、新しい記事を作成できます。
    </p>

    <p>記事では、以下のような項目を操作できます。</p>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>項目</th>
                    <th>説明</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>タイトル</th>
                    <td>ページの名前です。記事の一覧や Google などの検索エンジンの候補として表示されます。</td>
                </tr>
                <tr>
                    <th>内容</th>
                    <td>ページの内容です。<a href="<?= env('BASEPATH', '') ?>/help/markdown" target="_blank">マークダウン記法</a>という記法を用いて記述します。「内容」の入力欄の付近に「<code><?= __('file_upload_manage') ?></code>」というボタンがあります。<br>ページに画像や PDF などを掲載したい場合は、このボタンを押してください。ファイルのアップロードができます。</td>
                </tr>
                <tr>
                    <th>記事ID</th>
                    <td>ページの URL（アドレス）の一部になります。初期値として<code>post-日付</code> というような値が入っていますが、変更しても良いです。</td>
                </tr>
                <tr>
                    <th class="text-nowrap">ステータス</th>
                    <td>記事の状態を設定します。編集途中の記事を「下書き」、公開していいものを「公開」、なんらかの理由で公開しないものを「非公開」に設定します。</td>
                </tr>
                <tr>
                    <th class="text-nowrap">公開終了日時設定</th>
                    <td>公開を終了する日時を指定できます。期日が来ると記事は管理画面でしか見られなくなります。</td>
                </tr>
                <tr>
                    <th class="text-nowrap">公開予約日時</th>
                    <td>公開を開始する日時を指定できます。</td>
                </tr>
                <tr>
                    <th class="text-nowrap">プレビュー</th>
                    <td>編集途中の記事が、どのように公開されるかを確認できます。編集画面からのプレビュー画面は再読込できませんが、プレビュー画面を閉じて、再度、プレビューすることができます。</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">記事の編集</h3>

    <p>公開されている記事に、その記事の編集画面へのリンクを表示することができます。このリンクはシステムからログアウトしていると非表示になります。</p>

    <h2 class="fs-2 fw-bold mt-5 mb-4">ユーザの権限について</h2>

    <p>このシステムを操作できるユーザには「管理者」と「編集者」の2種類があります。記事の作成や修正をするだけでしたら、「編集者」でログインするとよいでしょう。</p>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">「編集者」ができること</h3>
    <ul class="mb-4">
        <li>記事の追加、編集、削除</li>
        <li>自分のパスワードやユーザ名の変更</li>
    </ul>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">「管理者」ができること</h3>
    <ul>
        <li>編集者ができること</li>
        <li>自分以外のユーザのパスワードやユーザ名の変更</li>
        <li>ユーザの追加や削除</li>
    </ul>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">管理者について</h3>
    <ul>
        <li>ユーザの管理で、管理者を削除することはできません</li>
        <li>ユーザの管理で、システムから1人も管理者がいなくなることはできません</li>
    </ul>
</div>
