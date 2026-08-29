<div class="container my-4">

    <h2 class="fs-2 fw-bold mt-5 mb-4">What you can do</h2>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">Create an article</h3>

    <p>You can create a new article from "<a href="<?= env('BASEPATH', '') ?>/post/create">Create an article</a>" in the <a href="<?= env('BASEPATH', '') ?>/dashboard">Management item list</a>.</p>

    <p>In an article, you can operate the following items. </p>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Items</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Title</th>
                    <td>The name of the page. It will be displayed in the article list and as a candidate in search engines such as Google. </td>
                </tr>
                <tr>
                    <th>Contents</th>
                    <td>The contents of the page. It is written using the notation called <a href="<?= env('BASEPATH', '') ?>/help/markdown" target="_blank">Markdown notation</a>. There is a button called "<code><?= __('file_upload_manage') ?></code>" near the "Content" input field. <br>If you want to post an image or PDF on the page, press this button. You can upload the file.</td>
                </tr>
                <tr>
                    <th>Article ID</th>
                    <td>This will be part of the page's URL (address). The initial value is <code>post-date</code>, but you can change it.</td>
                </tr>
                <tr>
                    <th class="text-nowrap">Status</th>
                    <td>Set the status of the article. Posts that are in the process of being edited are set to "Draft", those that can be published are set to "Published", and those that cannot be published for some reason are set to "Private".</td>
                </tr>
                <tr>
                    <th class="text-nowrap">Set publishing end date and time</th>
                    <td>You can specify the date and time when publishing will end. When the deadline arrives, the article will only be viewable in the administration screen. </td>
                </tr>
                <tr>
                    <th class="text-nowrap">Scheduled publication date and time</th>
                    <td>You can specify the date and time when publication will start. </td>
                </tr>
                <tr>
                    <th class="text-nowrap">Preview</th>
                    <td>You can check how the article you are editing will be published. The preview screen cannot be reloaded from the editing screen, but you can close the preview screen and preview it again. </td>
                </tr>
            </tbody>
        </table>
    </div>
    <h3 class="fs-5 fw-semibold mt-4 mb-2">Edit article</h3>
    <p>You can display a link to the editing screen of the article on the published article. This link will be hidden if you are logged out of the system. </p>

    <h2 class="fs-2 fw-bold mt-5 mb-4">User permissions</h2>

    <p>There are two types of users who can operate this system: "Administrator" and "Editor". If you only want to create or edit articles, it is recommended that you log in as an "Editor". </p>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">What an "Editor" can do</h3>
    <ul class="mb-4">
        <li>Add, edit, and delete posts</li>
        <li>Change your own password and username</li>
    </ul>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">What an "Administrator" can do</h3>
    <ul>
        <li>What an Editor can do</li>
        <li>Change passwords and usernames of users other than yourself</li>
        <li>Add and delete users</li>
    </ul>

    <h3 class="fs-5 fw-semibold mt-4 mb-2">About Administrators</h3>
    <ul>
        <li>Administrators cannot be deleted in user management</li>
        <li>Administrators cannot be removed from the system in user management</li>
    </ul>
</div>
