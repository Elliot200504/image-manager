<?php
// profile.php - Modern user profile page
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['username'];
include 'menu.php';

// Handle profile update
$profilePic = 'assets/images/placeholder.png';
$description = '';
$users = [];
if (file_exists('users.json')) {
    $users = json_decode(file_get_contents('users.json'), true);
    if (!is_array($users)) $users = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update description
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    // Handle profile picture upload
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profilePic']['name'], PATHINFO_EXTENSION);
        $picName = 'profile_' . md5($username . time()) . '.' . $ext;
        $picPath = 'uploads/' . $picName;
        move_uploaded_file($_FILES['profilePic']['tmp_name'], $picPath);
        $profilePic = $picPath;
    }
    // Update user in users.json
    $found = false;
    foreach ($users as &$user) {
        if (isset($user['username']) && $user['username'] === $username) {
            $user['description'] = $description;
            if (isset($profilePic) && $profilePic !== 'assets/images/placeholder.png') {
                $user['profilePic'] = $profilePic;
            }
            $found = true;
        }
    }
    if (!$found) {
        $users[] = [
            'username' => $username,
            'description' => $description,
            'profilePic' => $profilePic
        ];
    }
    file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
// Load user info
foreach ($users as $user) {
    if (isset($user['username']) && $user['username'] === $username) {
        $description = isset($user['description']) ? $user['description'] : '';
        $profilePic = isset($user['profilePic']) ? $user['profilePic'] : $profilePic;
    }
}
// Load all posts for user
$posts = [];
if (file_exists('documents.json')) {
    $docs = json_decode(file_get_contents('documents.json'), true);
    if (is_array($docs)) {
        foreach ($docs as $doc) {
            if (isset($doc['username']) && $doc['username'] === $username) {
                $posts[] = $doc;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.js"></script>
</head>
<body>
<div class="profile-container">
    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-picture">
        <div class="profile-info">
            <div class="profile-username"><?php echo htmlspecialchars($username); ?></div>
            <div class="profile-description"><?php echo htmlspecialchars($description); ?></div>
        </div>
    </div>
    <form class="ui form profile-edit-form" action="profile.php" method="post" enctype="multipart/form-data">
        <h4 class="ui dividing header">Edit Profile</h4>
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
        </div>
        <div class="field">
            <label>About Me</label>
            <textarea name="description" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="field">
            <label>Profile Picture</label>
            <input type="file" name="profilePic" accept="image/*">
        </div>
        <button class="ui button primary" type="submit">Save Changes</button>
    </form>
    <div class="profile-posts-section">
        <div class="ui styled fluid accordion" id="profilePostsAccordion">
            <div class="title active">
                <i class="dropdown icon"></i>
                All Posts
            </div>
            <div class="content active">
                <div class="ui segment">
                    <table class="ui very basic unstackable table image-table">
                        <tbody id="imageTableBody">
                        <?php foreach ($posts as $idx => $img): ?>
                            <tr class="image-row">
                                <td class="media-cell" style="vertical-align: top;">
                                    <div class="thumb-with-order">
                                        <div class="order-tab"><?= $idx + 1 ?></div>
                                        <img src="uploads/<?= htmlspecialchars($img['source']) ?>"
                                             alt="<?= htmlspecialchars($img['title']) ?>"
                                             class="table-thumb">
                                    </div>
                                    <div class="profile-post-title-table"><?= htmlspecialchars($img['title']) ?></div>
                                </td>
                                <td class="meta-cell" style="vertical-align: top;">
                                    <div class="meta-row">
                                        <div class="actions">
                                            <a href="uploads/<?= htmlspecialchars($img['source']) ?>" download="<?= htmlspecialchars($img['filename']) ?>" title="Download">
                                                <i class="download icon"></i>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($posts)): ?>
                            <tr><td colspan="2"><div class="ui message">No posts yet.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#profilePostsAccordion').accordion();
});
</script>
</body>
</html>