<?php
declare(strict_types=1);

$file = __DIR__ . '/red_elephant_meshy.glb';

if (!is_file($file)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Model not found';
    exit;
}

header('Content-Type: model/gltf-binary');
header('Content-Disposition: inline; filename="red_elephant_meshy.glb"');
header('Content-Length: ' . (string) filesize($file));
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($file);
exit;
