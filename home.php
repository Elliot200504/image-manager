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
        <div class="ui three stackable cards">
            <?php if (empty($documents)): ?>
                <div class="ui message">No documents found.</div>
            <?php else: ?>
                <?php foreach ($documents as $idx => $img): ?>
                    <div class="ui card">
                        <div class="image" style="height:220px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                            <img src="uploads/<?= htmlspecialchars($img['source']) ?>" alt="<?= htmlspecialchars($img['title'] ?? $img['filename']) ?>" style="max-height:200px;max-width:100%;object-fit:cover;cursor:pointer;" onclick="window.imageGallery && window.imageGallery.open(['uploads/<?= htmlspecialchars($img['source']) ?>'], 0);">
                        </div>
                        <div class="content">
                            <div class="header">
                                <?php if (!empty($img['title'])): ?>
                                    <?= htmlspecialchars($img['title']) ?>
                                <?php else: ?>
                                    <span style="color:#888;">No title</span>
                                <?php endif; ?>
                            </div>
                            <div class="description">
                                Posted by: <strong><?= htmlspecialchars($img['username'] ?? 'unknown') ?></strong>
                            </div>
                        </div>
                        <div class="extra content">
                            <a href="uploads/<?= htmlspecialchars($img['source']) ?>" download="<?= htmlspecialchars($img['filename']) ?>" title="Ladda ner">
                                <i class="download icon"></i> Download
                            </a>
                            <?php if (isset($_SESSION['username']) && $_SESSION['username'] === ($img['username'] ?? '')): ?>
                                <div class="ui right floated buttons">
                                    <button class="ui mini icon button rotate-btn" title="Rotera"><i class="undo icon"></i></button>
                                    <button class="ui mini icon button edit-btn" title="Redigera"><i class="pencil alternate icon"></i></button>
                                    <button class="ui mini icon button delete-btn" title="Ta bort"><i class="trash icon"></i></button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>