<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/layout.php';

$lang = getLang();
$t = loadTranslations($lang);
initGameSession();

$mindTarget = 'https://cdn.jsdelivr.net/gh/hiukim/mind-ar-js@1.2.5/examples/image-tracking/assets/card-example/card.mind';
$elephantModel = fullAssetUrl('/assets/models/model.php?v=5');
$itemsRequired = ITEMS_REQUIRED;
$collected = getCollectedCount();
$needsHttps = !isSecureGameHost();
$publicGameUrl = PUBLIC_HTTPS_BASE !== '' ? rtrim(PUBLIC_HTTPS_BASE, '/') . BASE_URL . '/game.php' : '';
$localGameUrl = localGameUrl('/game.php');

renderGameHead($t['game_title'], $t);
?>

<div id="start-screen" class="fixed inset-0 z-50 bg-brand flex flex-col items-center justify-center text-white p-6 text-center overflow-y-auto">
    <div class="text-6xl mb-4">📷</div>
    <h2 class="text-xl font-bold mb-2"><?= e($t['start_game']) ?></h2>

    <?php if ($needsHttps): ?>
    <div class="bg-white/15 rounded-2xl p-4 mb-4 max-w-sm text-left text-sm space-y-3">
        <p class="font-bold text-yellow-200">⚠️ <?= e($t['https_required_title']) ?></p>
        <p><?= e($t['https_required_body']) ?></p>
        <p class="text-xs text-red-100"><?= e($t['current_url']) ?>: <strong><?= e($_SERVER['HTTP_HOST'] ?? '') ?></strong></p>
        <?php if ($publicGameUrl): ?>
        <a href="<?= e($publicGameUrl) ?>" class="btn-primary text-sm"><?= e($t['open_https_game']) ?></a>
        <?php else: ?>
        <div class="bg-black/20 rounded-lg p-3 text-xs space-y-2">
            <p class="font-semibold"><?= e($t['https_option_ngrok']) ?></p>
            <code class="block bg-black/30 p-2 rounded">ngrok http 80</code>
            <p><?= e($t['https_option_ngrok_step2']) ?></p>
            <p class="font-semibold mt-2"><?= e($t['https_option_pc']) ?></p>
            <a href="<?= e($localGameUrl) ?>" class="underline break-all"><?= e($localGameUrl) ?></a>
        </div>
        <?php endif; ?>
    </div>
    <a href="<?= urlWithLang('/demo.php') ?>" class="btn-primary max-w-xs mb-3"><?= e($t['play_easy_btn']) ?></a>
    <a href="<?= urlWithLang('/index.php') ?>" class="btn-secondary"><?= e($t['play_again']) ?></a>
    <?php else: ?>
    <p class="text-sm text-red-100 mb-4 max-w-xs"><?= e($t['step1']) ?></p>
    <button id="start-ar-btn" type="button" class="btn-primary max-w-xs"><?= e($t['allow_camera_btn']) ?></button>
    <p class="text-xs text-red-200 mt-4 max-w-xs"><?= e($t['camera_tap_hint']) ?></p>
    <?php endif; ?>
</div>

<div id="loading-screen" class="hidden fixed inset-0 z-40 bg-brand flex flex-col items-center justify-center text-white p-6">
    <div class="text-5xl mb-4 animate-pulse">🐘</div>
    <p id="loading-text" class="text-lg font-medium"><?= e($t['requesting_camera']) ?></p>
</div>

<div id="camera-error" class="hidden fixed inset-0 z-40 bg-gray-900 flex flex-col items-center justify-center text-white p-6 text-center overflow-y-auto">
    <div class="text-5xl mb-4">📷</div>
    <p id="camera-error-msg" class="text-lg mb-4"><?= e($t['camera_denied']) ?></p>
    <div id="camera-error-help" class="text-sm text-gray-300 mb-6 max-w-sm text-left space-y-2"></div>
    <button id="retry-camera-btn" type="button" class="btn-primary max-w-xs mb-3"><?= e($t['allow_camera_btn']) ?></button>
    <a href="<?= urlWithLang('/index.php') ?>" class="btn-secondary"><?= e($t['play_again']) ?></a>
</div>

<div class="game-hud px-4">
    <div class="max-w-lg mx-auto bg-black/60 rounded-2xl p-3 text-white">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium"><?= e($t['items_collected']) ?></span>
            <span id="item-count" class="text-sm font-bold"><?= $collected ?>/<?= $itemsRequired ?></span>
        </div>
        <div id="item-dots" class="flex gap-2 justify-center">
            <?php for ($i = 0; $i < $itemsRequired; $i++): ?>
            <div class="item-dot <?= $i < $collected ? 'collected' : '' ?>" data-index="<?= $i ?>">
                <?= $i < $collected ? '🍀' : '?' ?>
            </div>
            <?php endfor; ?>
        </div>
        <p id="scan-status" class="text-xs text-center mt-2 text-white/80"><?= e($t['scan_hint']) ?></p>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-24 left-1/2 -translate-x-1/2 z-30 bg-brand text-white px-5 py-3 rounded-full text-sm font-medium shadow-lg"></div>

<div id="complete-modal" class="game-overlay hidden">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full text-center">
        <div class="text-5xl mb-3">🎉</div>
        <h2 class="text-xl font-bold text-brand mb-2"><?= e($t['complete_title']) ?></h2>
        <p class="text-gray-600 text-sm mb-6"><?= e($t['complete_message']) ?></p>
        <a href="<?= urlWithLang('/reward.php') ?>" class="btn-primary mx-auto"><?= e($t['claim_reward']) ?></a>
    </div>
</div>

<div id="ar-container" style="display:none;position:fixed;inset:0;z-index:1;"></div>

<template id="ar-scene-template">
<a-scene
    id="ar-scene"
    mindar-image="imageTargetSrc: <?= e($mindTarget) ?>; maxTrack: 1; uiLoading: no; uiScanning: no; uiError: no;"
    color-space="sRGB"
    renderer="alpha: true, antialias: false, precision: mediump, colorManagement: false, logarithmicDepthBuffer: false"
    vr-mode-ui="enabled: false"
    device-orientation-permission-ui="enabled: false"
>
    <a-assets timeout="120000"></a-assets>

    <a-camera position="0 0 0" look-controls="enabled: false" raycaster="far: 10000; objects: .clickable"></a-camera>
    <a-entity cursor="fuse: false; rayOrigin: mouse" raycaster="far: 10000; objects: .clickable"></a-entity>

    <a-entity mindar-image-target="targetIndex: 0">
        <a-light type="ambient" color="#FFFFFF" intensity="2"></a-light>
        <a-light type="directional" color="#FFFFFF" intensity="0.8" position="0 1 1"></a-light>

        <a-entity id="elephant-mascot" class="clickable" position="0 0.05 0">
            <a-gltf-model
                id="elephant-model"
                position="0 0.25 0"
                rotation="0 0 0"
                scale="0.14 0.14 0.14"
                visible="false"
            ></a-gltf-model>
            <a-entity id="elephant-fallback" visible="true">
                <a-sphere radius="0.22" color="#DC2626" position="0 0.28 0"></a-sphere>
                <a-sphere radius="0.16" color="#EF4444" position="0.16 0.48 0"></a-sphere>
                <a-cylinder radius="0.05" height="0.28" color="#B91C1C" position="0.3 0.38 0" rotation="0 0 -25"></a-cylinder>
                <a-circle radius="0.12" color="#FECACA" position="-0.08 0.52 -0.05" rotation="0 30 0"></a-circle>
                <a-circle radius="0.12" color="#FECACA" position="-0.08 0.52 0.05" rotation="0 -30 0"></a-circle>
            </a-entity>
            <a-ring radius-inner="0.35" radius-outer="0.42" color="#FBBF24" position="0 0.02 0" rotation="-90 0 0" opacity="0.95"></a-ring>
        </a-entity>

        <a-entity class="lucky-item clickable" position="-0.38 0.48 0.03" scale="0.1 0.1 0.1">
            <a-sphere radius="0.35" color="#DC2626"></a-sphere>
        </a-entity>
        <a-entity class="lucky-item clickable" position="0.38 0.42 0.03" scale="0.09 0.09 0.09">
            <a-sphere radius="0.35" color="#FBBF24"></a-sphere>
        </a-entity>
        <a-entity class="lucky-item clickable" position="0 0.62 0.03" scale="0.09 0.09 0.09">
            <a-sphere radius="0.35" color="#DC2626"></a-sphere>
        </a-entity>
    </a-entity>
</a-scene>
</template>

<script>
window.GAME_CONFIG = {
    apiUrl: '<?= BASE_URL ?>/api/collect-item.php',
    rewardUrl: '<?= urlWithLang('/reward.php') ?>',
    itemsRequired: <?= $itemsRequired ?>,
    initialCount: <?= $collected ?>,
    lang: '<?= e($lang) ?>',
    elephantModel: <?= json_encode($elephantModel) ?>,
    mindTarget: <?= json_encode($mindTarget) ?>,
    needsHttps: <?= $needsHttps ? 'true' : 'false' ?>,
    messages: {
        tapToCollect: <?= json_encode($t['tap_to_collect'], JSON_UNESCAPED_UNICODE) ?>,
        alreadyCollected: <?= json_encode($t['already_collected'], JSON_UNESCAPED_UNICODE) ?>,
        targetFound: <?= json_encode($t['target_found'], JSON_UNESCAPED_UNICODE) ?>,
        scanHint: <?= json_encode($t['scan_hint'], JSON_UNESCAPED_UNICODE) ?>,
        requestingCamera: <?= json_encode($t['requesting_camera'], JSON_UNESCAPED_UNICODE) ?>,
        loadingAr: <?= json_encode($t['loading_ar'], JSON_UNESCAPED_UNICODE) ?>,
        cameraDenied: <?= json_encode($t['camera_denied'], JSON_UNESCAPED_UNICODE) ?>,
        cameraHttpsRequired: <?= json_encode($t['camera_https_required'], JSON_UNESCAPED_UNICODE) ?>,
        cameraBlockedHelp: <?= json_encode($t['camera_blocked_help'], JSON_UNESCAPED_UNICODE) ?>,
        cameraNoDevice: <?= json_encode($t['camera_no_device'], JSON_UNESCAPED_UNICODE) ?>,
        cameraHelpSteps: <?= json_encode($t['camera_help_steps'], JSON_UNESCAPED_UNICODE) ?>,
        httpsHelpSteps: <?= json_encode($t['https_help_steps'], JSON_UNESCAPED_UNICODE) ?>,
    }
};
</script>
<script src="<?= BASE_URL ?>/assets/js/game.js"></script>

<?php renderGameFooter(); ?>
