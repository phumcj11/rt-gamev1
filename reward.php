<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/layout.php';

$lang = getLang();
$t = loadTranslations($lang);
$branches = getBranches();

$saved = !empty($_GET['saved']) && !empty($_SESSION['saved_player']);
$canClaim = isGameComplete() || $saved;

if (!$canClaim && !$saved) {
    header('Location: ' . urlWithLang('/index.php'));
    exit;
}

if (isGameComplete() && empty($_SESSION['pending_reward'])) {
    $_SESSION['pending_reward'] = pickRandomReward();
}

$reward = $_SESSION['pending_reward'] ?? null;
$playerData = $_SESSION['saved_player'] ?? null;

if ($saved && $playerData) {
    $rewardLabel = $lang === 'th' ? $playerData['reward_th'] : $playerData['reward_en'];
    $couponCode = $playerData['code'];
    $playerName = $playerData['name'];
}

renderHead($t['reward_title'], $t);
?>

<section class="max-w-lg mx-auto px-4 py-8">
    <?php if ($saved && $playerData): ?>
    <div class="text-center mb-8">
        <div class="text-5xl mb-3">🎊</div>
        <h1 class="text-2xl font-bold text-brand mb-2"><?= e($t['success_title']) ?></h1>
        <p class="text-gray-600 text-sm"><?= e($t['success_message']) ?></p>
    </div>

    <div class="bg-brand-light rounded-2xl p-6 text-center mb-6">
        <p class="text-sm text-gray-600 mb-1"><?= e($t['your_reward']) ?></p>
        <p class="text-xl font-bold text-brand mb-4"><?= e($rewardLabel) ?></p>
        <p class="text-sm text-gray-600 mb-2"><?= e($t['coupon_code']) ?></p>
        <div class="coupon-code"><?= e($couponCode) ?></div>
        <p class="text-xs text-gray-500 mt-4"><?= e($t['show_staff']) ?></p>
    </div>

    <div class="text-center">
        <a href="<?= urlWithLang('/index.php') ?>" class="btn-secondary"><?= e($t['play_again']) ?></a>
    </div>

    <?php else: ?>
    <div class="text-center mb-6">
        <div class="text-5xl mb-3">🐘</div>
        <h1 class="text-2xl font-bold text-brand mb-2"><?= e($t['reward_title']) ?></h1>
        <p class="text-gray-600 text-sm"><?= e($t['reward_subtitle']) ?></p>
    </div>

    <?php if ($reward): ?>
    <div class="bg-brand-light rounded-2xl p-5 text-center mb-6">
        <p class="text-sm text-gray-600 mb-1"><?= e($t['your_reward']) ?></p>
        <p class="text-lg font-bold text-brand">
            <?= e($lang === 'th' ? $reward['label_th'] : $reward['label_en']) ?>
        </p>
    </div>
    <?php endif; ?>

    <form id="reward-form" class="space-y-4" method="post" action="<?= BASE_URL ?>/api/save-player.php?lang=<?= e($lang) ?>">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($t['form_name']) ?> *</label>
            <input type="text" name="name" required maxlength="120"
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-brand focus:outline-none"
                placeholder="<?= e($t['form_name']) ?>">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($t['form_phone']) ?> *</label>
            <input type="tel" name="phone" required maxlength="20"
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-brand focus:outline-none"
                placeholder="08x-xxx-xxxx">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($t['form_branch']) ?> *</label>
            <select name="branch" required
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-brand focus:outline-none bg-white">
                <option value=""><?= e($t['select_branch']) ?></option>
                <?php foreach ($branches as $key => $labels): ?>
                <option value="<?= e($key) ?>"><?= e($labels[$lang]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"><?= e($t['form_nationality']) ?> *</label>
            <input type="text" name="nationality" required maxlength="80"
                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-brand focus:outline-none"
                placeholder="Thai / American / Chinese...">
        </div>
        <div id="form-error" class="hidden text-brand text-sm text-center"></div>
        <button type="submit" class="btn-primary mx-auto"><?= e($t['submit']) ?></button>
    </form>
    <?php endif; ?>
</section>

<?php if (!$saved): ?>
<script>
document.getElementById('reward-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const errorEl = document.getElementById('form-error');
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;

    try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            errorEl.textContent = data.message || 'Error';
            errorEl.classList.remove('hidden');
            btn.disabled = false;
        }
    } catch (err) {
        errorEl.textContent = 'Connection error';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
    }
});
</script>
<?php endif; ?>

<?php renderFooter($t); ?>
