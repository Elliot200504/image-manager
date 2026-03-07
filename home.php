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
    <h2 class="ui header">Welcome<?php if(isset($_SESSION['username'])) echo ', ' . htmlspecialchars($_SESSION['username']); ?>!</h2>
    <div class="ui segment">
        <strong>Here are the 10 most recent posts:</strong>
        <div class="ui divided items">
            <?php if (empty($recent)): ?>
                <div class="ui message">No posts yet.</div>
            <?php else: ?>
                <?php foreach ($recent as $doc): ?>
                    <div class="item">
                        <div class="content">
                            <div class="header">Document: <?php echo htmlspecialchars($doc['filename'] ?? 'Untitled'); ?></div>
                            <div class="description">
                                Bild ID: <?php echo htmlspecialchars($doc['Bild_ID'] ?? 'Unknown'); ?><br>
                                Order: <?php echo htmlspecialchars($doc['order'] ?? 'Unknown'); ?><br>
                                Source: <?php echo htmlspecialchars($doc['source'] ?? 'Unknown'); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="ui segment">
        <strong>All Documents:</strong>
        <div class="ui divided items">
            <?php if (empty($documents)): ?>
                <div class="ui message">No documents found.</div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="item">
                        <div class="content">
                            <div class="header">Document: <?php echo htmlspecialchars($doc['filename'] ?? 'Untitled'); ?></div>
                            <div class="description">
                                Bild ID: <?php echo htmlspecialchars($doc['Bild_ID'] ?? 'Unknown'); ?><br>
                                Order: <?php echo htmlspecialchars($doc['order'] ?? 'Unknown'); ?><br>
                                Source: <?php echo htmlspecialchars($doc['source'] ?? 'Unknown'); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>