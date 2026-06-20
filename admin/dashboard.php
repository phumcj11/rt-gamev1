<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/includes/functions.php';

requireAdmin();

$lang = getLang();
$t = loadTranslations($lang);
$stats = getDashboardStats();

$recentStmt = getDb()->query(
    'SELECT name, phone, branch, reward_code, reward_label_en, is_redeemed, created_at
     FROM players ORDER BY created_at DESC LIMIT 20'
);
$recentPlayers = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['dashboard']) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-brand text-white px-4 py-3">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <h1 class="font-bold">🐘 <?= e($t['dashboard']) ?></h1>
            <div class="flex gap-3 text-sm">
                <a href="<?= BASE_URL ?>/admin/preview-3d.php" class="hover:underline">ตั้งค่า 3D</a>
                <a href="<?= BASE_URL ?>/admin/redeem.php" class="hover:underline"><?= e($t['redeem_coupon']) ?></a>
                <a href="<?= BASE_URL ?>/admin/logout.php" class="hover:underline"><?= e($t['logout']) ?></a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <p class="text-xs text-gray-500"><?= e($t['total_players']) ?></p>
                <p class="text-2xl font-bold text-brand"><?= $stats['total_players'] ?></p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500"><?= e($t['rewards_issued']) ?></p>
                <p class="text-2xl font-bold text-brand"><?= $stats['rewards_issued'] ?></p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500"><?= e($t['rewards_redeemed']) ?></p>
                <p class="text-2xl font-bold text-green-600"><?= $stats['rewards_redeemed'] ?></p>
            </div>
            <div class="stat-card">
                <p class="text-xs text-gray-500"><?= e($t['rewards_pending']) ?></p>
                <p class="text-2xl font-bold text-amber-600"><?= $stats['rewards_pending'] ?></p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 mb-6">
            <h2 class="font-bold text-brand mb-4"><?= e($t['players_by_branch']) ?></h2>
            <?php if (empty($stats['by_branch'])): ?>
            <p class="text-gray-400 text-sm">No data yet</p>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($stats['by_branch'] as $row): ?>
                <div class="flex justify-between items-center text-sm">
                    <span><?= e(branchLabel($row['branch'], $lang)) ?></span>
                    <span class="font-bold text-brand"><?= (int) $row['total'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow p-5 overflow-x-auto">
            <h2 class="font-bold text-brand mb-4"><?= e($t['recent_players']) ?></h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= e($t['name']) ?></th>
                        <th><?= e($t['phone']) ?></th>
                        <th><?= e($t['branch']) ?></th>
                        <th><?= e($t['code']) ?></th>
                        <th><?= e($t['status']) ?></th>
                        <th><?= e($t['date']) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPlayers)): ?>
                    <tr><td colspan="6" class="text-gray-400 text-center">No players yet</td></tr>
                    <?php else: ?>
                    <?php foreach ($recentPlayers as $p): ?>
                    <tr>
                        <td><?= e($p['name']) ?></td>
                        <td><?= e($p['phone']) ?></td>
                        <td><?= e(branchLabel($p['branch'], $lang)) ?></td>
                        <td class="font-mono text-xs"><?= e($p['reward_code']) ?></td>
                        <td>
                            <?php if ($p['is_redeemed']): ?>
                            <span class="badge-redeemed"><?= e($t['redeemed']) ?></span>
                            <?php else: ?>
                            <span class="badge-pending"><?= e($t['pending']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs"><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
