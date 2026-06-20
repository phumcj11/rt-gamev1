<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
