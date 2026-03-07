<?php
// home.php - Home page with recent posts
session_start();
$documents = json_decode(file_get_contents('documents.json'), true) ?: [];
$recent = array_slice(array_reverse($documents), 0, 10); // Show 10 most recent
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'menu.php'; ?>
<div class="ui container" style="margin-top:2em;">
    <h2 class="ui header">Recent Posts</h2>
    <div class="ui divided items">
        <?php if (empty($recent)): ?>
            <div class="ui message">No posts yet.</div>
        <?php else: ?>
            <?php foreach ($recent as $doc): ?>
                <div class="item">
                    <div class="content">
                        <div class="header">Document: <?php echo htmlspecialchars($doc['name'] ?? 'Untitled'); ?></div>
                        <div class="description">
                            Uploaded: <?php echo htmlspecialchars($doc['date'] ?? 'Unknown'); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>