

<?php
if(!isset($_SESSION)) {
	session_start();
}
$logged_in = isset($_SESSION['username']) || (isset($_COOKIE['logged_in']) && $_COOKIE['logged_in'] === '1');
?>
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