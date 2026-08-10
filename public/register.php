<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
/** @var Users $users */
if ($auth->check()) {
    redirect('browse.php');
}

const MIN_PASSWORD_LENGTH = 8;
const MAX_USERNAME_LENGTH = 32;

$errors   = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');

    // Constrained so a username cannot be blank, collide by case, or contain
    // characters that would be awkward in a URL or a filename.
    if (!preg_match('/^[A-Za-z0-9._-]{3,' . MAX_USERNAME_LENGTH . '}$/', $username)) {
        $errors[] = 'Usernames must be 3–' . MAX_USERNAME_LENGTH
            . ' characters, using letters, numbers, dots, underscores or hyphens.';
    }

    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = 'Passwords must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if ($errors === []) {
        // create() re-checks for a duplicate inside its lock and returns false
        // if it lost the race, so this is not a check-then-write gap.
        if ($users->create($username, $password)) {
            $auth->login($username);
            flash('success', 'Account created. Welcome, ' . $username . '.');
            redirect('browse.php');
        }

        $errors[] = 'That username is already taken.';
    }
}

$pageTitle = 'Register';
require __DIR__ . '/partials/header.php';
?>
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-icon"><i class="user plus icon"></i></div>
        <h1 class="auth-title">Create an account</h1>
        <p class="auth-sub">Upload, organize and share your files.</p>

        <?php if ($errors !== []): ?>
            <div class="form-error">
                <i class="exclamation circle icon"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form class="ui form" method="post" novalidate>
            <?= Csrf::field() ?>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>"
                       placeholder="Pick a username" required autofocus autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="At least <?= MIN_PASSWORD_LENGTH ?> characters"
                       required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       placeholder="Repeat your password" required autocomplete="new-password">
            </div>
            <button class="ui button primary auth-submit" type="submit">Create account</button>
        </form>

        <div class="auth-alt">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
