<?php

/**
 * includes/config.local.example.php
 *
 * Contoh pengaturan untuk laptop (XAMPP).
 * Salin file ini menjadi  includes/config.local.php  lalu sesuaikan.
 * File config.local.php TIDAK ikut ke Git (sudah ada di .gitignore).
 *
 * Di Azure file ini tidak dipakai; pengaturan diisi lewat
 * Application settings pada Web App.
 */

return [
    'app_env'    => 'local',
    'app_debug'  => true,

    // MySQL bawaan XAMPP: user root tanpa password
    'db_host'    => '127.0.0.1',
    'db_port'    => 3306,
    'db_name'    => 'barbershop_native',
    'db_user'    => 'root',
    'db_pass'    => '',

    // Akun login halaman admin (WAJIB diganti)
    'admin_user' => 'admin',
    'admin_pass' => 'GantiPasswordIni123',
];
