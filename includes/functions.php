<?php
declare(strict_types=1);

require_once BASE_PATH . '/includes/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getLang(): string
{
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
    $lang = in_array($lang, ['en', 'th'], true) ? $lang : 'en';
    $_SESSION['lang'] = $lang;
    return $lang;
}

function loadTranslations(string $lang): array
{
    $file = BASE_PATH . "/lang/{$lang}.php";
    if (!file_exists($file)) {
        $file = BASE_PATH . '/lang/en.php';
    }
    return require $file;
}

function t(array $translations, string $key, array $replacements = []): string
{
    $text = $translations[$key] ?? $key;
    foreach ($replacements as $search => $replace) {
        $text = str_replace(':' . $search, (string) $replace, $text);
    }
    return $text;
}

function urlWithLang(string $path, ?string $lang = null): string
{
    $lang = $lang ?? getLang();
    $separator = str_contains($path, '?') ? '&' : '?';
    return BASE_URL . $path . $separator . 'lang=' . $lang;
}

function fullAssetUrl(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE_URL . $path;
}

function isSecureGameHost(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    $host = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0];
    return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
}

function localGameUrl(string $path = '/game.php'): string
{
    return 'http://localhost' . BASE_URL . $path;
}

function generateRewardCode(): string
{
    do {
        $code = 'EL' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = getDb()->prepare('SELECT id FROM players WHERE reward_code = ? LIMIT 1');
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    return $code;
}

function pickRandomReward(): array
{
    $stmt = getDb()->query('SELECT reward_key, label_en, label_th, weight FROM reward_pool WHERE is_active = 1');
    $rewards = $stmt->fetchAll();

    if (empty($rewards)) {
        return [
            'reward_key' => 'discount_10',
            'label_en' => '10% Discount Coupon',
            'label_th' => 'คูปองส่วนลด 10%',
        ];
    }

    $totalWeight = array_sum(array_column($rewards, 'weight'));
    $random = random_int(1, max(1, $totalWeight));
    $current = 0;

    foreach ($rewards as $reward) {
        $current += (int) $reward['weight'];
        if ($random <= $current) {
            return $reward;
        }
    }

    return $rewards[0];
}

function getBranches(): array
{
    return [
        'siam_paragon' => ['en' => 'Siam Paragon', 'th' => 'สยามพารากอน'],
        'central_world' => ['en' => 'Central World', 'th' => 'เซ็นทรัลเวิลด์'],
        'iconsiam' => ['en' => 'ICONSIAM', 'th' => 'ไอคอนสยาม'],
        'phuket' => ['en' => 'Phuket Branch', 'th' => 'สาขาภูเก็ต'],
        'chiang_mai' => ['en' => 'Chiang Mai Branch', 'th' => 'สาขาเชียงใหม่'],
    ];
}

function branchLabel(string $key, string $lang): string
{
    $branches = getBranches();
    return $branches[$key][$lang] ?? $key;
}

function initGameSession(): void
{
    if (!isset($_SESSION['game'])) {
        $_SESSION['game'] = [
            'items' => [],
            'started_at' => time(),
        ];
    }
}

function getCollectedCount(): int
{
    return count($_SESSION['game']['items'] ?? []);
}

function addCollectedItem(string $itemId): bool
{
    initGameSession();
    $items = $_SESSION['game']['items'] ?? [];

    if (count($items) >= ITEMS_REQUIRED) {
        return false;
    }

    $_SESSION['game']['items'][] = $itemId . '_' . count($items);
    return true;
}

function isGameComplete(): bool
{
    return getCollectedCount() >= ITEMS_REQUIRED;
}

function resetGameSession(): void
{
    unset($_SESSION['game'], $_SESSION['pending_reward']);
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function getDashboardStats(): array
{
    $db = getDb();

    $totalPlayers = (int) $db->query('SELECT COUNT(*) FROM players')->fetchColumn();
    $rewardsIssued = $totalPlayers;
    $rewardsRedeemed = (int) $db->query('SELECT COUNT(*) FROM players WHERE is_redeemed = 1')->fetchColumn();

    $branchStmt = $db->query('SELECT branch, COUNT(*) AS total FROM players GROUP BY branch ORDER BY total DESC');
    $byBranch = $branchStmt->fetchAll();

    return [
        'total_players' => $totalPlayers,
        'rewards_issued' => $rewardsIssued,
        'rewards_redeemed' => $rewardsRedeemed,
        'rewards_pending' => $rewardsIssued - $rewardsRedeemed,
        'by_branch' => $byBranch,
    ];
}
