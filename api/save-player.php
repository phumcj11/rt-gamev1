<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$lang = getLang();
$translations = loadTranslations($lang);

if (!isGameComplete()) {
    jsonResponse(['success' => false, 'message' => $translations['error_incomplete_game']], 400);
}

$name = trim((string) ($_POST['name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$branch = trim((string) ($_POST['branch'] ?? ''));
$nationality = trim((string) ($_POST['nationality'] ?? ''));

if ($name === '' || $phone === '' || $branch === '' || $nationality === '') {
    jsonResponse(['success' => false, 'message' => $translations['error_required']], 400);
}

if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
    jsonResponse(['success' => false, 'message' => $translations['error_invalid_phone']], 400);
}

$branches = getBranches();
if (!isset($branches[$branch])) {
    jsonResponse(['success' => false, 'message' => $translations['error_required']], 400);
}

$reward = $_SESSION['pending_reward'] ?? pickRandomReward();
$code = generateRewardCode();

try {
    $stmt = getDb()->prepare(
        'INSERT INTO players (name, phone, branch, nationality, items_collected, reward_code, reward_type, reward_label_en, reward_label_th)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $phone,
        $branch,
        $nationality,
        ITEMS_REQUIRED,
        $code,
        $reward['reward_key'],
        $reward['label_en'],
        $reward['label_th'],
    ]);

    $_SESSION['saved_player'] = [
        'code' => $code,
        'reward_en' => $reward['label_en'],
        'reward_th' => $reward['label_th'],
        'name' => $name,
    ];
    resetGameSession();

    jsonResponse([
        'success' => true,
        'code' => $code,
        'reward' => $lang === 'th' ? $reward['label_th'] : $reward['label_en'],
        'redirect' => BASE_URL . '/reward.php?lang=' . $lang . '&saved=1',
    ]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => $translations['error_save']], 500);
}
