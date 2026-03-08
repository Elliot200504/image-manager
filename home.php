<?php
// home.php - Home page with recent posts
session_start();

$documents = json_decode(file_get_contents('documents.json'), true) ?: [];
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


<div class="ui container" style="margin-top:2em;"></div>
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