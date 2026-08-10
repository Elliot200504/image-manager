<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
/** @var Files $files */
$username = $auth->requireLogin();

// ?kind=image narrows to one group; anything unrecognised falls back to "all"
// rather than erroring, so a stale or hand-edited link still renders.
$requestedKind = (string) ($_GET['kind'] ?? '');
$activeKind    = in_array($requestedKind, FileTypes::KINDS, true) ? $requestedKind : null;

$grouped = $files->groupedByKind();
$counts  = $files->countsByKind();
$total   = array_sum($counts);

$visible = $activeKind !== null
    ? array_intersect_key($grouped, [$activeKind => true])
    : $grouped;

/**
 * The list of images in render order, so the lightbox can page through them.
 * Only images go in — the viewer has nothing to show for a zip.
 */
$lightboxItems = [];
foreach ($visible as $kindFiles) {
    foreach ($kindFiles as $file) {
        if (($file['kind'] ?? null) === FileTypes::KIND_IMAGE) {
            $lightboxItems[] = [
                'id'     => $file['file_id'] ?? '',
                'src'    => 'download.php?id=' . rawurlencode((string) ($file['file_id'] ?? '')) . '&inline=1',
                'title'  => $file['title'] ?? '',
                'owner'  => $file['owner'] ?? '',
                'size'   => FileTypes::formatSize((int) ($file['size'] ?? 0)),
            ];
        }
    }
}

$pageTitle = 'Files';
require __DIR__ . '/partials/header.php';
?>
<main class="page-main">
    <section class="page-hero">
        <h1>Files</h1>
        <p>
            <?= e((string) $total) ?> file<?= $total === 1 ? '' : 's' ?>
            · <?= e(FileTypes::formatSize($files->totalSize())) ?> stored
        </p>
    </section>

    <?php if ($total === 0): ?>
        <div class="empty-state">
            <i class="cloud upload icon"></i>
            <p>Nothing uploaded yet.</p>
            <a class="ui primary button" href="upload.php">Upload your first file</a>
        </div>
    <?php else: ?>
        <nav class="kind-filter">
            <a class="kind-chip<?= $activeKind === null ? ' active' : '' ?>" href="browse.php">
                All <span class="kind-chip-count"><?= e((string) $total) ?></span>
            </a>
            <?php foreach (FileTypes::KINDS as $kind): ?>
                <?php if (!empty($counts[$kind])): ?>
                    <a class="kind-chip<?= $activeKind === $kind ? ' active' : '' ?>"
                       href="browse.php?kind=<?= e($kind) ?>">
                        <i class="<?= e(FileTypes::icon($kind)) ?> icon"></i>
                        <?= e(FileTypes::label($kind)) ?>
                        <span class="kind-chip-count"><?= e((string) $counts[$kind]) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <?php $imageIndex = 0; ?>
        <?php foreach ($visible as $kind => $kindFiles): ?>
            <section class="file-section">
                <div class="section-heading">
                    <h2><i class="<?= e(FileTypes::icon($kind)) ?> icon"></i><?= e(FileTypes::label($kind)) ?></h2>
                    <span class="section-sub"><?= count($kindFiles) ?> item<?= count($kindFiles) === 1 ? '' : 's' ?></span>
                </div>

                <div class="file-grid">
                    <?php foreach ($kindFiles as $file): ?>
                        <?php
                        $fileId    = (string) ($file['file_id'] ?? '');
                        $isImage   = ($file['kind'] ?? null) === FileTypes::KIND_IMAGE;
                        $isOwner   = ($file['owner'] ?? null) === $username;
                        $ratio     = ($isImage && !empty($file['width']) && !empty($file['height']))
                            ? round((float) $file['width'] / (float) $file['height'], 4)
                            : null;
                        $downloadUrl = 'download.php?id=' . rawurlencode($fileId);
                        ?>
                        <article class="file-card<?= $isOwner ? ' is-owner' : '' ?>"
                                 data-file-id="<?= e($fileId) ?>">
                            <div class="file-card-media"<?= $ratio ? ' style="--ratio:' . e((string) $ratio) . '"' : '' ?>>
                                <?php if ($isImage): ?>
                                    <img src="<?= e($downloadUrl . '&inline=1') ?>"
                                         alt="<?= e($file['title'] ?? '') ?>"
                                         class="file-thumb"
                                         loading="lazy"
                                         data-lightbox-index="<?= e((string) $imageIndex) ?>"
                                         <?php if (!empty($file['width'])): ?>
                                             width="<?= e((string) $file['width']) ?>"
                                             height="<?= e((string) $file['height']) ?>"
                                         <?php endif; ?>>
                                    <?php $imageIndex++; ?>
                                <?php else: ?>
                                    <div class="file-card-icon">
                                        <i class="<?= e(FileTypes::icon((string) ($file['kind'] ?? ''))) ?> icon"></i>
                                        <span class="file-card-ext"><?= e(strtoupper((string) ($file['extension'] ?? ''))) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="file-card-body">
                                <h3 class="file-card-title" title="<?= e($file['original_name'] ?? '') ?>">
                                    <?= e($file['title'] ?? '') ?>
                                </h3>
                                <div class="file-card-meta">
                                    <span><?= e(FileTypes::formatSize((int) ($file['size'] ?? 0))) ?></span>
                                    <span>·</span>
                                    <span><?= e((string) ($file['owner'] ?? 'unknown')) ?></span>
                                    <?php if (!empty($file['uploaded_at'])): ?>
                                        <span>·</span>
                                        <span><?= e(time_ago((int) $file['uploaded_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="file-card-actions">
                                <a class="icon-btn" href="<?= e($downloadUrl) ?>" title="Download">
                                    <i class="download icon"></i>
                                </a>
                                <?php if ($isOwner): ?>
                                    <button class="icon-btn js-rename" title="Rename"
                                            data-title="<?= e($file['title'] ?? '') ?>">
                                        <i class="pencil alternate icon"></i>
                                    </button>
                                    <button class="icon-btn icon-btn-danger js-delete" title="Delete"
                                            data-title="<?= e($file['title'] ?? '') ?>">
                                        <i class="trash alternate outline icon"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<script id="lightboxData" type="application/json">
    <?= json_encode($lightboxItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>
<?php
$pageScripts = ['lightbox.js', 'browse.js'];
require __DIR__ . '/partials/footer.php';
