<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/includes/model-settings.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

echo json_encode(loadModelSettings(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
