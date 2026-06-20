<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/functions.php';

$lang = getLang();
$t = loadTranslations($lang);

renderHead($t['play_guide_title'], $t);
?>

<section class="max-w-lg mx-auto px-4 py-8">
    <div class="text-center mb-6">
        <div class="text-5xl mb-2">🐘</div>
        <h1 class="text-2xl font-bold text-brand"><?= e($t['play_guide_title']) ?></h1>
        <p class="text-gray-600 text-sm mt-2"><?= e($t['play_guide_subtitle']) ?></p>
    </div>

    <div class="space-y-4">
        <div class="bg-green-50 border-2 border-green-500 rounded-2xl p-5">
            <p class="font-bold text-green-800 text-lg mb-1">✅ <?= e($t['play_easy_title']) ?></p>
            <p class="text-sm text-green-900 mb-4"><?= e($t['play_easy_desc']) ?></p>
            <a href="<?= urlWithLang('/demo.php') ?>" class="btn-primary mx-auto bg-green-600 shadow-green-600/30">
                <?= e($t['play_easy_btn']) ?>
            </a>
        </div>

        <div class="bg-brand-light border-2 border-brand rounded-2xl p-5">
            <p class="font-bold text-brand text-lg mb-1">📷 <?= e($t['play_ar_title']) ?></p>
            <p class="text-sm text-gray-700 mb-3"><?= e($t['play_ar_desc']) ?></p>
            <ol class="text-sm text-gray-700 space-y-2 mb-4 list-decimal list-inside">
                <li><?= e($t['play_ar_step1']) ?></li>
                <li><?= e($t['play_ar_step2']) ?></li>
                <li><?= e($t['play_ar_step3']) ?></li>
            </ol>
            <?php if (isSecureGameHost()): ?>
            <a href="<?= urlWithLang('/game.php') ?>" class="btn-primary mx-auto"><?= e($t['start_game']) ?></a>
            <?php else: ?>
            <p class="text-xs bg-yellow-100 text-yellow-900 rounded-lg p-3 mb-3"><?= e($t['play_ar_blocked']) ?></p>
            <a href="<?= localGameUrl('/game.php') ?>" class="btn-secondary mx-auto text-sm"><?= e($t['play_ar_pc_only']) ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="<?= urlWithLang('/index.php') ?>" class="text-sm text-gray-500 underline"><?= e($t['play_again']) ?></a>
    </div>
</section>

<?php renderFooter($t); ?>
