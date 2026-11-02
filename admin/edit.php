<?php

declare(strict_types=1);

/**
 * admin/edit.php
 * Form ubah layanan (Update). Memakai form dan validasi yang sama dengan create.php.
 *   GET  ?id=N  -> tampilkan data layanan
 *   POST        -> validasi, lalu UPDATE lewat prepared statement
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/service_form.php';

require_login();

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) {
    csrf_check();
}

// ID dari form (POST) atau dari alamat (GET); hanya angka bulat positif yang diterima
$rawId = (string) ($isPost ? ($_POST['id'] ?? '') : ($_GET['id'] ?? ''));
$id    = ctype_digit($rawId) && strlen($rawId) <= 10 ? (int) $rawId : 0;

if ($id < 1) {
    flash_set('error', 'Layanan tidak ditemukan.');
    redirect('admin/');
}

$errors    = [];
$saveError = null;
$values    = [];

try {
    $stmt = db()->prepare('SELECT id, name, description, price, duration_minutes, is_active FROM services WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $service = $stmt->fetch();
} catch (Throwable $e) {
    error_log('[admin/edit] gagal memuat layanan: ' . $e->getMessage());
    flash_set('error', 'Data layanan belum bisa dimuat. Coba lagi sebentar lagi.');
    redirect('admin/');
}

if (!$service) {
    flash_set('error', 'Layanan tidak ditemukan. Mungkin sudah dihapus.');
    redirect('admin/');
}

if ($isPost) {
    $values = [
        'name'             => (string) ($_POST['name'] ?? ''),
        'description'      => (string) ($_POST['description'] ?? ''),
        'price'            => (string) ($_POST['price'] ?? ''),
        'duration_minutes' => (string) ($_POST['duration_minutes'] ?? ''),
        'is_active'        => !empty($_POST['is_active']) ? 1 : 0,
    ];

    [$clean, $errors] = validate_service($_POST);

    if (!$errors) {
        try {
            $update = db()->prepare(
                'UPDATE services
                 SET name = :name, description = :description, price = :price,
                     duration_minutes = :duration, is_active = :active
                 WHERE id = :id'
            );
            $update->execute([
                ':name'        => $clean['name'],
                ':description' => $clean['description'],
                ':price'       => $clean['price'],
                ':duration'    => $clean['duration_minutes'],
                ':active'      => $clean['is_active'],
                ':id'          => $id,
            ]);

            flash_set('success', 'Layanan "' . $clean['name'] . '" berhasil diperbarui.');
            redirect('admin/');
        } catch (Throwable $e) {
            error_log('[admin/edit] gagal menyimpan: ' . $e->getMessage());
            $saveError = 'Perubahan belum tersimpan karena gangguan database. Coba lagi sebentar lagi.';
        }
    }
} else {
    $values = [
        'name'             => (string) $service['name'],
        'description'      => (string) ($service['description'] ?? ''),
        'price'            => (string) $service['price'],
        'duration_minutes' => (string) $service['duration_minutes'],
        'is_active'        => (int) $service['is_active'],
    ];
}

$pageTitle = 'Edit layanan';
$bodyClass = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <h1>Edit layanan</h1>
</div>

<section class="form-card wide">
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">Perubahan belum tersimpan. Periksa isian yang ditandai merah.</div>
    <?php endif; ?>
    <?php if ($saveError !== null): ?>
        <div class="alert alert-error" role="alert"><?= e($saveError) ?></div>
    <?php endif; ?>

    <?php render_service_form($values, $errors, url('admin/edit.php'), 'Simpan perubahan', $id); ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
