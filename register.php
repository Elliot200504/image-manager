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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.8.8/semantic.min.css">
</head>
<body>
<div class="ui container" style="margin-top:2em;">
    <h2 class="ui header">Register</h2>
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
        <button class="ui button primary" type="submit">Register</button>
    </form>
    <div style="margin-top:1em;">
        <a href="login.php">Already have an account? Login</a>
    </div>
</div>
</body>
</html>