<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $targetDir = __DIR__ . "/uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $originalName = basename($_FILES["image"]["name"]);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
        $filename = uniqid('img_') . '.' . $ext;
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $jsonPath = __DIR__ . '/documents.json';
            $images = [];
            if (file_exists($jsonPath)) {
                $images = json_decode(file_get_contents($jsonPath), true) ?? [];
            }
            $nextOrder = count($images) + 1;
            $nextID = "Bild_ID_" . $nextOrder;
            $images[] = [
                "Bild_ID" => $nextID,
                "order" => $nextOrder,
                "filename" => $filename
            ];
            file_put_contents($jsonPath, json_encode($images, JSON_PRETTY_PRINT));
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo '<div class="ui negative message">Upload failed.</div>';
        }
    } else {
        echo '<div class="ui negative message">Invalid file type. Allowed: jpg, jpeg, png, gif, webp.</div>';
    }
}
?>
<form class="ui form" method="post" enctype="multipart/form-data" style="margin-bottom:2em;">
    <div class="field">
        <label>Select Image</label>
        <input type="file" name="image" accept="image/*" required>
    </div>
    <button class="ui primary button" type="submit">Upload</button>
</form>