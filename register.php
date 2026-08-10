<?php
// register.php - User registration page
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = json_decode(file_get_contents('users.json'), true) ?: [];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $error = 'Username already exists.';
            break;
        }
    }
    if (!isset($error)) {
        $users[] = ['username' => $username, 'password' => $password];
        file_put_contents('users.json', json_encode($users));
        $_SESSION['username'] = $username;
        header('Location: profile.php');
        exit;
    }
}
$pageTitle = 'Register';
include 'menu.php';
?>
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-icon"><i class="user plus icon"></i></div>
        <h1 class="auth-title">Create an account</h1>
        <p class="auth-sub">Join to upload, organize and share your images.</p>
        <?php if(isset($error)) echo '<div class="ui red message">'.$error.'</div>'; ?>
        <form class="ui form" method="post">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Pick a username" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Pick a password" required>
            </div>
            <button class="ui button primary auth-submit" type="submit">Register</button>
        </form>
        <div class="auth-alt">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</main>
</body>
</html>
