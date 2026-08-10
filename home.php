<?php
// home.php - Home page with recent posts
session_start();

$documents = json_decode(file_get_contents('documents.json'), true) ?: [];

// Backfill image dimensions for entries uploaded before width/height were stored,
// so the frontend can reserve space and avoid layout shift.
$dimsDirty = false;
foreach ($documents as &$doc) {
    if ((empty($doc['width']) || empty($doc['height'])) && !empty($doc['source'])) {
        $path = __DIR__ . '/uploads/' . $doc['source'];
        if (file_exists($path)) {
            $dims = @getimagesize($path);
            if ($dims) {
                $doc['width'] = $dims[0];
                $doc['height'] = $dims[1];
                $dimsDirty = true;
            }
        }
    }
}
unset($doc);
if ($dimsDirty && is_writable('documents.json')) {
    file_put_contents('documents.json', json_encode($documents, JSON_PRETTY_PRINT));
}

$pageTitle = 'Home';
include 'menu.php';

// Get all types from documents

$types = array_unique(array_map(function($img){ return $img['type'] ?? 'Other'; }, $documents));
sort($types);

// Count images per type
$typeCounts = [];
foreach ($documents as $img) {
    $type = $img['type'] ?? 'Other';
    if (!isset($typeCounts[$type])) $typeCounts[$type] = 0;
    $typeCounts[$type]++;
}

// Top 3 types by image count
arsort($typeCounts);
$top3types = array_slice(array_keys($typeCounts), 0, 3);        

// Group images by type
$imagesByType = [];
foreach ($documents as $img) {
    $type = $img['type'] ?? 'Other';
    if (!isset($imagesByType[$type])) $imagesByType[$type] = [];
    $imagesByType[$type][] = $img;
}

?>


<main class="ui container page-main"></main>
<script>
window.imagesByType = <?= json_encode($imagesByType) ?>;
window.typeCounts = <?= json_encode($typeCounts) ?>;
window.types = <?= json_encode($types) ?>;
window.top3types = <?= json_encode($top3types) ?>;
window.username = <?= isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null' ?>;
</script>
<script src="assets/js/spa.js"></script>
</body>
</html>