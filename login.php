<?php
// login.php - User login page
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = json_decode(file_get_contents('users.json'), true) ?: [];
    $username = $_POST['username'];
    $password = $_POST['password'];
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            setcookie('logged_in', '1', time() + 3600, '/');
            header('Location: profile.php');
            exit;
        }
    }
    $error = 'Invalid username or password.';
}
$pageTitle = 'Login';
include 'menu.php';
?>
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-icon"><i class="sign in alternate icon"></i></div>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Log in to browse and share your images.</p>
        <?php if(isset($error)) echo '<div class="ui red message">'.$error.'</div>'; ?>
        <form class="ui form" method="post">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Your username" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <button class="ui button primary auth-submit" type="submit">Login</button>
        </form>
        <div class="auth-alt">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</main>
</body>
</html>
