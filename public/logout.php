<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */

// POST only: a GET logout can be triggered by any third-party page embedding
// <img src="…/logout.php">, which is a nuisance rather than a breach, but it is
// still a state change driven from outside.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('browse.php');
}

Csrf::verify();
$auth->logout();

// Start a fresh session purely to carry the confirmation message.
session_start();
flash('info', 'You have been logged out.');

redirect('login.php');
