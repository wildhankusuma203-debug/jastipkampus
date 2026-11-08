<?php

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');

$storagePath = '/tmp/storage';
$directories = [
    $storagePath . '/app/private/ktm_vault',
    $storagePath . '/app/public',
    $storagePath . '/framework/views',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/logs',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$_ENV['APP_STORAGE'] = $storagePath;
$_SERVER['APP_STORAGE'] = $storagePath;

// Teruskan ke public/index.php agar vendor/autoload.php dimuat lebih dulu
require __DIR__ . '/../public/index.php';