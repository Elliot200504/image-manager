<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
if ($auth->check()) {
    redirect('browse.php');
}

$error    = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both a username and a password.';
    } elseif ($auth->attempt($username, $password)) {
        flash('success', 'Welcome back, ' . $username . '.');
        redirect('browse.php');
    } else {
        // Deliberately identical for "no such user" and "wrong password" —
        // distinguishing them tells an attacker which usernames are real.
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/partials/header.php';
?>
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-icon"><i class="sign in alternate icon"></i></div>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Log in to upload and manage your files.</p>

        <?php if ($error !== null): ?>
            <div class="form-error"><i class="exclamation circle icon"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form class="ui form" method="post" novalidate>
            <?= Csrf::field() ?>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>"
                       placeholder="Your username" required autofocus autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password" required autocomplete="current-password">
            </div>
            <button class="ui button primary auth-submit" type="submit">Log in</button>
        </form>

        <div class="auth-alt">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
