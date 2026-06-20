<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/model-settings.php';

requireAdmin();

$settings = loadModelSettings();
$modelUrl = $settings['modelUrl'];
if (!str_starts_with($modelUrl, 'http')) {
    $modelUrl = fullAssetUrl(str_starts_with($modelUrl, '/') ? $modelUrl : BASE_URL . '/' . ltrim($modelUrl, '/'));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า 3D ช้าง | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://aframe.io/releases/1.4.2/aframe.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <style>
        #preview-wrap { height: min(55vh, 480px); background: #111; border-radius: 1rem; overflow: hidden; position: relative; }
        #preview-scene { width: 100%; height: 100%; }
        .ctrl-row { display: grid; grid-template-columns: 110px 1fr 52px; gap: .5rem; align-items: center; margin-bottom: .45rem; }
        .ctrl-row label { font-size: .75rem; color: #555; }
        .ctrl-row input[type=range] { width: 100%; }
        .ctrl-row .val { font-size: .75rem; font-family: monospace; text-align: right; color: #B91C1C; }
        .section-title { font-size: .8rem; font-weight: 700; color: #B91C1C; margin: 1rem 0 .5rem; border-bottom: 1px solid #FEE2E2; padding-bottom: .25rem; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-brand text-white px-4 py-3">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <h1 class="font-bold text-sm md:text-base">🐘 ตั้งค่า 3D ช้าง — Preview</h1>
            <div class="flex gap-3 text-xs md:text-sm">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="hover:underline">Dashboard</a>
                <a href="<?= BASE_URL ?>/admin/redeem.php" class="hover:underline">Redeem</a>
                <a href="<?= BASE_URL ?>/game.php" target="_blank" class="hover:underline">ทดสอบ AR</a>
                <a href="<?= BASE_URL ?>/admin/logout.php" class="hover:underline">Logout</a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-4 grid lg:grid-cols-2 gap-4">
        <!-- Controls -->
        <div class="bg-white rounded-xl shadow p-4 order-2 lg:order-1 max-h-[85vh] overflow-y-auto">
            <p class="text-xs text-gray-500 mb-3">ปรับค่าแล้วดู Preview ด้านขวา → กด <strong>บันทึก</strong> เพื่อใช้ในเกม AR</p>

            <div class="section-title">ไฟล์โมเดล</div>
            <input id="f-modelUrl" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mb-2"
                   value="<?= e($settings['modelUrl']) ?>" placeholder="/assets/models/xxx.glb">
            <label class="flex items-center gap-2 text-sm mb-1">
                <input id="f-preferGltf" type="checkbox" <?= $settings['preferGltf'] ? 'checked' : '' ?>> ใช้โมเดล GLB (ถ้าโหลดได้)
            </label>
            <label class="flex items-center gap-2 text-sm mb-1">
                <input id="f-autoFit" type="checkbox" <?= $settings['autoFit'] ? 'checked' : '' ?>> Auto-fit ขนาดจาก bounding box
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input id="f-showEmoji" type="checkbox" <?= $settings['showEmoji'] ? 'checked' : '' ?>> แสดง emoji 🐘 สำรอง
            </label>

            <div class="section-title">GLB Scale / Position / Rotation</div>
            <?php
            $sliders = [
                ['gltfScale', 'Scale', 0.01, 1.5, 0.01],
                ['gltfPosX', 'Pos X', -1, 1, 0.01],
                ['gltfPosY', 'Pos Y', -0.5, 1.5, 0.01],
                ['gltfPosZ', 'Pos Z', -0.5, 1, 0.01],
                ['gltfRotX', 'Rot X', -180, 180, 1],
                ['gltfRotY', 'Rot Y', -180, 180, 1],
                ['gltfRotZ', 'Rot Z', -180, 180, 1],
                ['autoFitHeight', 'Auto-fit H', 0.1, 1.0, 0.01],
            ];
            foreach ($sliders as [$key, $label, $min, $max, $step]):
                $val = $settings[$key];
            ?>
            <div class="ctrl-row">
                <label for="f-<?= $key ?>"><?= $label ?></label>
                <input id="f-<?= $key ?>" type="range" min="<?= $min ?>" max="<?= $max ?>" step="<?= $step ?>" value="<?= $val ?>">
                <span class="val" id="v-<?= $key ?>"><?= $val ?></span>
            </div>
            <?php endforeach; ?>

            <div class="section-title">Mascot / Fallback</div>
            <?php
            $sliders2 = [
                ['mascotPosX', 'Group X', -1, 1, 0.01],
                ['mascotPosY', 'Group Y', -0.5, 1, 0.01],
                ['mascotPosZ', 'Group Z', -0.5, 1, 0.01],
                ['fallbackScale', 'Fallback scale', 0.3, 3, 0.05],
                ['emojiScale', 'Emoji scale', 0.5, 6, 0.1],
                ['emojiPosY', 'Emoji Y', 0, 1.5, 0.01],
            ];
            foreach ($sliders2 as [$key, $label, $min, $max, $step]):
                $val = $settings[$key];
            ?>
            <div class="ctrl-row">
                <label for="f-<?= $key ?>"><?= $label ?></label>
                <input id="f-<?= $key ?>" type="range" min="<?= $min ?>" max="<?= $max ?>" step="<?= $step ?>" value="<?= $val ?>">
                <span class="val" id="v-<?= $key ?>"><?= $val ?></span>
            </div>
            <?php endforeach; ?>

            <div class="flex flex-wrap gap-2 mt-4 sticky bottom-0 bg-white pt-2 pb-1">
                <button id="btn-save" type="button" class="btn-primary flex-1 min-w-[120px] text-sm py-3">💾 บันทึก</button>
                <button id="btn-reset" type="button" class="btn-secondary flex-1 min-w-[100px] text-sm py-3">↺ ค่าเริ่มต้น</button>
                <button id="btn-reload-model" type="button" class="btn-secondary flex-1 min-w-[100px] text-sm py-3">🔄 โหลด GLB ใหม่</button>
            </div>
            <p id="save-msg" class="text-xs mt-2 text-center text-gray-500"></p>
            <?php if ($settings['updatedAt']): ?>
            <p class="text-xs text-gray-400 text-center">อัปเดตล่าสุด: <?= e($settings['updatedAt']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-amber-700 bg-amber-50 rounded-lg p-2 mt-3">
                📌 หลังบันทึก: เกมบน XAMPP ใช้ทันที · เกมบน Vercel ต้อง push ไฟล์ <code>assets/config/ar-model.json</code> ขึ้น GitHub
            </p>
        </div>

        <!-- Preview -->
        <div class="order-1 lg:order-2">
            <div id="preview-wrap">
                <a-scene id="preview-scene" embedded renderer="antialias: true; alpha: false" vr-mode-ui="enabled: false">
                    <a-assets timeout="120000">
                        <a-asset-item id="preview-glb" src="<?= e($modelUrl) ?>" response-type="arraybuffer"></a-asset-item>
                    </a-assets>

                    <a-camera position="0 0.6 1.4" look-controls="enabled: true" wasd-controls="enabled: false"></a-camera>
                    <a-light type="ambient" color="#fff" intensity="1.5"></a-light>
                    <a-light type="directional" color="#fff" intensity="1" position="1 2 2"></a-light>

                    <!-- Simulated AR target card (blue) -->
                    <a-plane color="#2563EB" width="1" height="0.62" rotation="-90 0 0" position="0 0 0"></a-plane>
                    <a-text value="MIND AR Target" align="center" color="#fff" width="2"
                            position="0 0.01 -0.25" rotation="-90 0 0" scale="0.5 0.5 0.5"></a-text>

                    <a-entity id="preview-mascot" position="0 0 0.02">
                        <a-text id="preview-emoji" value="🐘" align="center" color="#DC2626" width="4"
                                position="0 0.38 0.01" scale="2.5 2.5 2.5"></a-text>

                        <a-entity id="preview-fallback">
                            <a-sphere color="#DC2626" radius="0.16" position="0 0.18 0"></a-sphere>
                            <a-sphere color="#EF4444" radius="0.11" position="0.14 0.32 0"></a-sphere>
                            <a-cylinder color="#B91C1C" radius="0.035" height="0.14" position="0.22 0.26 0" rotation="0 0 -28"></a-cylinder>
                            <a-circle color="#FECACA" radius="0.07" position="-0.06 0.34 0.01"></a-circle>
                            <a-cylinder color="#991B1B" radius="0.04" height="0.12" position="-0.08 0.08 0"></a-cylinder>
                            <a-cylinder color="#991B1B" radius="0.04" height="0.12" position="0.08 0.08 0"></a-cylinder>
                        </a-entity>

                        <a-gltf-model id="preview-model" src="#preview-glb" visible="true"
                                      position="0 0.15 0.02" scale="0.22 0.22 0.22"></a-gltf-model>

                        <a-ring color="#FBBF24" radius-inner="0.18" radius-outer="0.22"
                                position="0 0.005 0" rotation="-90 0 0"></a-ring>
                    </a-entity>
                </a-scene>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-center">ลากเมาส์ / นิ้วหมุนดูรอบโมเดล · การ์ดสีน้ำเงิน = เป้า AR</p>
        </div>
    </main>

<script>
(function () {
    const SAVE_URL = <?= json_encode(BASE_URL . '/admin/api/save-model-settings.php') ?>;
    const DEFAULTS = <?= json_encode(defaultModelSettings(), JSON_UNESCAPED_UNICODE) ?>;

    const fields = [
        'modelUrl', 'preferGltf', 'autoFit', 'showEmoji',
        'gltfScale', 'gltfPosX', 'gltfPosY', 'gltfPosZ',
        'gltfRotX', 'gltfRotY', 'gltfRotZ', 'autoFitHeight',
        'mascotPosX', 'mascotPosY', 'mascotPosZ',
        'fallbackScale', 'emojiScale', 'emojiPosY',
    ];

    const mascot   = document.getElementById('preview-mascot');
    const fallback = document.getElementById('preview-fallback');
    const model    = document.getElementById('preview-model');
    const emoji    = document.getElementById('preview-emoji');
    const saveMsg  = document.getElementById('save-msg');

    function getSettings() {
        const s = {};
        s.modelUrl = document.getElementById('f-modelUrl').value.trim();
        s.preferGltf = document.getElementById('f-preferGltf').checked;
        s.autoFit = document.getElementById('f-autoFit').checked;
        s.showEmoji = document.getElementById('f-showEmoji').checked;
        ['gltfScale','gltfPosX','gltfPosY','gltfPosZ','gltfRotX','gltfRotY','gltfRotZ',
         'autoFitHeight','mascotPosX','mascotPosY','mascotPosZ',
         'fallbackScale','emojiScale','emojiPosY'].forEach(k => {
            s[k] = parseFloat(document.getElementById('f-' + k).value);
        });
        return s;
    }

    function applyPreview(s) {
        if (mascot) mascot.setAttribute('position', `${s.mascotPosX} ${s.mascotPosY} ${s.mascotPosZ}`);

        if (fallback) fallback.setAttribute('scale', `${s.fallbackScale} ${s.fallbackScale} ${s.fallbackScale}`);

        if (emoji) {
            emoji.setAttribute('visible', s.showEmoji ? 'true' : 'false');
            emoji.setAttribute('scale', `${s.emojiScale} ${s.emojiScale} ${s.emojiScale}`);
            emoji.setAttribute('position', `0 ${s.emojiPosY} 0.01`);
        }

        if (model) {
            model.setAttribute('visible', s.preferGltf ? 'true' : 'false');
            if (fallback) fallback.setAttribute('visible', s.preferGltf ? 'false' : 'true');

            if (s.autoFit && model.getObject3D('mesh') && typeof THREE !== 'undefined') {
                try {
                    const mesh = model.getObject3D('mesh');
                    mesh.updateMatrixWorld(true);
                    const box = new THREE.Box3().setFromObject(mesh);
                    if (!box.isEmpty()) {
                        const size = box.getSize(new THREE.Vector3());
                        const center = box.getCenter(new THREE.Vector3());
                        const maxDim = Math.max(size.x, size.y, size.z, 0.001);
                        let sc = s.autoFitHeight / maxDim;
                        sc = Math.max(0.05, Math.min(sc, 2));
                        model.setAttribute('scale', `${sc} ${sc} ${sc}`);
                        model.setAttribute('position', {
                            x: -center.x * sc + s.gltfPosX,
                            y: -center.y * sc + s.gltfPosY,
                            z: -center.z * sc + s.gltfPosZ,
                        });
                        model.setAttribute('rotation', `${s.gltfRotX} ${s.gltfRotY} ${s.gltfRotZ}`);
                        return;
                    }
                } catch (_) {}
            }

            model.setAttribute('scale', `${s.gltfScale} ${s.gltfScale} ${s.gltfScale}`);
            model.setAttribute('position', `${s.gltfPosX} ${s.gltfPosY} ${s.gltfPosZ}`);
            model.setAttribute('rotation', `${s.gltfRotX} ${s.gltfRotY} ${s.gltfRotZ}`);
        }
    }

    function bindControls() {
        fields.forEach(k => {
            const el = document.getElementById('f-' + k);
            if (!el) return;
            const evt = el.type === 'checkbox' || el.type === 'text' ? 'input' : 'input';
            el.addEventListener(evt, () => {
                if (el.type === 'range') {
                    const v = document.getElementById('v-' + k);
                    if (v) v.textContent = el.value;
                }
                applyPreview(getSettings());
            });
        });

        if (model) {
            model.addEventListener('model-loaded', () => applyPreview(getSettings()));
            model.addEventListener('model-error', () => {
                if (fallback) fallback.setAttribute('visible', 'true');
                if (model) model.setAttribute('visible', 'false');
                saveMsg.textContent = '⚠️ โหลด GLB ไม่ได้ — ตรวจ path ไฟล์';
                saveMsg.className = 'text-xs mt-2 text-center text-red-600';
            });
        }
    }

    document.getElementById('btn-save')?.addEventListener('click', async () => {
        saveMsg.textContent = 'กำลังบันทึก...';
        saveMsg.className = 'text-xs mt-2 text-center text-gray-500';
        try {
            const res = await fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(getSettings()),
            });
            const data = await res.json();
            if (data.success) {
                saveMsg.textContent = '✅ บันทึกแล้ว — เปิดเกม AR ทดสอบได้เลย';
                saveMsg.className = 'text-xs mt-2 text-center text-green-600';
            } else {
                throw new Error(data.message || 'Save failed');
            }
        } catch (e) {
            saveMsg.textContent = '❌ ' + e.message;
            saveMsg.className = 'text-xs mt-2 text-center text-red-600';
        }
    });

    document.getElementById('btn-reset')?.addEventListener('click', () => {
        Object.entries(DEFAULTS).forEach(([k, v]) => {
            const el = document.getElementById('f-' + k);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!v;
            else el.value = v;
            const lab = document.getElementById('v-' + k);
            if (lab) lab.textContent = v;
        });
        applyPreview(getSettings());
        saveMsg.textContent = 'รีเซ็ตเป็นค่าเริ่มต้น (ยังไม่ได้บันทึก)';
        saveMsg.className = 'text-xs mt-2 text-center text-amber-600';
    });

    document.getElementById('btn-reload-model')?.addEventListener('click', () => {
        const url = document.getElementById('f-modelUrl').value.trim();
        if (model && url) {
            model.removeAttribute('src');
            setTimeout(() => model.setAttribute('src', url), 100);
        }
    });

    bindControls();
    applyPreview(getSettings());
})();
</script>
</body>
</html>
