<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: /login.php');
exit;
