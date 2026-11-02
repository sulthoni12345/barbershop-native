<?php

declare(strict_types=1);

/**
 * includes/db.php
 *
 * Koneksi database (PDO + MySQL).
 *   - Memakai prepared statement asli (EMULATE_PREPARES = false)
 *   - Mendukung SSL, yang WAJIB untuk Azure Database for MySQL
 *     (aktifkan lewat DB_SSL=true di Application settings)
 *   - Pesan error ke pengguna dibuat umum; rincian teknis masuk ke log
 */

require_once __DIR__ . '/config.php';

/**
 * Konstanta SSL milik driver MySQL. PHP 8.4+ memakai Pdo\Mysql::ATTR_*,
 * sedangkan PDO::MYSQL_ATTR_* dinyatakan usang (deprecated) di PHP 8.5.
 * Fungsi ini memilih yang tepat agar jalan di PHP 8.2 sampai 8.5.
 */
function pdo_mysql_attr(string $name): int
{
    $new = 'Pdo\\Mysql::ATTR_' . $name;

    return defined($new) ? (int) constant($new) : (int) constant('PDO::MYSQL_ATTR_' . $name);
}

/** Menyusun DSN setelah memvalidasi host dan nama database. */
function db_dsn(): string
{
    $host = (string) config('db_host');
    $name = (string) config('db_name');
    $port = (int) config('db_port');

    if (!preg_match('/^[A-Za-z0-9._:-]+$/', $host) || !preg_match('/^[A-Za-z0-9_$-]+$/', $name) || $port < 1 || $port > 65535) {
        throw new RuntimeException('Konfigurasi database tidak valid.');
    }

    return sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
}

/** Menyusun opsi PDO, termasuk SSL bila diaktifkan. */
function db_options(): array
{
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    if (config('db_ssl')) {
        $options[pdo_mysql_attr('SSL_CA')]                  = (string) config('db_ssl_ca');
        $options[pdo_mysql_attr('SSL_VERIFY_SERVER_CERT')] = (bool) config('db_ssl_verify');
    }

    return $options;
}

/** Mengembalikan satu koneksi PDO yang dipakai bersama selama request. */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(db_dsn(), (string) config('db_user'), (string) config('db_pass'), db_options());

        // Samakan zona waktu sesi MySQL dengan aplikasi (Azure MySQL memakai UTC)
        $offset = (new DateTime('now', new DateTimeZone((string) config('timezone'))))->format('P');
        $pdo->exec('SET time_zone = ' . $pdo->quote($offset));
    } catch (PDOException $e) {
        error_log('[db] koneksi gagal: ' . $e->getMessage());
        $pdo = null;

        throw new RuntimeException('Tidak dapat terhubung ke database.', 0, $e);
    }

    return $pdo;
}

/**
 * Ringkasan kesehatan koneksi (dipakai health.php).
 * Hasil: ['ok' => bool, 'server' => ..., 'ssl' => ..., 'services' => ..., 'error' => ...]
 */
function db_status(): array
{
    try {
        $pdo = db();

        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $cipher  = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch();
        $count   = null;

        try {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
        } catch (PDOException) {
            // tabel belum diimpor
        }

        return [
            'ok'       => true,
            'server'   => $version,
            'ssl'      => ($cipher && ($cipher['Value'] ?? '') !== '') ? $cipher['Value'] : null,
            'services' => $count,
        ];
    } catch (Throwable $e) {
        $previous = $e->getPrevious();

        return ['ok' => false, 'error' => ($previous ?? $e)->getMessage()];
    }
}
