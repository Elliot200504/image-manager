<?php
$input = json_decode(file_get_contents('php://input'), true);
$jsonPath = __DIR__ . '/documents.json';
$images = [];
if (file_exists($jsonPath)) {
    $images = json_decode(file_get_contents($jsonPath), true) ?? [];
}

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    if(!isset($_SESSION)){
        session_start();
    }
    $targetDir = __DIR__ . "/uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $originalName = basename($_FILES["image"]["name"]);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    if (in_array($ext, $allowed)) {
        $filename = uniqid('img_') . '.' . $ext;
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $nextOrder = count($images) + 1;
            $nextID = "Bild_ID_" . $nextOrder;
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'anonymous';
            $images[] = [
                "Bild_ID" => $nextID,
                "order" => $nextOrder,
                "filename" => $originalName,
                "source" => $filename,
                "username" => $username,
                "title" => $title
            ];
            file_put_contents($jsonPath, json_encode($images, JSON_PRETTY_PRINT));
            $feedback = '<div class="ui positive message">Upload successful!</div>';
        } else {
            $feedback = '<div class="ui negative message">Upload failed.</div>';
        }
    } else {
        $feedback = '<div class="ui negative message">Invalid file type. Allowed: jpg, jpeg, png, gif, webp.</div>';
    }
}
elseif (isset($input['action']) && $input['action'] === 'rename' && isset($input['Bild_ID'], $input['filename'])) {
    foreach ($images as &$img) {
        if ($img['Bild_ID'] === $input['Bild_ID']) {
            $img['filename'] = $input['filename']; // Only update filename
            break;
        }
    }
    file_put_contents($jsonPath, json_encode($images, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
    exit;
}
?>
<?php include 'menu.php'; ?>
<div class="ui container" style="margin-top:2em;">
    <div class="ui centered card" style="max-width:400px;margin:auto;">
        <div class="content">
            <div class="header">Upload a new image</div>
        </div>
        <div class="content">
            <?php if ($feedback) echo $feedback; ?>
            <form class="ui form" method="post" enctype="multipart/form-data">
                <div class="field">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="Enter a title" required>
                </div>
                <div class="field">
                    <label>Välj fil</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <button class="ui primary button" type="submit">Ladda upp</button>
            </form>
        </div>
    </div>
</div>
