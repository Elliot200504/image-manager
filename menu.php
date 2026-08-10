<?php
if(!isset($_SESSION)) {
	session_start();
}
$logged_in = isset($_SESSION['username']) || (isset($_COOKIE['logged_in']) && $_COOKIE['logged_in'] === '1');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · Image Manager' : 'Image Manager' ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="assets/css/gallery.css">
	<link rel="stylesheet" href="assets/css/profile.css">
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
	<script src="assets/js/gallery.js"></script>
	<script src="assets/js/main.js"></script>

</head>
<body>
<header class="site-nav">
	<div class="site-nav-inner">
		<a class="site-brand" href="<?= $logged_in ? 'home.php' : 'login.php' ?>">
			<span class="site-brand-mark"><i class="images outline icon"></i></span>
			Image Manager
		</a>
		<nav class="site-nav-links">
			<?php if ($logged_in): ?>
				<a class="site-nav-link<?= $currentPage === 'home.php' ? ' active' : '' ?>" href="home.php"><i class="home icon"></i><span>Home</span></a>
				<a class="site-nav-link<?= $currentPage === 'profile.php' ? ' active' : '' ?>" href="profile.php"><i class="user icon"></i><span>Profile</span></a>
				<a class="site-nav-link<?= $currentPage === 'documentupload.php' ? ' active' : '' ?>" href="documentupload.php"><i class="plus icon"></i><span>Post</span></a>
				<a class="site-nav-link nav-logout" href="logout.php"><i class="sign out alternate icon"></i><span>Logout</span></a>
			<?php else: ?>
				<a class="site-nav-link<?= $currentPage === 'login.php' ? ' active' : '' ?>" href="login.php">Login</a>
				<a class="site-nav-link nav-cta" href="register.php">Register</a>
			<?php endif; ?>
		</nav>
	</div>
</header>
