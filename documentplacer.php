<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (is_array($data) && count($data) > 0) {
        file_put_contents(__DIR__ . '/documents.json', json_encode($data, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }
}

$images = [];
if (file_exists(__DIR__ . '/documents.json')) {
    $images = json_decode(file_get_contents(__DIR__ . '/documents.json'), true) ?? [];
    $images = array_filter($images, function($img) {
        return isset($img['filename']) && $img['filename'] !== '';
    });
    usort($images, function($a, $b) {
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });
}
?>

<?php if (count($images) > 0): ?>
<div class="ui styled fluid accordion" id="imageAccordion">
    <div class="title">
        <i class="dropdown icon"></i>
        <span style="font-size:1.2em;font-weight:bold;">Your Images (<?= count($images) ?>)</span>
    </div>
    <div class="content">
        <div class="ui segment">
            <div class="image-grid" id="imageGrid">
            <?php foreach ($images as $idx => $img): ?>
                <div class="image-item" draggable="true"
                     data-bild-id="<?= htmlspecialchars($img['Bild_ID'] ?? 'Bild_ID_' . ($idx + 1)) ?>"
                     data-order="<?= htmlspecialchars($img['order'] ?? ($idx + 1)) ?>">
                    <div class="order-badge"><?= htmlspecialchars($img['order'] ?? ($idx + 1)) ?></div>
                    <img src="uploads/<?= htmlspecialchars($img['filename']) ?>"
                         alt="<?= htmlspecialchars($img['Bild_ID'] ?? 'Bild_ID_' . ($idx + 1)) ?>"
                         data-gallery-index="<?= $idx ?>">
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#imageAccordion').accordion({ exclusive: false });

    // Collect all image sources for the gallery
    const images = [];
    $('#imageGrid img').each(function() {
        images.push($(this).attr('src'));
    });

    // Open gallery on image click
    $('#imageGrid img').on('click', function() {
        const idx = Number($(this).attr('data-gallery-index'));
        if (window.imageGallery) {
            window.imageGallery.open(images, idx);
        }
    });
});
</script>
<?php else: ?>
<div class="ui warning message">
    <i class="photo icon"></i>
    No images uploaded.
</div>
<?php endif; ?>