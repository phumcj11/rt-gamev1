<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$itemId = trim((string) ($input['item_id'] ?? ''));

if ($itemId === '') {
    jsonResponse(['success' => false, 'message' => 'Invalid item'], 400);
}

initGameSession();
$added = addCollectedItem($itemId);
$count = getCollectedCount();
$complete = isGameComplete();

if ($complete && empty($_SESSION['pending_reward'])) {
    $_SESSION['pending_reward'] = pickRandomReward();
}

jsonResponse([
    'success' => true,
    'added' => $added,
    'count' => $count,
    'required' => ITEMS_REQUIRED,
    'complete' => $complete,
    'reward' => $complete ? $_SESSION['pending_reward'] : null,
]);
