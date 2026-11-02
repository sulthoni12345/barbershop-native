<?php

declare(strict_types=1);

/**
 * logout.php
 * Hanya menerima POST dengan token CSRF yang valid (tombol Logout di header).
 * Membuka alamat ini langsung lewat browser (GET) tidak melakukan apa-apa.
 */

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('');
}

csrf_check();
logout();

flash_set('success', 'Kamu sudah logout.');
redirect('login.php');
