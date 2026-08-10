<?php

declare(strict_types=1);

/**
 * Shared page chrome. Expects $auth in scope (from bootstrap) and optionally
 * $pageTitle. Pages that include this must also include footer.php.
 */

/** @var Auth $auth */
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$isLoggedIn  = $auth->check();
$currentUser = $auth->username();

// Clickjacking and MIME-sniffing protection. Kept here rather than in the vhost
// so the app carries its own defaults wherever it is deployed.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$navLinks = $isLoggedIn
    ? [
        ['browse.php',  'Files',   'folder open outline'],
        ['upload.php',  'Upload',  'cloud upload'],
        ['profile.php', 'Profile', 'user outline'],
    ]
    : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' · ' . APP_NAME : APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header class="site-nav">
    <div class="site-nav-inner">
        <a class="site-brand" href="<?= $isLoggedIn ? 'browse.php' : 'login.php' ?>">
            <span class="site-brand-mark"><i class="folder open icon"></i></span>
            <?= e(APP_NAME) ?>
        </a>
        <nav class="site-nav-links">
            <?php foreach ($navLinks as [$href, $label, $icon]): ?>
                <a class="site-nav-link<?= $currentPage === $href ? ' active' : '' ?>" href="<?= e($href) ?>">
                    <i class="<?= e($icon) ?> icon"></i><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>

            <?php if ($isLoggedIn): ?>
                <!-- Logout changes state, so it is a POST with a token, not a link.
                     A GET logout can be triggered by any <img> on any page. -->
                <form class="nav-logout-form" method="post" action="logout.php">
                    <?= Csrf::field() ?>
                    <button type="submit" class="site-nav-link nav-logout">
                        <i class="sign out alternate icon"></i><span>Logout</span>
                    </button>
                </form>
            <?php else: ?>
                <a class="site-nav-link<?= $currentPage === 'login.php' ? ' active' : '' ?>" href="login.php">Login</a>
                <a class="site-nav-link nav-cta" href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php $flashes = take_flashes(); ?>
<?php if ($flashes !== []): ?>
    <div class="flash-stack">
        <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>">
                <i class="<?= $flash['type'] === 'success' ? 'check circle' : ($flash['type'] === 'error' ? 'exclamation circle' : 'info circle') ?> icon"></i>
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
