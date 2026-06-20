(function () {
    'use strict';

    const config = window.GAME_CONFIG || {};
    const msg = config.messages || {};

    let collectedCount = config.initialCount || 0;
    let targetVisible = false;
    let collecting = false;
    let arStarted = false;
    let modelLoaded = false;
    let sceneEl = null;

    const startScreen   = document.getElementById('start-screen');
    const startBtn      = document.getElementById('start-ar-btn');
    const retryBtn      = document.getElementById('retry-camera-btn');
    const loadingScreen = document.getElementById('loading-screen');
    const loadingText   = document.getElementById('loading-text');
    const cameraError   = document.getElementById('camera-error');
    const completeModal = document.getElementById('complete-modal');
    const itemCount     = document.getElementById('item-count');
    const scanStatus    = document.getElementById('scan-status');
    const toast         = document.getElementById('toast');
    const itemDots      = document.querySelectorAll('.item-dot');
    const sceneTemplate = document.getElementById('ar-scene-template');

    /* ── UI helpers ─────────────────────────────────────────── */
    function showToast(msg_, dur = 2200) {
        toast.textContent = msg_;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, dur);
    }

    function updateUI() {
        itemCount.textContent = collectedCount + '/' + config.itemsRequired;
        itemDots.forEach((dot, i) => {
            if (i < collectedCount) {
                dot.classList.add('collected');
                dot.textContent = '🍀';
            }
        });
    }

    function hideLoading() {
        if (loadingScreen) loadingScreen.style.display = 'none';
    }
    function showLoading(text) {
        if (loadingText && text) loadingText.textContent = text;
        if (loadingScreen) loadingScreen.style.display = 'flex';
        if (cameraError) cameraError.style.display = 'none';
    }

    function showError(message) {
        hideLoading();
        if (startScreen) startScreen.style.display = 'none';
        const p = document.getElementById('camera-error-msg');
        if (p) p.textContent = message || msg.cameraDenied || 'กล้องเปิดไม่ได้';
        if (cameraError) cameraError.style.display = 'flex';
        arStarted = false;
    }

    /* ── Collect item API ────────────────────────────────────── */
    async function collectItem(itemId) {
        if (collecting) return;
        collecting = true;
        try {
            const res  = await fetch(config.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ item_id: itemId }),
            });
            const data = await res.json();
            if (data.added) {
                collectedCount = data.count;
                updateUI();
                showToast('🍀 ' + (msg.tapToCollect || 'เก็บแล้ว!'));
                if (data.complete) {
                    setTimeout(() => {
                        if (completeModal) completeModal.style.display = 'flex';
                    }, 800);
                }
            } else {
                showToast(msg.alreadyCollected || 'เก็บแล้ว!');
            }
        } catch {
            showToast('Connection error');
        } finally {
            collecting = false;
        }
    }

    /* ── Elephant model helpers ──────────────────────────────── */
    function getModel()    { return document.getElementById('elephant-model'); }
    function getFallback() { return document.getElementById('elephant-fallback'); }

    function showFallback() {
        getModel()?.setAttribute('visible', 'false');
        getFallback()?.setAttribute('visible', 'true');
    }

    function showGltf() {
        getFallback()?.setAttribute('visible', 'false');
        getModel()?.setAttribute('visible', 'true');
    }

    function fitModel(modelEl) {
        if (!modelEl || typeof THREE === 'undefined') return false;
        const mesh = modelEl.getObject3D('mesh');
        if (!mesh) return false;

        mesh.updateMatrixWorld(true);
        const box = new THREE.Box3().setFromObject(mesh);
        if (box.isEmpty()) return false;

        const size   = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z, 0.001);
        const s      = 0.5 / maxDim;

        modelEl.setAttribute('scale', `${s} ${s} ${s}`);
        modelEl.setAttribute('position', {
            x: -center.x * s,
            y: -center.y * s + 0.28,
            z: -center.z * s + 0.05,
        });
        return true;
    }

    function setupModelListeners() {
        const model = getModel();
        if (!model || model.dataset.listenersSet) return;
        model.dataset.listenersSet = '1';

        model.addEventListener('model-loaded', () => {
            if (fitModel(model)) {
                showGltf();
                modelLoaded = true;
            } else {
                showFallback();
            }
        });
        model.addEventListener('model-error', () => showFallback());
    }

    /* ── Mount AR scene ──────────────────────────────────────── */
    function mountArScene() {
        if (sceneEl) return sceneEl;
        if (!sceneTemplate) return null;

        // Append directly to body — MindAR requires this for correct compositing
        const clone = sceneTemplate.content.cloneNode(true);
        document.body.appendChild(clone);

        sceneEl = document.getElementById('ar-scene');
        if (!sceneEl) return null;

        // Set up model listeners immediately (safe before A-Frame init)
        setupModelListeners();

        return sceneEl;
    }

    /* ── Bind AR events ──────────────────────────────────────── */
    function bindArEvents() {
        if (!sceneEl || sceneEl.dataset.bound) return;
        sceneEl.dataset.bound = '1';

        sceneEl.addEventListener('arReady', () => {
            hideLoading();
            // Load GLB model in background
            const model = getModel();
            if (model && !model.getAttribute('src')) {
                model.setAttribute('src', config.elephantModel || '/assets/models/red_elephant_mascot_3d.glb');
            }
        });

        sceneEl.addEventListener('arError', () => {
            showError(msg.cameraDenied);
        });

        // Target tracking events
        sceneEl.addEventListener('loaded', () => {
            const targetEntity = sceneEl.querySelector('[mindar-image-target]');
            if (!targetEntity) return;

            targetEntity.addEventListener('targetFound', () => {
                targetVisible = true;
                if (scanStatus) scanStatus.textContent = msg.targetFound || 'พบช้างแล้ว!';
                // Show whichever elephant we have
                if (modelLoaded) showGltf(); else showFallback();
            });

            targetEntity.addEventListener('targetLost', () => {
                targetVisible = false;
                if (scanStatus) scanStatus.textContent = msg.scanHint || 'ชี้กล้องไปที่การ์ด';
            });
        });

        // Click to collect
        sceneEl.addEventListener('click', (e) => {
            if (!targetVisible) return;
            const el = e.target?.closest?.('#elephant-mascot') || e.target;
            if (el?.id === 'elephant-mascot' || el?.closest?.('#elephant-mascot')) {
                collectItem('elephant_main');
            }
        });

        // Touch to collect (mobile)
        document.getElementById('elephant-mascot')?.addEventListener('click', () => {
            if (targetVisible) collectItem('elephant_main');
        });

        document.querySelectorAll('.lucky-item').forEach((el, i) => {
            el.addEventListener('click', () => {
                if (targetVisible) collectItem('lucky_item_' + (i + 1));
            });
        });
    }

    /* ── Start AR ────────────────────────────────────────────── */
    async function startAr() {
        if (arStarted) return;

        if (!window.isSecureContext) {
            showError(msg.cameraHttpsRequired || 'ต้องใช้ HTTPS');
            return;
        }

        arStarted = true;
        if (startScreen) startScreen.style.display = 'none';
        showLoading(msg.loadingAr || 'กำลังเปิด AR...');

        mountArScene();
        if (!sceneEl) {
            showError('ไม่สามารถสร้างฉาก AR ได้');
            return;
        }

        bindArEvents();

        // Safety timeout — if arReady never fires within 25s, hide loader anyway
        setTimeout(hideLoading, 25000);
    }

    startBtn?.addEventListener('click', startAr);

    retryBtn?.addEventListener('click', () => window.location.reload());
})();
