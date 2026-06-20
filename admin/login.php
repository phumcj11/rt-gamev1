<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$lang = getLang();
$t = loadTranslations($lang);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = getDb()->prepare('SELECT id, username, password_hash, display_name FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['display_name'];
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }

    $error = $t['invalid_login'];
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['admin_login']) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="bg-brand-light min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">🐘</div>
            <h1 class="text-xl font-bold text-brand"><?= e($t['admin_login']) ?></h1>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 text-sm rounded-lg p-3 mb-4"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($t['username']) ?></label>
                <input type="text" name="username" required autocomplete="username"
                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-brand focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($t['password']) ?></label>
                <input type="password" name="password" required autocomplete="current-password"
                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-brand focus:outline-none">
            </div>
            <button type="submit" class="btn-primary"><?= e($t['login']) ?></button>
        </form>
        <p class="text-xs text-gray-400 text-center mt-4">Default: admin / admin123</p>
    </div>
</body>
</html>
