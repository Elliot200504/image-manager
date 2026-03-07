<?php
// profile.php - User profile page
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
</head>
<body>
<div class="ui container" style="margin-top:2em;">
    <h2 class="ui header">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
    <a class="ui button" href="logout.php">Logout</a>
</div>
</body>
</html>