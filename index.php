<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="assets/js/gallery.js"></script>
    <script src="assets/js/main.js"></script>
</head>
<body>
    <div class="ui container" style="margin-top:2em;">
        <h1 class="ui header">Bild Manager</h1>
        <?php include 'documentupload.php'; ?>
        <?php include 'documentplacer.php'; ?>
    </div>
</body>
</html>