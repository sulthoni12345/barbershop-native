<?php

declare(strict_types=1);

/**
 * admin/create.php
 * Form tambah layanan (Create). Validasi di server, simpan lewat prepared statement.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/service_form.php';

require_login();

$values = ['name' => '', 'description' => '', 'price' => '', 'duration_minutes' => '30', 'is_active' => 1];
$errors = [];
$saveError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Nilai yang ditampilkan kembali bila ada error
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
            $stmt = db()->prepare(
                'INSERT INTO services (name, description, price, duration_minutes, is_active)
                 VALUES (:name, :description, :price, :duration, :active)'
            );
            $stmt->execute([
                ':name'        => $clean['name'],
                ':description' => $clean['description'],
                ':price'       => $clean['price'],
                ':duration'    => $clean['duration_minutes'],
                ':active'      => $clean['is_active'],
            ]);

            flash_set('success', 'Layanan "' . $clean['name'] . '" berhasil ditambahkan.');
            redirect('admin/');
        } catch (Throwable $e) {
            error_log('[admin/create] gagal menyimpan: ' . $e->getMessage());
            $saveError = 'Layanan belum tersimpan karena gangguan database. Coba lagi sebentar lagi.';
        }
    }
}

$pageTitle = 'Tambah layanan';
$bodyClass = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <h1>Tambah layanan</h1>
</div>

<section class="form-card wide">
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">Layanan belum tersimpan. Periksa isian yang ditandai merah.</div>
    <?php endif; ?>
    <?php if ($saveError !== null): ?>
        <div class="alert alert-error" role="alert"><?= e($saveError) ?></div>
    <?php endif; ?>

    <?php render_service_form($values, $errors, url('admin/create.php'), 'Simpan layanan'); ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
