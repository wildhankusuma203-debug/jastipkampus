<?php

// Buat direktori penyimpanan sementara di /tmp Vercel yang diizinkan menulis (writable)
$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0777, true);
    mkdir($storagePath . '/framework/views', 0777, true);
    mkdir($storagePath . '/framework/cache/data', 0777, true);
    mkdir($storagePath . '/framework/sessions', 0777, true);
    mkdir($storagePath . '/logs', 0777, true);
}

// Forward Vercel requests to normal Laravel public/index.php
require __DIR__ . '/../public/index.php';