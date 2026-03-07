


<?php
if(!isset($_SESSION)) {
	session_start();
}
$logged_in = isset($_SESSION['username']) || (isset($_COOKIE['logged_in']) && $_COOKIE['logged_in'] === '1');
?>
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
<div class="ui menu">
	<?php if ($logged_in): ?>
		<a class="item" href="home.php">Home</a>
		<a class="item" href="profile.php">Profile</a>
		<a class="item" href="documentupload.php">Post</a>
		<a class="item" href="logout.php">Logout</a>
	<?php else: ?>
		<a class="item" href="login.php">Login</a>
		<a class="item" href="register.php">Register</a>
	<?php endif; ?>
</div>