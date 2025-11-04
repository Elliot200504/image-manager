<?php
if (!is_array($images)) $images = [];
$jsonPath = __DIR__ . '/documents.json';
$images = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'rename' && isset($input['Bild_ID'], $input['filename'])) {
        foreach ($images as &$img) {
            if ($img['Bild_ID'] === $input['Bild_ID']) {
                $img['filename'] = $input['filename'];
                break;
            }
        }
        file_put_contents($jsonPath, json_encode($images, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }

    if (is_array($input) && isset($input[0]['Bild_ID'])) {
        $newImages = [];
        foreach ($input as $newImg) {
            foreach ($images as $oldImg) {
                if ($oldImg['Bild_ID'] === $newImg['Bild_ID']) {
                    $newImg['source'] = $oldImg['source'];
                    break;
                }
            }
            $newImages[] = $newImg;
        }
        file_put_contents($jsonPath, json_encode($newImages, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
?>

<?php if (!empty($images) && is_array($images) && count($images) > 0): ?>
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
    data-bild-id="<?= htmlspecialchars($img['Bild_ID']) ?>"
    data-order="<?= htmlspecialchars($img['order']) ?>">
    <td class="media-cell" style="vertical-align: top;">
        <div class="thumb-with-order">
            <div class="order-tab"><?= $idx + 1 ?></div>
            <img src="uploads/<?= htmlspecialchars($img['source']) ?>"
                 alt="<?= htmlspecialchars($img['filename']) ?>"
                 class="table-thumb"
                 data-gallery-index="<?= $idx ?>">
        </div>
         <input type="text"
                   class="filename-input"
                   value="<?= htmlspecialchars($img['filename']) ?>"
                   data-bild-id="<?= htmlspecialchars($img['Bild_ID']) ?>">
    </td>
    <td class="meta-cell" style="vertical-align: top;">
        <div class="meta-row">
            <div class="actions">
                <a href="uploads/<?= htmlspecialchars($img['source']) ?>" download="<?= htmlspecialchars($img['filename']) ?>" title="Ladda ner">
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
        </div>
    </td>
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

    const tbody = document.getElementById('imageTableBody');
    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('filename-input')) {
            const bildId = e.target.getAttribute('data-bild-id');
            const newFilename = e.target.value.trim();
            fetch('documentplacer.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'rename', Bild_ID: bildId, filename: newFilename })
            })
            .then(res => res.json())
            .then(json => {
                if (!json.success) alert('Kunde inte byta namn!');
            });
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