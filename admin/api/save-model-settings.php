<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/model-settings.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);

if (!is_array($input)) {
    jsonResponse(['success' => false, 'message' => 'Invalid JSON'], 400);
}

$settings = sanitizeModelSettings($input);

if (!saveModelSettings($settings)) {
    jsonResponse(['success' => false, 'message' => 'Cannot write config file — check folder permissions'], 500);
}

jsonResponse([
    'success'  => true,
    'message'  => 'Saved',
    'settings' => $settings,
]);
