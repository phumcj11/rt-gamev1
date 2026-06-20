<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/db.php';

$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = getDb()->prepare('UPDATE admin_users SET password_hash = ? WHERE username = ?');
$stmt->execute([$hash, 'admin']);

echo "Admin password reset to: admin123\n";
echo "Hash: {$hash}\n";
