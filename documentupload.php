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
    <div class="ui segment upload-segment" style="max-width:700px;margin:auto;padding:2em 2em 1em 2em;border-radius:18px;box-shadow:0 4px 32px #0001;background:#fff;">
        <div class="content">
            <div class="header" style="font-size:1.7em;text-align:center;">Upload a new image</div>
            <div class="description" style="margin-bottom:1em;text-align:center;color:#888;">
                Select an image to preview before uploading. Add a descriptive title for a friendlier experience!
            </div>
        </div>
        <div class="content">
            <?php if ($feedback) echo $feedback; ?>
            <form class="ui form" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="field">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="Enter a title" required>
                </div>
                <div class="field">
                    <label>Choose image</label>
                    <input type="file" name="image" accept="image/*" required id="imageInput">
                </div>
                <div class="field preview-field" style="text-align:center;">
                    <img id="imagePreview" src="" style="display:none;max-width:100%;max-height:350px;border-radius:12px;margin:1em auto;box-shadow:0 2px 12px #0002;" alt="Preview">
                    <div id="previewInfo" style="color:#888;font-size:1em;margin-top:0.5em;"></div>
                </div>
                <button class="ui primary button" type="submit" style="width:100%;font-size:1.15em;padding:1em 0;">Upload</button>
            </form>
        </div>
    </div>
</div>
<script>
// Image preview logic
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const previewInfo = document.getElementById('previewInfo');
imageInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
            previewInfo.textContent = `${file.name} (${Math.round(file.size/1024)} KB)`;
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.style.display = 'none';
        previewInfo.textContent = '';
    }
});
</script>
