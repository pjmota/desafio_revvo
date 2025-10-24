<?php
require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\AuthController;

$auth = new AuthController();
$auth->logout();
exit;
?>