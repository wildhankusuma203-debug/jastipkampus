<?php

$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath . '/framework/views', 0777, true);
    mkdir($storagePath . '/framework/cache/data', 0777, true);
    mkdir($storagePath . '/framework/sessions', 0777, true);
    mkdir($storagePath . '/logs', 0777, true);
}

// Otomatis buat cache konfigurasi & route di memori /tmp jika belum ada
if (!file_exists('/tmp/config.php')) {
    $_SERVER['APP_CONFIG_CACHE'] = '/tmp/config.php';
}

require __DIR__ . '/../public/index.php';