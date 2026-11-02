<?php

declare(strict_types=1);

/**
 * includes/header.php
 *
 * Pembuka halaman: <head>, navigasi, dan flash message.
 * Sebelum require file ini, halaman boleh mengisi:
 *   $pageTitle  (string)  judul tab, contoh 'Daftar Layanan'
 *   $bodyClass  (string)  kelas tambahan pada <body>, contoh 'admin'
 */

require_once __DIR__ . '/auth.php';

session_boot();

$pageTitle = $pageTitle ?? '';
$bodyClass = $bodyClass ?? '';
$fullTitle = ($pageTitle !== '' ? $pageTitle . ' - ' : '') . (string) config('app_name');

// Tandai menu aktif berdasarkan nama file yang sedang dibuka
$currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$isActive = static fn (string $needle): string => str_contains($currentPath, $needle) ? ' class="active"' : '';

// style.css diberi ?v=waktu-ubah agar browser memuat ulang saat file berubah
$cssFile    = APP_ROOT . '/assets/style.css';
$cssVersion = is_file($cssFile) ? (string) filemtime($cssFile) : '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Oswald:wght@500;600&display=swap">
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>?v=<?= e($cssVersion) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('')) ?>"><?= e(config('app_name')) ?></a>
        <nav class="nav" aria-label="Navigasi utama">
            <a href="<?= e(url('')) ?>"<?= $currentPath === url('') || $currentPath === url('index.php') ? ' class="active"' : '' ?>>Beranda</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= e(url('admin/')) ?>"<?= $isActive('/admin/') ?>>Kelola Layanan</a>
                <form class="inline" method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">Logout (<?= e($_SESSION['admin']['user'] ?? '') ?>)</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>"<?= $isActive('login.php') ?>>Login Admin</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
<?php foreach (flash_get() as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
