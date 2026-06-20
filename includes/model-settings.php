<?php
declare(strict_types=1);

function modelSettingsFilePath(): string
{
    return BASE_PATH . '/assets/config/ar-model.json';
}

function defaultModelSettings(): array
{
    return [
        'modelUrl'      => '/assets/models/red_elephant_mascot_3d.glb',
        'preferGltf'    => true,
        'autoFit'       => true,
        'autoFitHeight' => 0.35,
        'gltfScale'     => 0.22,
        'gltfPosX'      => 0.0,
        'gltfPosY'      => 0.15,
        'gltfPosZ'      => 0.02,
        'gltfRotX'      => 0.0,
        'gltfRotY'      => 0.0,
        'gltfRotZ'      => 0.0,
        'mascotPosX'    => 0.0,
        'mascotPosY'    => 0.0,
        'mascotPosZ'    => 0.02,
        'showEmoji'     => true,
        'emojiScale'    => 2.5,
        'emojiPosY'     => 0.38,
        'fallbackScale' => 1.0,
        'updatedAt'     => null,
    ];
}

function loadModelSettings(): array
{
    $defaults = defaultModelSettings();
    $path = modelSettingsFilePath();

    if (!is_file($path)) {
        return $defaults;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return $defaults;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return $defaults;
    }

    return array_merge($defaults, $data);
}

function sanitizeModelSettings(array $input): array
{
    $defaults = defaultModelSettings();
    $out = [];

    $out['modelUrl'] = trim((string) ($input['modelUrl'] ?? $defaults['modelUrl']));
    if ($out['modelUrl'] === '') {
        $out['modelUrl'] = $defaults['modelUrl'];
    }

    $out['preferGltf']    = !empty($input['preferGltf']);
    $out['autoFit']       = !empty($input['autoFit']);
    $out['showEmoji']     = !empty($input['showEmoji']);
    $out['autoFitHeight'] = clampFloat($input['autoFitHeight'] ?? $defaults['autoFitHeight'], 0.05, 2.0);
    $out['gltfScale']     = clampFloat($input['gltfScale'] ?? $defaults['gltfScale'], 0.01, 3.0);
    $out['gltfPosX']      = clampFloat($input['gltfPosX'] ?? $defaults['gltfPosX'], -2.0, 2.0);
    $out['gltfPosY']      = clampFloat($input['gltfPosY'] ?? $defaults['gltfPosY'], -2.0, 2.0);
    $out['gltfPosZ']      = clampFloat($input['gltfPosZ'] ?? $defaults['gltfPosZ'], -2.0, 2.0);
    $out['gltfRotX']      = clampFloat($input['gltfRotX'] ?? $defaults['gltfRotX'], -180.0, 180.0);
    $out['gltfRotY']      = clampFloat($input['gltfRotY'] ?? $defaults['gltfRotY'], -180.0, 180.0);
    $out['gltfRotZ']      = clampFloat($input['gltfRotZ'] ?? $defaults['gltfRotZ'], -180.0, 180.0);
    $out['mascotPosX']    = clampFloat($input['mascotPosX'] ?? $defaults['mascotPosX'], -2.0, 2.0);
    $out['mascotPosY']    = clampFloat($input['mascotPosY'] ?? $defaults['mascotPosY'], -2.0, 2.0);
    $out['mascotPosZ']    = clampFloat($input['mascotPosZ'] ?? $defaults['mascotPosZ'], -2.0, 2.0);
    $out['emojiScale']    = clampFloat($input['emojiScale'] ?? $defaults['emojiScale'], 0.5, 8.0);
    $out['emojiPosY']     = clampFloat($input['emojiPosY'] ?? $defaults['emojiPosY'], -1.0, 2.0);
    $out['fallbackScale'] = clampFloat($input['fallbackScale'] ?? $defaults['fallbackScale'], 0.1, 5.0);
    $out['updatedAt']     = gmdate('c');

    return $out;
}

function clampFloat(mixed $value, float $min, float $max): float
{
    $n = (float) $value;
    return max($min, min($max, $n));
}

function saveModelSettings(array $settings): bool
{
    $dir = dirname(modelSettingsFilePath());
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }

    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return file_put_contents(modelSettingsFilePath(), $json . "\n") !== false;
}
