<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
/** @var Uploader $uploader */
$username = $auth->requireLogin();

$error = null;
$title = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $title = trim((string) ($_POST['title'] ?? ''));

    // An upload larger than post_max_size arrives with $_POST and $_FILES both
    // empty — PHP discards the body before the script runs — so the generic
    // "choose a file" message would be actively misleading here.
    $postMax = ini_get('post_max_size');
    if ($_FILES === [] && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $error = 'That upload exceeded the server limit of ' . $postMax . '.';
    } else {
        try {
            $record = $uploader->store($_FILES['file'] ?? [], $username, $title);
            flash('success', '“' . $record['title'] . '” uploaded.');
            redirect('browse.php');
        } catch (UploadException $e) {
            $error = $e->getMessage();
        }
    }
}

$accept = implode(',', array_map(
    static fn (string $ext) => '.' . $ext,
    FileTypes::allowedExtensions()
));

$pageTitle = 'Upload';
require __DIR__ . '/partials/header.php';
?>
<main class="page-main">
    <div class="upload-card">
        <h1 class="upload-title">Upload a file</h1>
        <p class="upload-sub">
            Any document, image, archive, audio or video file up to
            <?= e(FileTypes::formatSize($uploader->maxBytes())) ?>.
        </p>

        <?php if ($error !== null): ?>
            <div class="form-error"><i class="exclamation circle icon"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form class="ui form" method="post" enctype="multipart/form-data" id="uploadForm">
            <?= Csrf::field() ?>

            <div class="field">
                <label for="title">Title <span class="field-optional">(optional)</span></label>
                <input type="text" id="title" name="title" value="<?= e($title) ?>"
                       maxlength="180" placeholder="Defaults to the filename">
            </div>

            <div class="field">
                <label for="fileInput">File</label>
                <div class="file-drop" id="fileDrop">
                    <input type="file" id="fileInput" name="file" required
                           accept="<?= e($accept) ?>"
                           data-max-bytes="<?= e((string) $uploader->maxBytes()) ?>">
                    <div class="file-drop-icon"><i class="cloud upload icon"></i></div>
                    <div class="file-drop-text">Click to choose a file, or drag &amp; drop it here</div>
                    <div class="file-drop-hint" id="fileDropHint">
                        Max <?= e(FileTypes::formatSize($uploader->maxBytes())) ?>
                    </div>
                </div>
            </div>

            <!-- Populated by upload.js once a file is chosen. -->
            <div class="upload-preview" id="uploadPreview" hidden>
                <img id="previewImage" alt="" hidden>
                <div class="upload-preview-meta">
                    <div class="upload-preview-name" id="previewName"></div>
                    <div class="upload-preview-size" id="previewSize"></div>
                </div>
            </div>

            <button class="ui primary button upload-submit" type="submit" id="uploadSubmit">
                Upload
            </button>
        </form>
    </div>
</main>
<?php
$pageScripts = ['upload.js'];
require __DIR__ . '/partials/footer.php';
