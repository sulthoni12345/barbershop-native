<?php

declare(strict_types=1);

/**
 * health.php - pemeriksaan koneksi database.
 *
 * Publik hanya melihat {"status":"ok"} atau {"status":"error"}.
 * Rincian (versi server, SSL, jumlah data, pesan error) baru tampil
 * jika APP_DEBUG=true, dipakai saat menelusuri masalah di Azure.
 */

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$status = db_status();
http_response_code($status['ok'] ? 200 : 503);

$output = ['status' => $status['ok'] ? 'ok' : 'error'];

if (config('app_debug')) {
    $output['detail'] = $status;
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
