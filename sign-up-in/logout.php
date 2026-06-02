<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_config.php';

startAuthSession();
$_SESSION = [];
session_destroy();

redirectTo('./signin.html');

