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
    $type = isset($_POST['type']) ? trim($_POST['type']) : 'Other';
    if (in_array($ext, $allowed)) {
        $filename = uniqid('img_') . '.' . $ext;
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile))
        {
            $nextOrder = count($images) + 1;
            $nextID = "Bild_ID_" . $nextOrder;
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'anonymous';
            $dims = @getimagesize($targetFile);

            $images[] = [
                "Bild_ID" => $nextID,
                "order" => $nextOrder,
                "filename" => $originalName,
                "source" => $filename,
                "username" => $username,
                "title" => $title,
                "type" => $type,
                "width" => $dims ? $dims[0] : null,
                "height" => $dims ? $dims[1] : null
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
$pageTitle = 'Post';
include 'menu.php';
?>
<main class="ui container page-main">
    <div class="upload-card">
        <h1 class="upload-title">Upload a new image</h1>
        <p class="upload-sub">Drop in an image, give it a title and a category — done.</p>
        <?php if ($feedback) echo $feedback; ?>
        <form class="ui form" method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="field">
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter a title" required>
            </div>
            <div class="field">
                <label>Type</label>
                <select name="type" required>
                    <option value="Other">Other</option>
                    <option value="Animals">Animals</option>
                    <option value="People">People</option>
                    <option value="Architecture">Architecture</option>
                    <option value="Technology">Technology</option>
                    <option value="Clothing">Clothing</option>
                </select>
            </div>
            <div class="field">
                <label>Image</label>
                <div class="file-drop" id="fileDrop">
                    <input type="file" name="image" accept="image/*" required id="imageInput">
                    <div class="file-drop-icon"><i class="cloud upload icon"></i></div>
                    <div class="file-drop-text">Click to choose an image, or drag &amp; drop it here</div>
                    <div class="file-drop-hint">JPG, PNG, GIF or WEBP</div>
                </div>
            </div>
            <div class="field preview-field">
                <img id="imagePreview" src="" alt="Preview">
                <div id="previewInfo"></div>
            </div>
            <button class="ui primary button upload-submit" type="submit">Upload</button>
        </form>
    </div>
</main>
<script>
// Image preview + drop-zone state
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const previewInfo = document.getElementById('previewInfo');
const fileDrop = document.getElementById('fileDrop');

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

// Highlight the drop zone while dragging a file over it
['dragenter', 'dragover'].forEach(evt =>
    fileDrop.addEventListener(evt, () => fileDrop.classList.add('dragover'))
);
['dragleave', 'drop'].forEach(evt =>
    fileDrop.addEventListener(evt, () => fileDrop.classList.remove('dragover'))
);
</script>
</body>
</html>
