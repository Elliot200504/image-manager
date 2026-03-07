
<?php
session_start();
?>
<div class="ui menu">
	<a class="item" href="home.php">Home</a>
	<?php if (isset($_SESSION['username'])): ?>
		<a class="item" href="profile.php">Profile</a>
		<a class="item" href="documentupload.php">Post</a>
		<a class="item" href="logout.php">Logout</a>
	<?php else: ?>
		<a class="item" href="login.php">Login</a>
		<a class="item" href="register.php">Register</a>
	<?php endif; ?>
</div>