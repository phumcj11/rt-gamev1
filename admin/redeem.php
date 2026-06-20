<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/includes/functions.php';

requireAdmin();

$lang = getLang();
$t = loadTranslations($lang);
$message = '';
$messageType = '';
$player = null;

$searchCode = trim((string) ($_GET['code'] ?? $_POST['code'] ?? ''));

if ($searchCode !== '') {
    $stmt = getDb()->prepare('SELECT * FROM players WHERE reward_code = ? LIMIT 1');
    $stmt->execute([strtoupper($searchCode)]);
    $player = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_id'])) {
    $playerId = (int) $_POST['redeem_id'];
    $adminName = $_SESSION['admin_name'] ?? 'Admin';

    $stmt = getDb()->prepare(
        'UPDATE players SET is_redeemed = 1, redeemed_at = NOW(), redeemed_by = ?
         WHERE id = ? AND is_redeemed = 0'
    );
    $stmt->execute([$adminName, $playerId]);

    if ($stmt->rowCount() > 0) {
        $message = $t['redeem_success'];
        $messageType = 'success';
    } else {
        $message = $t['already_redeemed'];
        $messageType = 'warning';
    }

    $searchCode = trim((string) ($_POST['code'] ?? ''));
    if ($searchCode !== '') {
        $stmt = getDb()->prepare('SELECT * FROM players WHERE reward_code = ? LIMIT 1');
        $stmt->execute([strtoupper($searchCode)]);
        $player = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['redeem_coupon']) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-brand text-white px-4 py-3">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <h1 class="font-bold">🎫 <?= e($t['redeem_coupon']) ?></h1>
            <div class="flex gap-3 text-sm">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="hover:underline"><?= e($t['dashboard']) ?></a>
                <a href="<?= BASE_URL ?>/admin/logout.php" class="hover:underline"><?= e($t['logout']) ?></a>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto p-4">
        <?php if ($message): ?>
        <div class="mb-4 p-3 rounded-lg text-sm <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
            <?= e($message) ?>
        </div>
        <?php endif; ?>

        <form method="get" class="bg-white rounded-xl shadow p-5 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2"><?= e($t['search_code']) ?></label>
            <div class="flex gap-2">
                <input type="text" name="code" value="<?= e($searchCode) ?>" required
                    class="flex-1 border-2 border-gray-200 rounded-xl px-4 py-3 font-mono uppercase focus:border-brand focus:outline-none"
                    placeholder="ELXXXXXX">
                <button type="submit" class="btn-secondary whitespace-nowrap"><?= e($t['search']) ?></button>
            </div>
        </form>

        <?php if ($searchCode !== '' && !$player): ?>
        <div class="bg-white rounded-xl shadow p-6 text-center text-gray-500">
            <?= e($t['not_found']) ?>: <strong><?= e(strtoupper($searchCode)) ?></strong>
        </div>
        <?php elseif ($player): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-center mb-6">
                <div class="coupon-code inline-block"><?= e($player['reward_code']) ?></div>
                <?php if ($player['is_redeemed']): ?>
                <p class="mt-3"><span class="badge-redeemed"><?= e($t['redeemed']) ?></span></p>
                <?php else: ?>
                <p class="mt-3"><span class="badge-pending"><?= e($t['pending']) ?></span></p>
                <?php endif; ?>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div>
                    <h3 class="font-bold text-brand text-sm mb-2"><?= e($t['player_info']) ?></h3>
                    <dl class="text-sm space-y-1">
                        <dt class="text-gray-500"><?= e($t['name']) ?></dt>
                        <dd class="font-medium mb-2"><?= e($player['name']) ?></dd>
                        <dt class="text-gray-500"><?= e($t['phone']) ?></dt>
                        <dd class="font-medium mb-2"><?= e($player['phone']) ?></dd>
                        <dt class="text-gray-500"><?= e($t['branch']) ?></dt>
                        <dd class="font-medium mb-2"><?= e(branchLabel($player['branch'], $lang)) ?></dd>
                        <dt class="text-gray-500"><?= e($t['form_nationality']) ?></dt>
                        <dd class="font-medium"><?= e($player['nationality']) ?></dd>
                    </dl>
                </div>
                <div>
                    <h3 class="font-bold text-brand text-sm mb-2"><?= e($t['reward_info']) ?></h3>
                    <dl class="text-sm space-y-1">
                        <dt class="text-gray-500"><?= e($t['your_reward']) ?></dt>
                        <dd class="font-medium mb-2"><?= e($lang === 'th' ? $player['reward_label_th'] : $player['reward_label_en']) ?></dd>
                        <dt class="text-gray-500"><?= e($t['date']) ?></dt>
                        <dd class="font-medium mb-2"><?= e(date('d/m/Y H:i', strtotime($player['created_at']))) ?></dd>
                        <?php if ($player['is_redeemed']): ?>
                        <dt class="text-gray-500"><?= e($t['redeemed']) ?></dt>
                        <dd class="font-medium"><?= e(date('d/m/Y H:i', strtotime((string) $player['redeemed_at']))) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <?php if (!$player['is_redeemed']): ?>
            <form method="post" class="text-center">
                <input type="hidden" name="code" value="<?= e($player['reward_code']) ?>">
                <input type="hidden" name="redeem_id" value="<?= (int) $player['id'] ?>">
                <button type="submit" class="btn-primary max-w-xs mx-auto" onclick="return confirm('Mark this coupon as redeemed?')">
                    ✅ <?= e($t['mark_redeemed']) ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
