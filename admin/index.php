<?php

declare(strict_types=1);

/**
 * admin/index.php
 * Daftar SEMUA layanan (aktif dan nonaktif) untuk admin (Read).
 * Tombol Tambah, Edit, dan Hapus mengarah ke create.php, edit.php, delete.php
 * yang dibuat di langkah 9 dan 10.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$services  = [];
$loadError = false;

try {
    $services = db()
        ->query('SELECT id, name, description, price, duration_minutes, is_active
                 FROM services
                 ORDER BY id ASC')
        ->fetchAll();
} catch (Throwable $e) {
    error_log('[admin] gagal memuat layanan: ' . $e->getMessage());
    $loadError = true;
    http_response_code(503);
}

$total  = count($services);
$active = count(array_filter($services, static fn (array $s): bool => (int) $s['is_active'] === 1));

$pageTitle = 'Kelola layanan';
$bodyClass = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Kelola layanan</h1>
        <?php if (!$loadError): ?>
            <p class="muted"><?= $total ?> layanan, <?= $active ?> aktif dan tampil di halaman utama.</p>
        <?php endif; ?>
    </div>
    <a class="btn" href="<?= e(url('admin/create.php')) ?>">Tambah layanan</a>
</div>

<?php if ($loadError): ?>
    <div class="alert alert-error" role="alert">Data layanan belum bisa dimuat. Periksa koneksi database, lalu muat ulang halaman.</div>
<?php elseif (!$services): ?>
    <div class="empty-state">
        <h2>Belum ada layanan</h2>
        <p>Tambahkan layanan pertama agar daftar harga muncul di halaman utama.</p>
        <a class="btn" href="<?= e(url('admin/create.php')) ?>">Tambah layanan</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Layanan</th>
                    <th>Harga</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td>
                            <strong><?= e($s['name']) ?></strong>
                            <?php if ((string) $s['description'] !== ''): ?>
                                <div class="row-desc"><?= e($s['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="nowrap"><?= e(rupiah($s['price'])) ?></td>
                        <td class="nowrap"><?= e(durasi((int) $s['duration_minutes'])) ?></td>
                        <td>
                            <?php if ((int) $s['is_active'] === 1): ?>
                                <span class="badge badge-on">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-off">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-outline btn-sm" href="<?= e(url('admin/edit.php')) ?>?id=<?= (int) $s['id'] ?>">Edit</a>
                                <form class="inline" method="post" action="<?= e(url('admin/delete.php')) ?>"
                                      onsubmit="return confirm('Hapus layanan ini? Tindakan ini tidak bisa dibatalkan.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
