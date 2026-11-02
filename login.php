<?php

declare(strict_types=1);

/**
 * login.php
 * Form login admin. Memakai auth.php: token CSRF, penguncian setelah 5 kali gagal,
 * dan ID sesi diganti saat berhasil login.
 */

require_once __DIR__ . '/includes/auth.php';

session_boot();

// Sudah login: langsung ke halaman admin
if (is_logged_in()) {
    redirect('admin/');
}

$error    = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $error = login_attempt($username, $password);

    if ($error === null) {
        flash_set('success', 'Selamat datang kembali, ' . $username . '.');
        redirect('admin/');
    }
}

$locked    = login_locked_seconds();
$pageTitle = 'Login admin';
require __DIR__ . '/includes/header.php';
?>

<section class="form-card">
    <h1>Login admin</h1>
    <p class="form-lead">Masuk untuk mengelola daftar layanan barbershop.</p>

    <?php if (!login_enabled()): ?>
        <div class="alert alert-info" role="alert">Login admin belum diaktifkan. Isi <code>admin_pass</code> di <code>config.local.php</code> (XAMPP) atau <code>ADMIN_PASSWORD</code> di Application settings (Azure).</div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login.php')) ?>" autocomplete="on">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>"
                   autocomplete="username" maxlength="100" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" maxlength="200" required>
        </div>

        <button type="submit" class="btn btn-block"<?= ($locked > 0 || !login_enabled()) ? ' disabled' : '' ?>>Masuk</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
