<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
/** @var Users $users */
/** @var Files $files */
/** @var Uploader $uploader */
$username = $auth->requireLogin();

const MAX_DESCRIPTION_LENGTH = 500;

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $description = trim((string) ($_POST['description'] ?? ''));

    if (mb_strlen($description) > MAX_DESCRIPTION_LENGTH) {
        $error = 'That description is too long (max ' . MAX_DESCRIPTION_LENGTH . ' characters).';
    } else {
        $changes = ['description' => $description];

        // An avatar is just another upload: same allowlist, same sniffing, same
        // out-of-root storage. The old code wrote it straight into uploads/
        // with only an extension check.
        $avatarUpload = $_FILES['avatar'] ?? null;
        $hasAvatar    = is_array($avatarUpload)
            && (int) ($avatarUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasAvatar) {
            $extension = strtolower(pathinfo((string) ($avatarUpload['name'] ?? ''), PATHINFO_EXTENSION));

            if (FileTypes::kindForExtension($extension) !== FileTypes::KIND_IMAGE) {
                $error = 'A profile picture must be an image.';
            } else {
                try {
                    $record = $uploader->store($avatarUpload, $username, $username . '’s avatar');
                    $changes['avatar'] = $record['file_id'];
                } catch (UploadException $e) {
                    $error = $e->getMessage();
                }
            }
        }

        if ($error === null) {
            $users->update($username, $changes);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    }
}

$user        = $auth->user() ?? [];
$description = (string) ($user['description'] ?? '');
$avatarId    = (string) ($user['avatar'] ?? '');
$avatarUrl   = $avatarId !== '' ? 'download.php?id=' . rawurlencode($avatarId) . '&inline=1' : null;
$myFiles     = $files->forOwner($username);
$mySize      = array_sum(array_map(static fn (array $f) => (int) ($f['size'] ?? 0), $myFiles));

$pageTitle = 'Profile';
require __DIR__ . '/partials/header.php';
?>
<main class="page-main profile-shell">
    <section class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-header-row">
            <?php if ($avatarUrl !== null): ?>
                <img src="<?= e($avatarUrl) ?>" alt="" class="profile-picture">
            <?php else: ?>
                <div class="profile-picture profile-picture-empty">
                    <?= e(mb_strtoupper(mb_substr($username, 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div class="profile-info">
                <div class="profile-username"><?= e($username) ?></div>
                <div class="profile-description">
                    <?= $description !== '' ? e($description) : '<span class="muted">No description yet.</span>' ?>
                </div>
            </div>
            <div class="profile-stats">
                <span class="count-chip"><?= count($myFiles) ?> file<?= count($myFiles) === 1 ? '' : 's' ?></span>
                <span class="count-chip"><?= e(FileTypes::formatSize($mySize)) ?></span>
            </div>
        </div>
    </section>

    <section class="profile-card profile-card-body">
        <h2 class="profile-section-title"><i class="edit outline icon"></i>Edit profile</h2>

        <?php if ($error !== null): ?>
            <div class="form-error"><i class="exclamation circle icon"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form class="ui form" method="post" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="username_display">Username</label>
                <input type="text" id="username_display" value="<?= e($username) ?>" readonly>
            </div>
            <div class="field">
                <label for="description">About me</label>
                <textarea id="description" name="description" rows="3"
                          maxlength="<?= MAX_DESCRIPTION_LENGTH ?>"
                          placeholder="Tell us about yourself..."><?= e($description) ?></textarea>
            </div>
            <div class="field">
                <label for="avatar">Profile picture</label>
                <input type="file" id="avatar" name="avatar" accept="image/*">
            </div>
            <button class="ui button primary" type="submit">Save changes</button>
        </form>
    </section>

    <section class="profile-card profile-card-body">
        <h2 class="profile-section-title"><i class="folder open outline icon"></i>My files</h2>

        <?php if ($myFiles === []): ?>
            <div class="empty-state">
                <i class="cloud upload icon"></i>
                <p>You haven't uploaded anything yet.</p>
                <a class="ui primary button" href="upload.php">Upload a file</a>
            </div>
        <?php else: ?>
            <p class="reorder-hint"><i class="arrows alternate vertical icon"></i>Drag a row to reorder your files.</p>
            <ul class="file-list" id="myFileList">
                <?php foreach ($myFiles as $index => $file): ?>
                    <?php $fileId = (string) ($file['file_id'] ?? ''); ?>
                    <li class="file-list-row" data-file-id="<?= e($fileId) ?>">
                        <span class="file-list-handle" title="Drag to reorder">
                            <i class="grip lines icon"></i>
                        </span>
                        <span class="file-list-position"><?= $index + 1 ?></span>
                        <span class="file-list-icon">
                            <i class="<?= e(FileTypes::icon((string) ($file['kind'] ?? ''))) ?> icon"></i>
                        </span>
                        <span class="file-list-name">
                            <span class="file-list-title"><?= e($file['title'] ?? '') ?></span>
                            <span class="file-list-sub">
                                <?= e($file['original_name'] ?? '') ?>
                                · <?= e(FileTypes::formatSize((int) ($file['size'] ?? 0))) ?>
                            </span>
                        </span>
                        <span class="file-list-actions">
                            <a class="icon-btn" href="download.php?id=<?= e(rawurlencode($fileId)) ?>" title="Download">
                                <i class="download icon"></i>
                            </a>
                            <button class="icon-btn icon-btn-danger js-delete" title="Delete"
                                    data-title="<?= e($file['title'] ?? '') ?>">
                                <i class="trash alternate outline icon"></i>
                            </button>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
<?php
$pageScripts = ['profile.js'];
require __DIR__ . '/partials/footer.php';
