<?php

declare(strict_types=1);

/**
 * includes/config.php
 *
 * Pengaturan aplikasi. Urutan prioritas (yang terakhir menang):
 *   1. Nilai bawaan di file ini
 *   2. includes/config.local.php  -> khusus laptop, TIDAK di-commit ke Git
 *   3. Environment variable       -> Azure App Service (Application settings)
 *
 * Dengan urutan ini kode yang sama berjalan di XAMPP maupun di Azure
 * tanpa perlu diubah. Environment variable yang kosong dianggap tidak diisi.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/** Membaca environment variable; null jika tidak ada atau kosong. */
function env_value(string $key): ?string
{
    $value = getenv($key);

    if ($value === false) {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? false;
    }

    return ($value === false || $value === '') ? null : (string) $value;
}

/** Mengubah "true", "1", "yes", "on" (tanpa membedakan huruf besar) menjadi boolean. */
function to_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

/** Menyusun seluruh konfigurasi dari ketiga sumber. */
function load_config(): array
{
    // 1. Nilai bawaan
    $config = [
        'app_name'      => 'Barbershop',
        'app_env'       => 'production',
        'app_debug'     => false,
        'timezone'      => 'Asia/Jakarta',
        'base_path'     => null,                                  // null = dideteksi otomatis
        'db_host'       => '127.0.0.1',
        'db_port'       => 3306,
        'db_name'       => 'barbershop_native',
        'db_user'       => 'root',
        'db_pass'       => '',
        'db_ssl'        => false,                                 // Azure MySQL wajib SSL
        'db_ssl_ca'     => '/etc/ssl/certs/ca-certificates.crt',  // bundel CA bawaan Linux
        'db_ssl_verify' => true,
        'admin_user'    => 'admin',
        'admin_pass'    => '',                                    // kosong = login dinonaktifkan
    ];

    // 2. File lokal (jika ada)
    $localFile = __DIR__ . '/config.local.php';
    if (is_file($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            $config = array_replace($config, $local);
        }
    }

    // 3. Environment variable
    $map = [
        'APP_NAME'       => 'app_name',
        'APP_ENV'        => 'app_env',
        'APP_DEBUG'      => 'app_debug',
        'APP_TIMEZONE'   => 'timezone',
        'APP_BASE_PATH'  => 'base_path',
        'DB_HOST'        => 'db_host',
        'DB_PORT'        => 'db_port',
        'DB_NAME'        => 'db_name',
        'DB_USER'        => 'db_user',
        'DB_PASSWORD'    => 'db_pass',
        'DB_SSL'         => 'db_ssl',
        'DB_SSL_CA'      => 'db_ssl_ca',
        'DB_SSL_VERIFY'  => 'db_ssl_verify',
        'ADMIN_USER'     => 'admin_user',
        'ADMIN_PASSWORD' => 'admin_pass',
    ];

    foreach ($map as $envKey => $configKey) {
        $value = env_value($envKey);
        if ($value !== null) {
            $config[$configKey] = $value;
        }
    }

    // Pastikan tipe data benar
    $config['app_debug']     = to_bool($config['app_debug']);
    $config['db_ssl']        = to_bool($config['db_ssl']);
    $config['db_ssl_verify'] = to_bool($config['db_ssl_verify']);
    $config['db_port']       = (int) $config['db_port'];

    return $config;
}

/** Mengambil satu nilai konfigurasi, contoh: config('db_host'). */
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= load_config();

    return $config[$key] ?? $default;
}

/**
 * Folder tempat aplikasi berada, relatif terhadap document root web server.
 *   XAMPP : "/barbershop-native"  (project di htdocs/barbershop-native)
 *   Azure : ""                    (project di root /home/site/wwwroot)
 * Bisa dipaksa lewat APP_BASE_PATH.
 */
function base_path(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $override = config('base_path');
    if ($override !== null) {
        $rel = trim((string) $override, '/');

        return $base = ($rel === '' ? '' : '/' . $rel);
    }

    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $appRoot = str_replace('\\', '/', realpath(APP_ROOT) ?: APP_ROOT);

    // strncasecmp: Windows tidak membedakan huruf besar/kecil pada nama folder
    if ($docRoot !== '' && strncasecmp($appRoot, $docRoot, strlen($docRoot)) === 0) {
        $rel = trim(substr($appRoot, strlen($docRoot)), '/');

        return $base = ($rel === '' ? '' : '/' . $rel);
    }

    return $base = '';
}

/** Membuat URL yang benar di XAMPP maupun Azure, contoh: url('admin/'). */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

// Pengaturan PHP berdasarkan konfigurasi
date_default_timezone_set((string) config('timezone'));
error_reporting(E_ALL);
ini_set('display_errors', config('app_debug') ? '1' : '0');
ini_set('log_errors', '1');
