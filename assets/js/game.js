(function () {
    'use strict';

    const config  = window.GAME_CONFIG || {};
    const msg     = config.messages || {};

    let collectedCount = config.initialCount || 0;
    let targetVisible  = false;
    let collecting     = false;
    let arStarted      = false;
    let modelLoaded    = false;
    let modelSettings  = null;
    let tapCount       = 0;
    let sceneEl        = null;

    const startScreen   = document.getElementById('start-screen');
    const startBtn      = document.getElementById('start-ar-btn');
    const retryBtn      = document.getElementById('retry-camera-btn');
    const loadingScreen = document.getElementById('loading-screen');
    const loadingText   = document.getElementById('loading-text');
    const cameraError   = document.getElementById('camera-error');
    const completeModal = document.getElementById('complete-modal');
    const tapOverlay    = document.getElementById('tap-overlay');
    const tapBtn        = document.getElementById('tap-btn');
    const itemCount     = document.getElementById('item-count');
    const scanStatus    = document.getElementById('scan-status');
    const toast         = document.getElementById('toast');
    const itemDots      = document.querySelectorAll('.item-dot');
    const sceneTemplate = document.getElementById('ar-scene-template');

    /* ── UI helpers ─────────────────────────────────────────── */
    function show(el, d) { if (el) el.style.display = d || 'flex'; }
    function hide(el)    { if (el) el.style.display = 'none'; }

    function showToast(text, dur) {
        if (!toast) return;
        toast.textContent = text;
        show(toast, 'block');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => hide(toast), dur || 2200);
    }

    function updateUI() {
        if (itemCount) itemCount.textContent = collectedCount + '/' + config.itemsRequired;
        itemDots.forEach((dot, i) => {
            if (i < collectedCount) { dot.classList.add('collected'); dot.textContent = '🍀'; }
        });
    }

    function hideLoading()     { hide(loadingScreen); }
    function showLoading(text) {
        if (loadingText && text) loadingText.textContent = text;
        show(loadingScreen);
        hide(cameraError);
    }
    function showError(message) {
        hideLoading();
        hide(startScreen);
        hide(tapOverlay);
        const p = document.getElementById('camera-error-msg');
        if (p) p.textContent = message || 'กล้องเปิดไม่ได้';
        show(cameraError);
        arStarted = false;
    }

    /* ── Tap overlay ─────────────────────────────────────────── */
    function showTapOverlay() {
        if (tapBtn) tapBtn.textContent = '🐘 แตะช้าง! (' + collectedCount + '/3)';
        show(tapOverlay, 'block');
    }
    function hideTapOverlay() { hide(tapOverlay); }

    /* ── Collect item ─────────────────────────────────────────── */
    const ITEM_IDS = ['lucky_item_1', 'lucky_item_2', 'lucky_item_3'];

    async function collectItem() {
        if (collecting || !targetVisible) return;
        collecting = true;

        const itemId = ITEM_IDS[tapCount % ITEM_IDS.length];

        try {
            const res  = await fetch(config.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ item_id: itemId }),
            });
            const data = await res.json();

            if (data.added) {
                tapCount++;
                collectedCount = data.count;
                updateUI();
                showToast('🍀 +1  (' + data.count + '/3)');
                if (tapBtn) tapBtn.textContent = '🐘 แตะช้าง! (' + data.count + '/3)';

                if (data.complete) {
                    hideTapOverlay();
                    setTimeout(() => show(completeModal), 800);
                }
            } else {
                showToast(msg.alreadyCollected || 'เก็บแล้ว!');
            }
        } catch {
            showToast('Connection error — try again');
        } finally {
            collecting = false;
        }
    }

    /* ── Model settings (from admin) ─────────────────────────── */
    function resolveAssetUrl(path) {
        if (!path || path.startsWith('http')) return path;
        const base = config.baseUrl || '';
        if (base && path.startsWith(base)) return path;
        if (path.startsWith('/')) return base + path;
        return base + '/' + path;
    }

    async function fetchModelSettings() {
        if (modelSettings) return modelSettings;
        const url = config.modelSettingsUrl || '/assets/config/ar-model.json';
        try {
            const res = await fetch(resolveAssetUrl(url) + (url.includes('?') ? '&' : '?') + 't=' + Date.now());
            if (res.ok) modelSettings = await res.json();
        } catch (_) { /* use defaults */ }
        if (!modelSettings) {
            modelSettings = {
                preferGltf: true, autoFit: true, autoFitHeight: 0.35,
                gltfScale: 0.22, gltfPosX: 0, gltfPosY: 0.15, gltfPosZ: 0.02,
                gltfRotX: 0, gltfRotY: 0, gltfRotZ: 0,
                mascotPosX: 0, mascotPosY: 0, mascotPosZ: 0.02,
                showEmoji: true, emojiScale: 2.5, emojiPosY: 0.38, fallbackScale: 1,
            };
        }
        return modelSettings;
    }

    function applySceneSettings(s) {
        if (!s) return;
        const mascot = document.getElementById('elephant-mascot');
        const fallback = getFallback();
        const emoji = document.getElementById('elephant-emoji');

        if (mascot) mascot.setAttribute('position', `${s.mascotPosX} ${s.mascotPosY} ${s.mascotPosZ}`);
        if (fallback) fallback.setAttribute('scale', `${s.fallbackScale} ${s.fallbackScale} ${s.fallbackScale}`);
        if (emoji) {
            emoji.setAttribute('visible', s.showEmoji ? 'true' : 'false');
            emoji.setAttribute('scale', `${s.emojiScale} ${s.emojiScale} ${s.emojiScale}`);
            emoji.setAttribute('position', `0 ${s.emojiPosY} 0.01`);
        }
    }

    function applyGltfSettings(model, s) {
        if (!model || !s) return;

        if (s.autoFit) {
            try {
                const mesh = model.getObject3D('mesh');
                if (mesh && typeof THREE !== 'undefined') {
                    mesh.updateMatrixWorld(true);
                    const box = new THREE.Box3().setFromObject(mesh);
                    if (!box.isEmpty()) {
                        const size = box.getSize(new THREE.Vector3());
                        const center = box.getCenter(new THREE.Vector3());
                        const maxDim = Math.max(size.x, size.y, size.z, 0.001);
                        let sc = (s.autoFitHeight || 0.35) / maxDim;
                        sc = Math.max(0.05, Math.min(sc, 2));
                        model.setAttribute('scale', `${sc} ${sc} ${sc}`);
                        model.setAttribute('position', {
                            x: -center.x * sc + (s.gltfPosX || 0),
                            y: -center.y * sc + (s.gltfPosY || 0.15),
                            z: -center.z * sc + (s.gltfPosZ || 0.02),
                        });
                        model.setAttribute('rotation', `${s.gltfRotX||0} ${s.gltfRotY||0} ${s.gltfRotZ||0}`);
                        return;
                    }
                }
            } catch (_) { /* fall through */ }
        }

        const sc = s.gltfScale || 0.22;
        model.setAttribute('scale', `${sc} ${sc} ${sc}`);
        model.setAttribute('position', `${s.gltfPosX||0} ${s.gltfPosY||0.15} ${s.gltfPosZ||0.02}`);
        model.setAttribute('rotation', `${s.gltfRotX||0} ${s.gltfRotY||0} ${s.gltfRotZ||0}`);
    }

    /* ── Elephant model helpers ──────────────────────────────── */
    function getModel()    { return document.getElementById('elephant-model'); }
    function getFallback() { return document.getElementById('elephant-fallback'); }

    function showFallback() {
        const m = getModel(), f = getFallback(), emoji = document.getElementById('elephant-emoji');
        if (m) m.setAttribute('visible', 'false');
        if (f) f.setAttribute('visible', 'true');
        if (emoji) emoji.setAttribute('visible', 'true');
    }

    function showGltf() {
        const s = modelSettings || {};
        if (s.preferGltf === false) { showFallback(); return; }

        const mesh = getModel()?.getObject3D('mesh');
        if (!mesh || mesh.children.length === 0) {
            showFallback();
            return;
        }
        const f = getFallback(), emoji = document.getElementById('elephant-emoji');
        if (f) f.setAttribute('visible', 'false');
        if (emoji) emoji.setAttribute('visible', 'false');
        const m = getModel();
        if (m) m.setAttribute('visible', 'true');
    }

    async function loadElephantModel() {
        const model = getModel();
        if (!model) return;

        const s = await fetchModelSettings();
        applySceneSettings(s);
        showFallback();

        if (s.preferGltf === false) return;

        try {
            let url = resolveAssetUrl(s.modelUrl || config.elephantModel || '/assets/models/red_elephant_mascot_3d.glb');
            const res = await fetch(url, { cache: 'no-cache' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const buf = await res.arrayBuffer();
            const blobUrl = URL.createObjectURL(new Blob([buf], { type: 'model/gltf-binary' }));
            model.setAttribute('src', blobUrl);
        } catch (_) {
            /* keep primitive elephant */
        }
    }

    function setupModelListeners() {
        const model = getModel();
        if (!model || model.dataset.listenersSet) return;
        model.dataset.listenersSet = '1';

        model.addEventListener('model-loaded', async () => {
            const s = modelSettings || await fetchModelSettings();
            applyGltfSettings(model, s);
            showGltf();
            modelLoaded = true;
        });

        model.addEventListener('model-error', () => showFallback());
    }

    /* ── Mount AR scene ──────────────────────────────────────── */
    function mountArScene() {
        if (sceneEl) return sceneEl;
        if (!sceneTemplate) return null;

        // Must append directly to body — MindAR composites video + canvas at body level
        const clone = sceneTemplate.content.cloneNode(true);
        document.body.appendChild(clone);

        sceneEl = document.getElementById('ar-scene');
        if (!sceneEl) return null;

        setupModelListeners();
        showFallback();
        return sceneEl;
    }

    /* ── AR events ───────────────────────────────────────────── */
    function bindArEvents() {
        if (!sceneEl || sceneEl.dataset.bound) return;
        sceneEl.dataset.bound = '1';

        sceneEl.addEventListener('arReady', () => {
            hideLoading();
            loadElephantModel();
        });

        sceneEl.addEventListener('arError', () => showError(msg.cameraDenied));

        // Wait for scene to be ready before querying mindar-image-target
        sceneEl.addEventListener('loaded', () => {
            const targetEntity = sceneEl.querySelector('[mindar-image-target]');
            if (!targetEntity) return;

            targetEntity.addEventListener('targetFound', async () => {
                targetVisible = true;
                if (scanStatus) scanStatus.textContent = msg.targetFound || 'พบช้างแล้ว! — แตะช้าง';
                await fetchModelSettings();
                applySceneSettings(modelSettings);
                if (modelLoaded) showGltf(); else showFallback();
                showTapOverlay();
            });

            targetEntity.addEventListener('targetLost', () => {
                targetVisible = false;
                if (scanStatus) scanStatus.textContent = msg.scanHint || 'ชี้กล้องไปที่การ์ด';
                hideTapOverlay();
            });
        });
    }

    /* ── Start AR ─────────────────────────────────────────────── */
    async function startAr() {
        if (arStarted) return;

        if (!window.isSecureContext) {
            showError(msg.cameraHttpsRequired || 'ต้องใช้ HTTPS — เปิดผ่านลิงก์ Vercel');
            return;
        }

        arStarted = true;
        hide(startScreen);
        showLoading(msg.loadingAr || 'กำลังเปิดกล้อง AR...');

        mountArScene();
        if (!sceneEl) {
            showError('ไม่สามารถสร้างฉาก AR ได้');
            return;
        }

        await fetchModelSettings();
        applySceneSettings(modelSettings);

        bindArEvents();

        // Safety: hide loader after 25 s even if arReady never fires
        setTimeout(hideLoading, 25000);
    }

    /* ── Wire up buttons ─────────────────────────────────────── */
    startBtn?.addEventListener('click', startAr);
    tapBtn?.addEventListener('click', collectItem);
    retryBtn?.addEventListener('click', () => window.location.reload());
})();
