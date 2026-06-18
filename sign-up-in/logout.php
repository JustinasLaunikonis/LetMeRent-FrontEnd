<?php
declare(strict_types=1);

require_once __DIR__ . '/authConfig.php';

startAuthSession();
$_SESSION = [];
session_destroy();

redirectTo('./signup.php');
