(function () {
    'use strict';

    const config  = window.GAME_CONFIG || {};
    const msg     = config.messages || {};

    let collectedCount = config.initialCount || 0;
    let targetVisible  = false;
    let collecting     = false;
    let arStarted      = false;
    let modelLoaded    = false;
    let tapCount       = 0;          // tracks which lucky-item to collect next
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
        const mesh = getModel()?.getObject3D('mesh');
        // Only swap to GLB if it actually has geometry
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

        showFallback(); // always show primitive first

        try {
            const url = config.elephantModel || '/assets/models/red_elephant_mascot_3d.glb';
            const res = await fetch(url, { cache: 'force-cache' });
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

        model.addEventListener('model-loaded', () => {
            // Fixed scale — auto-fit often produces invisible results on mobile
            model.setAttribute('scale', '0.22 0.22 0.22');
            model.setAttribute('position', '0 0.18 0.02');
            model.setAttribute('rotation', '0 0 0');

            // Try centering via bounding box, but clamp scale
            try {
                const mesh = model.getObject3D('mesh');
                if (mesh && typeof THREE !== 'undefined') {
                    mesh.updateMatrixWorld(true);
                    const box = new THREE.Box3().setFromObject(mesh);
                    if (!box.isEmpty()) {
                        const size   = box.getSize(new THREE.Vector3());
                        const center = box.getCenter(new THREE.Vector3());
                        const maxDim = Math.max(size.x, size.y, size.z, 0.001);
                        let s = 0.35 / maxDim;
                        s = Math.max(0.08, Math.min(s, 0.5)); // clamp
                        model.setAttribute('scale', `${s} ${s} ${s}`);
                        model.setAttribute('position', {
                            x: -center.x * s,
                            y: -center.y * s + 0.2,
                            z: -center.z * s + 0.02,
                        });
                    }
                }
            } catch (_) { /* use fixed scale above */ }

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

            targetEntity.addEventListener('targetFound', () => {
                targetVisible = true;
                if (scanStatus) scanStatus.textContent = msg.targetFound || 'พบช้างแล้ว! — แตะช้าง';
                showFallback(); // ensure elephant is visible when target found
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

        bindArEvents();

        // Safety: hide loader after 25 s even if arReady never fires
        setTimeout(hideLoading, 25000);
    }

    /* ── Wire up buttons ─────────────────────────────────────── */
    startBtn?.addEventListener('click', startAr);
    tapBtn?.addEventListener('click', collectItem);
    retryBtn?.addEventListener('click', () => window.location.reload());
})();
