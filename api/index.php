<?php

$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath . '/app/public', 0777, true);
    mkdir($storagePath . '/framework/views', 0777, true);
    mkdir($storagePath . '/framework/cache/data', 0777, true);
    mkdir($storagePath . '/framework/sessions', 0777, true);
    mkdir($storagePath . '/logs', 0777, true);
}

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($storagePath);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();
$kernel->terminate($request, $response);