(function () {
    'use strict';

    const config = window.GAME_CONFIG || {};
    const msg = config.messages || {};

    let collectedCount = config.initialCount || 0;
    let targetVisible = false;
    let collecting = false;
    let arStarted = false;
    let previewStream = null;
    let modelBlobUrl = null;
    let modelLoading = false;
    let modelLoaded = false;
    let sceneEl = null;

    const startScreen = document.getElementById('start-screen');
    const startBtn = document.getElementById('start-ar-btn');
    const retryBtn = document.getElementById('retry-camera-btn');
    const loadingScreen = document.getElementById('loading-screen');
    const loadingText = document.getElementById('loading-text');
    const cameraError = document.getElementById('camera-error');
    const cameraErrorMsg = document.getElementById('camera-error-msg');
    const cameraErrorHelp = document.getElementById('camera-error-help');
    const completeModal = document.getElementById('complete-modal');
    const itemCount = document.getElementById('item-count');
    const scanStatus = document.getElementById('scan-status');
    const toast = document.getElementById('toast');
    const itemDots = document.querySelectorAll('.item-dot');
    const arContainer = document.getElementById('ar-container');
    const sceneTemplate = document.getElementById('ar-scene-template');

    function showToast(message, duration = 2200) {
        toast.textContent = message;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), duration);
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

    async function collectItem(itemId) {
        if (collecting) return;
        collecting = true;

        try {
            const res = await fetch(config.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ item_id: itemId }),
            });
            const data = await res.json();

            if (data.added) {
                collectedCount = data.count;
                updateUI();
                showToast('🍀 ' + (msg.tapToCollect || 'Collected!'));

                if (data.complete) {
                    setTimeout(() => completeModal.classList.remove('hidden'), 800);
                }
            } else {
                showToast(msg.alreadyCollected || 'Already collected!');
            }
        } catch (err) {
            showToast('Connection error. Try again.');
        } finally {
            collecting = false;
        }
    }

    function hideLoading() {
        loadingScreen?.classList.add('hidden');
    }

    function showLoading(text) {
        if (loadingText && text) loadingText.textContent = text;
        loadingScreen?.classList.remove('hidden');
        cameraError?.classList.add('hidden');
    }

    function showCameraError(message, helpHtml) {
        hideLoading();
        startScreen?.classList.add('hidden');
        if (cameraErrorMsg) cameraErrorMsg.textContent = message || msg.cameraDenied;
        if (cameraErrorHelp) cameraErrorHelp.innerHTML = helpHtml || msg.cameraHelpSteps || '';
        cameraError?.classList.remove('hidden');
        arStarted = false;
    }

    function checkSecureContext() {
        return window.isSecureContext;
    }

    function getHelpSteps() {
        return msg.cameraHelpSteps || '';
    }

    async function requestCameraPermission() {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('NO_API');
        }

        previewStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 640, max: 1280 },
                height: { ideal: 480, max: 720 },
            },
            audio: false,
        });
        return previewStream;
    }

    function stopPreviewStream() {
        if (previewStream) {
            previewStream.getTracks().forEach((track) => track.stop());
            previewStream = null;
        }
    }

    async function preloadElephantModel() {
        if (modelBlobUrl || modelLoading) return modelBlobUrl;
        modelLoading = true;

        try {
            const url = config.elephantModel;
            const res = await fetch(url, { credentials: 'same-origin', cache: 'force-cache' });
            if (!res.ok) throw new Error('MODEL_HTTP_' + res.status);

            const buffer = await res.arrayBuffer();
            if (!buffer || buffer.byteLength < 1000) throw new Error('MODEL_EMPTY');

            if (modelBlobUrl) URL.revokeObjectURL(modelBlobUrl);
            modelBlobUrl = URL.createObjectURL(new Blob([buffer], { type: 'model/gltf-binary' }));
            return modelBlobUrl;
        } finally {
            modelLoading = false;
        }
    }

    function showFallbackElephant() {
        document.getElementById('elephant-model')?.setAttribute('visible', false);
        document.getElementById('elephant-fallback')?.setAttribute('visible', true);
    }

    function showGltfElephant() {
        document.getElementById('elephant-fallback')?.setAttribute('visible', false);
        document.getElementById('elephant-model')?.setAttribute('visible', true);
    }

    function fitModelToTarget(modelEl, targetHeight) {
        const object = modelEl.getObject3D('mesh');
        if (!object || typeof THREE === 'undefined') return false;

        object.updateMatrixWorld(true);
        const box = new THREE.Box3().setFromObject(object);
        if (box.isEmpty()) return false;

        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z, 0.001);
        const scale = targetHeight / maxDim;

        modelEl.setAttribute('scale', `${scale} ${scale} ${scale}`);
        modelEl.setAttribute('position', {
            x: -center.x * scale,
            y: -center.y * scale + targetHeight * 0.5,
            z: -center.z * scale + 0.05,
        });
        return true;
    }

    function setupElephantModel() {
        const model = document.getElementById('elephant-model');
        if (!model || model.dataset.ready) return;
        model.dataset.ready = '1';

        model.addEventListener('model-loaded', () => {
            if (fitModelToTarget(model, 0.5)) {
                showGltfElephant();
                modelLoaded = true;
            }
        });

        model.addEventListener('model-error', () => {
            showFallbackElephant();
        });
    }

    function loadGltfInBackground() {
        preloadElephantModel()
            .then((blobUrl) => {
                const model = document.getElementById('elephant-model');
                if (!model || !blobUrl) return;
                model.setAttribute('src', blobUrl);
            })
            .catch(() => {
                /* keep lightweight fallback */
            });
    }

    function optimizeRenderer() {
        if (!sceneEl?.renderer) return;
        sceneEl.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    }

    function mountArScene() {
        if (sceneEl || !sceneTemplate || !arContainer) return sceneEl;

        const clone = sceneTemplate.content.cloneNode(true);
        arContainer.appendChild(clone);
        arContainer.classList.remove('hidden');
        sceneEl = document.getElementById('ar-scene');
        setupElephantModel();
        showFallbackElephant();
        return sceneEl;
    }

    function bindArEvents() {
        if (!sceneEl || sceneEl.dataset.bound) return;
        sceneEl.dataset.bound = '1';

        sceneEl.addEventListener('arReady', () => {
            hideLoading();
            stopPreviewStream();
            optimizeRenderer();
            loadGltfInBackground();
        });

        sceneEl.addEventListener('arError', () => {
            showCameraError(msg.cameraDenied, getHelpSteps());
        });

        sceneEl.addEventListener('renderstart', () => {
            hideLoading();
            optimizeRenderer();
        });

        const target = document.querySelector('[mindar-image-target]');
        if (target) {
            target.addEventListener('targetFound', () => {
                targetVisible = true;
                scanStatus.textContent = msg.targetFound || 'Target found!';
            });
            target.addEventListener('targetLost', () => {
                targetVisible = false;
                scanStatus.textContent = msg.scanHint || 'Scan the target';
            });
        }

        document.getElementById('elephant-mascot')?.addEventListener('click', () => {
            if (targetVisible) collectItem('elephant_main');
        });

        document.querySelectorAll('.lucky-item').forEach((el, index) => {
            el.addEventListener('click', () => {
                if (targetVisible) collectItem('lucky_item_' + (index + 1));
            });
        });
    }

    async function startAr() {
        if (arStarted) return;

        if (config.needsHttps || !checkSecureContext()) {
            showCameraError(msg.cameraHttpsRequired, msg.httpsHelpSteps || getHelpSteps());
            return;
        }

        arStarted = true;
        startScreen?.classList.add('hidden');
        showLoading(msg.requestingCamera || 'Requesting camera...');

        try {
            await requestCameraPermission();
            showLoading(msg.loadingAr || 'Loading AR...');
            mountArScene();
            bindArEvents();

            setTimeout(hideLoading, 8000);
        } catch (err) {
            console.error('AR start error:', err);
            stopPreviewStream();
            arStarted = false;

            let message = msg.cameraDenied;
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                message = msg.cameraDenied;
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                message = msg.cameraNoDevice || msg.cameraDenied;
            } else if (err.message === 'NO_API') {
                message = msg.cameraHttpsRequired;
            }

            showCameraError(message, getHelpSteps());
        }
    }

    if (!config.needsHttps && startBtn) {
        startBtn.addEventListener('click', startAr);
    }

    retryBtn?.addEventListener('click', () => {
        cameraError?.classList.add('hidden');
        startScreen?.classList.remove('hidden');
        if (!config.needsHttps) startAr();
    });
})();
