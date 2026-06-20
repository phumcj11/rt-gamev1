<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/layout.php';

$lang = getLang();
$t = loadTranslations($lang);
resetGameSession();

renderHead($t['app_title'], $t);
?>

<section class="hero-pattern text-white px-4 py-10 text-center">
    <div class="max-w-lg mx-auto">
        <div class="text-6xl mb-4 animate-bounce">🐘</div>
        <h1 class="text-2xl font-bold mb-2"><?= e($t['app_title']) ?></h1>
        <p class="text-lg font-medium text-red-100 mb-4"><?= e($t['app_tagline']) ?></p>
        <p class="text-sm text-red-100/90 leading-relaxed"><?= e($t['hero_subtitle']) ?></p>
    </div>
</section>

<section class="max-w-lg mx-auto px-4 py-8">
    <div class="text-center mb-6 space-y-3">
        <a href="<?= urlWithLang('/demo.php') ?>" class="btn-primary pulse-ring mx-auto bg-green-600">
            <span class="text-xl">🐘</span>
            <?= e($t['play_easy_btn']) ?>
        </a>
        <p class="text-xs text-gray-500"><?= e($t['play_easy_desc']) ?></p>
        <a href="<?= urlWithLang('/play.php') ?>" class="text-sm text-brand underline"><?= e($t['play_guide_link']) ?></a>
    </div>

    <div class="text-center mb-6">
        <a href="<?= urlWithLang('/game.php') ?>" class="btn-secondary mx-auto max-w-xs">
            <span class="text-lg">📷</span>
            <?= e($t['start_game']) ?> (AR)
        </a>
    </div>

    <div class="bg-brand-light rounded-2xl p-6">
        <h2 class="text-brand font-bold text-lg mb-4 flex items-center gap-2">
            <span>🎯</span> <?= e($t['how_to_play']) ?>
        </h2>
        <ol class="space-y-4">
            <?php foreach (['step1', 'step2', 'step3', 'step4', 'step5'] as $i => $key): ?>
            <li class="flex items-start gap-3">
                <span class="step-number"><?= $i + 1 ?></span>
                <span class="text-sm text-gray-700 pt-0.5"><?= e($t[$key]) ?></span>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <div class="mt-6 grid grid-cols-3 gap-3 text-center">
        <div class="bg-white border-2 border-brand-light rounded-xl p-3">
            <div class="text-2xl mb-1">🏪</div>
            <p class="text-xs text-gray-600">Store Logo</p>
        </div>
        <div class="bg-white border-2 border-brand-light rounded-xl p-3">
            <div class="text-2xl mb-1">🛍️</div>
            <p class="text-xs text-gray-600">Shopping Bag</p>
        </div>
        <div class="bg-white border-2 border-brand-light rounded-xl p-3">
            <div class="text-2xl mb-1">📋</div>
            <p class="text-xs text-gray-600">Poster</p>
        </div>
    </div>

    <div class="mt-6 bg-white border-2 border-dashed border-brand rounded-2xl p-4 text-center">
        <p class="text-sm font-medium text-brand mb-2">Demo AR Target (for testing)</p>
        <img src="<?= BASE_URL ?>/assets/targets/demo-scan-target.png" alt="Demo scan target"
            class="mx-auto max-w-[200px] rounded-lg shadow">
        <p class="text-xs text-gray-500 mt-2">Print or display this image to test the AR game</p>
    </div>
</section>

<?php renderFooter($t); ?>
