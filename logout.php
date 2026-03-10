<?php
// logout.php - User logout
session_start();
session_destroy();
header('Location: login.php');
exit;
