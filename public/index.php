<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */

redirect($auth->check() ? 'browse.php' : 'login.php');
