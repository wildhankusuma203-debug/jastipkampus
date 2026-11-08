<?php

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');

$storagePath = '/tmp/storage';
if (!is_dir($storagePath . '/app/private/ktm_vault')) {
    mkdir($storagePath . '/app/private/ktm_vault', 0777, true);
    mkdir($storagePath . '/app/public', 0777, true);
    mkdir($storagePath . '/framework/views', 0777, true);
    mkdir($storagePath . '/framework/cache/data', 0777, true);
    mkdir($storagePath . '/framework/sessions', 0777, true);
    mkdir($storagePath . '/logs', 0777, true);
}

$_ENV['APP_STORAGE'] = $storagePath;
$_SERVER['APP_STORAGE'] = $storagePath;

require __DIR__ . '/../public/index.php';