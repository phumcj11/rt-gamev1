<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/layout.php';

$lang = getLang();
$t = loadTranslations($lang);
initGameSession();

$collected = getCollectedCount();
$itemsRequired = ITEMS_REQUIRED;

renderHead($t['demo_title'] ?? 'โหมดทดสอบ', $t);
?>

<section class="max-w-lg mx-auto px-4 py-6 min-h-[70vh] flex flex-col">
    <div class="text-center mb-4">
        <span class="inline-block bg-brand text-white text-xs font-bold px-3 py-1 rounded-full mb-2">
            <?= e($t['demo_badge']) ?>
        </span>
        <h1 class="text-xl font-bold text-brand"><?= e($t['demo_title']) ?></h1>
        <p class="text-sm text-gray-600 mt-1"><?= e($t['demo_subtitle']) ?></p>
    </div>

    <div class="bg-brand-light rounded-2xl p-4 mb-4 flex items-center justify-between">
        <span class="text-sm font-medium text-brand"><?= e($t['items_collected']) ?></span>
        <span id="demo-count" class="text-lg font-bold text-brand"><?= $collected ?>/<?= $itemsRequired ?></span>
    </div>

    <div id="demo-stage" class="flex-1 bg-gradient-to-b from-red-50 to-white border-2 border-brand-light rounded-3xl flex flex-col items-center justify-center p-6 cursor-pointer select-none active:scale-[0.98] transition-transform">
        <div id="demo-elephant" class="text-[120px] leading-none drop-shadow-lg animate-bounce">🐘</div>
        <p id="demo-hint" class="mt-6 text-brand font-semibold text-center"><?= e($t['demo_tap_hint']) ?></p>
        <div id="demo-dots" class="flex gap-3 mt-4">
            <?php for ($i = 0; $i < $itemsRequired; $i++): ?>
            <div class="demo-dot w-10 h-10 rounded-full border-2 border-brand flex items-center justify-center text-sm <?= $i < $collected ? 'bg-brand text-white' : 'bg-white text-brand' ?>">
                <?= $i < $collected ? '🍀' : ($i + 1) ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <div id="demo-complete" class="hidden mt-6 text-center">
        <div class="text-5xl mb-2">🎉</div>
        <p class="font-bold text-brand mb-4"><?= e($t['complete_message']) ?></p>
        <a href="<?= urlWithLang('/reward.php') ?>" class="btn-primary mx-auto"><?= e($t['claim_reward']) ?></a>
    </div>

    <p class="text-xs text-gray-400 text-center mt-4"><?= e($t['demo_note']) ?></p>
    <a href="<?= urlWithLang('/index.php') ?>" class="text-center text-sm text-brand mt-2 underline"><?= e($t['play_again']) ?></a>
</section>

<script>
(function () {
    const apiUrl = '<?= BASE_URL ?>/api/collect-item.php';
    const required = <?= $itemsRequired ?>;
    let count = <?= $collected ?>;
    let busy = false;

    const stage = document.getElementById('demo-stage');
    const countEl = document.getElementById('demo-count');
    const hint = document.getElementById('demo-hint');
    const complete = document.getElementById('demo-complete');
    const dots = document.querySelectorAll('.demo-dot');

    function updateDots() {
        countEl.textContent = count + '/' + required;
        dots.forEach((dot, i) => {
            if (i < count) {
                dot.className = 'demo-dot w-10 h-10 rounded-full border-2 border-brand flex items-center justify-center text-sm bg-brand text-white';
                dot.textContent = '🍀';
            }
        });
    }

    stage.addEventListener('click', async () => {
        if (busy || count >= required) return;
        busy = true;

        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: 'demo_' + count }),
            });
            const data = await res.json();
            if (data.added) {
                count = data.count;
                updateDots();
                hint.textContent = count >= required ? '<?= e($t['complete_title']) ?>' : '<?= e($t['tap_to_collect']) ?> (' + count + '/' + required + ')';
                if (data.complete) {
                    stage.classList.add('hidden');
                    complete.classList.remove('hidden');
                }
            }
        } catch (e) {
            hint.textContent = 'Error - refresh page';
        } finally {
            busy = false;
        }
    });

    if (count >= required) {
        stage.classList.add('hidden');
        complete.classList.remove('hidden');
    }
})();
</script>

<?php renderFooter($t); ?>
