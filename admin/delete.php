<?php

declare(strict_types=1);

/**
 * admin/delete.php
 * Menghapus layanan (Delete). Hanya menerima POST + token CSRF dari tombol Hapus.
 * Membuka alamat ini langsung lewat browser (GET) tidak menghapus apa pun.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/');
}

csrf_check();

$rawId = (string) ($_POST['id'] ?? '');
$id    = ctype_digit($rawId) && strlen($rawId) <= 10 ? (int) $rawId : 0;

if ($id < 1) {
    flash_set('error', 'Layanan tidak ditemukan.');
    redirect('admin/');
}

try {
    $pdo = db();

    $find = $pdo->prepare('SELECT name FROM services WHERE id = :id');
    $find->execute([':id' => $id]);
    $name = $find->fetchColumn();

    if ($name === false) {
        flash_set('error', 'Layanan tidak ditemukan. Mungkin sudah dihapus.');
    } else {
        $delete = $pdo->prepare('DELETE FROM services WHERE id = :id');
        $delete->execute([':id' => $id]);
        flash_set('success', 'Layanan "' . $name . '" berhasil dihapus.');
    }
} catch (Throwable $e) {
    error_log('[admin/delete] gagal menghapus: ' . $e->getMessage());
    flash_set('error', 'Layanan belum terhapus karena gangguan database. Coba lagi sebentar lagi.');
}

redirect('admin/');
