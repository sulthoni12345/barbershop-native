<?php

declare(strict_types=1);

/**
 * index.php
 * Landing page: menampilkan layanan aktif dari database (Read).
 */

require_once __DIR__ . '/includes/db.php';

$services  = [];
$loadError = false;

try {
    $services = db()
        ->query('SELECT id, name, description, price, duration_minutes
                 FROM services
                 WHERE is_active = 1
                 ORDER BY price ASC, id ASC')
        ->fetchAll();
} catch (Throwable $e) {
    error_log('[index] gagal memuat layanan: ' . $e->getMessage());
    $loadError = true;
    http_response_code(503);
}

$pageTitle = 'Potong rambut dan cukur';
$bodyClass = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div>
        <h1>Rambut rapi, pulang percaya diri.</h1>
        <p class="lead">Potong rambut, cukur, dan rapikan jenggot dengan harga yang jelas sebelum duduk di kursi. Pilih layanan yang kamu butuhkan dari daftar di bawah.</p>
        <a class="btn" href="#layanan">Lihat daftar harga</a>
    </div>
    <div class="pole-wrap" aria-hidden="true">
        <div class="pole"></div>
    </div>
</section>

<section class="board" id="layanan" aria-labelledby="judul-layanan">
    <div class="board-head">
        <h2 id="judul-layanan">Daftar harga</h2>
        <?php if (!$loadError && $services): ?>
            <p><?= count($services) ?> layanan tersedia</p>
        <?php endif; ?>
    </div>

    <?php if ($loadError): ?>
        <div class="board-empty">
            <strong>Daftar harga belum bisa dimuat.</strong>
            Terjadi gangguan saat membaca data. Muat ulang halaman beberapa saat lagi.
        </div>
    <?php elseif (!$services): ?>
        <div class="board-empty">
            <strong>Belum ada layanan yang ditampilkan.</strong>
            Admin dapat menambahkan layanan lewat menu Login Admin.
        </div>
    <?php else: ?>
        <ul class="menu">
            <?php foreach ($services as $s): ?>
                <li>
                    <div class="menu-line">
                        <h3><?= e($s['name']) ?></h3>
                        <span class="leader" aria-hidden="true"></span>
                        <span class="price"><?= e(rupiah($s['price'])) ?></span>
                    </div>
                    <p class="menu-desc">
                        <?= e($s['description']) ?>
                        <span class="menu-dur"><?= e(durasi((int) $s['duration_minutes'])) ?></span>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
