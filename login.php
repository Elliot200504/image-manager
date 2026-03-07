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
            header('Location: profile.php');
            exit;
        }
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
</head>
<body>
<div class="ui container" style="margin-top:2em;">
    <h2 class="ui header">Login</h2>
    <?php if(isset($error)) echo '<div class="ui red message">'.$error.'</div>'; ?>
    <form class="ui form" method="post">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button class="ui button primary" type="submit">Login</button>
    </form>
    <div style="margin-top:1em;">
        <a href="register.php">Don't have an account? Register</a>
    </div>
</div>
</body>
</html>