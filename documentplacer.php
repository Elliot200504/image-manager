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
        <span style="font-size:1.2em;font-weight:bold;">Bilder/Dokument (<?= count($images) ?>)</span>
    </div>
    <div class="content">
        <div class="ui segment">
            <table class="ui very basic unstackable table image-table">
                <tbody id="imageTableBody">
<?php foreach ($images as $idx => $img): ?>
    <tr class="image-row" draggable="true"
        data-bild-id="<?= htmlspecialchars($img['Bild_ID'] ?? 'Bild_ID_' . ($idx + 1)) ?>"
        data-order="<?= htmlspecialchars($img['order'] ?? ($idx + 1)) ?>">
        <td style="width:36px; vertical-align: top;">
            <div class="order-square"><?= $idx + 1 ?></div>
        </td>
        <td style="width:120px; vertical-align: top;">
            <img src="uploads/<?= htmlspecialchars($img['filename']) ?>"
                 alt="<?= htmlspecialchars($img['Bild_ID'] ?? 'Bild') ?>"
                 class="table-thumb"
                 data-gallery-index="<?= $idx ?>">
        </td>
        <td style="vertical-align: top;">
            <a href="uploads/<?= htmlspecialchars($img['filename']) ?>"
               class="filename-link"
               target="_blank">
                <?= htmlspecialchars($img['filename']) ?>
            </a>
        </td>
        <td class="actions-cell" style="vertical-align: top; text-align: right; position: relative;">
            <div class="actions">
                <a href="uploads/<?= htmlspecialchars($img['filename']) ?>" download title="Ladda ner">
                    <i class="download icon"></i>
                </a>
                <button class="ui icon button rotate-btn" title="Rotera">
                    <i class="undo icon"></i>
                </button>
                <button class="ui icon button edit-btn" title="Redigera">
                    <i class="pencil alternate icon"></i>
                </button>
                <button class="ui icon button delete-btn" title="Ta bort">
                    <i class="trash icon"></i>
                </button>
            </div>
        </td>
    </tr>
    <tr>
    </tr>
<?php endforeach; ?>
</tbody>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#imageAccordion').accordion({ exclusive: false });

    // Gallery trigger
    const images = [];
    $('.table-thumb').each(function() {
        images.push($(this).attr('src'));
    });
    $('.table-thumb').on('click', function() {
        const idx = Number($(this).attr('data-gallery-index'));
        if (window.imageGallery) {
            window.imageGallery.open(images, idx);
        }
    });

    // TODO: Add handlers for rotate, edit, delete as needed
});
</script>
<?php else: ?>
<div class="ui warning message">
    <i class="photo icon"></i>
    Inga bilder eller dokument har laddats upp än.
</div>
<?php endif; ?>