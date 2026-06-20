<?php
declare(strict_types=1);

function renderHead(string $title, array $translations, array $extra = []): void
{
    $lang = $translations['lang_code'] ?? 'en';
    $otherLang = $translations['other_lang_code'] ?? 'th';
    $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
    $pathOnly = strtok($currentPath, '?') ?: '/';
    if (str_starts_with($pathOnly, BASE_URL)) {
        $pathOnly = substr($pathOnly, strlen(BASE_URL)) ?: '/';
    }
    $switchUrl = BASE_URL . $pathOnly . '?lang=' . $otherLang;
    ?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#DC2626">
    <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#DC2626', dark: '#B91C1C', light: '#FEE2E2' }
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <?php foreach ($extra['styles'] ?? [] as $style): ?>
        <link rel="stylesheet" href="<?= e($style) ?>">
    <?php endforeach; ?>
</head>
<body class="bg-white text-gray-900 min-h-screen flex flex-col">
    <header class="bg-brand text-white shadow-md sticky top-0 z-50">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
            <a href="<?= urlWithLang('/index.php', $lang) ?>" class="flex items-center gap-2">
                <span class="text-2xl">🐘</span>
                <span class="font-bold text-sm leading-tight"><?= e($translations['app_title']) ?></span>
            </a>
            <a href="<?= e($switchUrl) ?>" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-full transition">
                <?= e($translations['other_lang']) ?>
            </a>
        </div>
    </header>
    <main class="flex-1">
    <?php
}

function renderGameHead(string $title, array $translations): void
{
    $lang = $translations['lang_code'] ?? 'en';
    $otherLang = $translations['other_lang_code'] ?? 'th';
    $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
    $pathOnly = strtok($currentPath, '?') ?: '/';
    if (str_starts_with($pathOnly, BASE_URL)) {
        $pathOnly = substr($pathOnly, strlen(BASE_URL)) ?: '/';
    }
    $switchUrl = BASE_URL . $pathOnly . '?lang=' . $otherLang;
    ?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#DC2626">
    <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <script src="https://aframe.io/releases/1.4.2/aframe.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-aframe.prod.js"></script>
</head>
<body class="game-page bg-black overflow-hidden">
    <header class="game-header bg-brand text-white shadow-md">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
            <a href="<?= urlWithLang('/index.php', $lang) ?>" class="flex items-center gap-2">
                <span class="text-2xl">🐘</span>
                <span class="font-bold text-sm leading-tight"><?= e($translations['app_title']) ?></span>
            </a>
            <a href="<?= e($switchUrl) ?>" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-full transition">
                <?= e($translations['other_lang']) ?>
            </a>
        </div>
    </header>
    <?php
}

function renderFooter(array $translations): void
{
    ?>
    </main>
    <footer class="bg-brand-light text-brand-dark text-center text-xs py-4 px-4">
        <p><?= e($translations['campaign_note'] ?? '') ?></p>
        <p class="mt-1 opacity-70">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </footer>
</body>
</html>
    <?php
}

function renderGameFooter(): void
{
    ?>
</body>
</html>
    <?php
}
