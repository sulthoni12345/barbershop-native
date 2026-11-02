<?php

declare(strict_types=1);

/**
 * includes/auth.php
 *
 * Fondasi keamanan halaman admin:
 *   - Sesi aman (HttpOnly, SameSite, Secure bila HTTPS, batas waktu tidak aktif)
 *   - Token CSRF untuk setiap form POST
 *   - Login/logout admin (perbandingan waktu-konstan, ID sesi diganti saat login)
 *   - Pembatasan percobaan login (5 gagal -> terkunci 60 detik)
 *   - Flash message, redirect, dan e() untuk mencegah XSS di tampilan
 */

require_once __DIR__ . '/config.php';

const SESSION_IDLE_TIMEOUT = 1800; // 30 menit tanpa aktivitas -> otomatis logout
const LOGIN_MAX_ATTEMPTS   = 5;
const LOGIN_LOCK_SECONDS   = 60;

/* ---------------------------------------------------------------------------
 * Pengaman tampilan
 * ------------------------------------------------------------------------- */

/** Escape teks sebelum dicetak ke HTML. Pakai di SEMUA data dinamis: <?= e($x) ?> */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format rupiah, contoh: rupiah(50000) -> "Rp 50.000" */
function rupiah(int|float|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

/** Ubah menit jadi teks, contoh: 45 -> "45 menit", 90 -> "1 jam 30 menit" */
function durasi(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' menit';
    }

    $hours = intdiv($minutes, 60);
    $rest  = $minutes % 60;

    return $rest === 0 ? $hours . ' jam' : $hours . ' jam ' . $rest . ' menit';
}

/** Redirect ke path internal aplikasi lalu hentikan skrip. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ---------------------------------------------------------------------------
 * Sesi
 * ------------------------------------------------------------------------- */

/** True jika request datang lewat HTTPS (termasuk di belakang proxy Azure). */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/** Memulai sesi dengan pengaturan aman (aman dipanggil berkali-kali). */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('BARBERSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() === '' ? '/' : base_path() . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Logout otomatis bila terlalu lama tidak aktif
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash_set('error', 'Sesi berakhir karena tidak aktif. Silakan login lagi.');
    }
    $_SESSION['last_activity'] = $now;
}

/* ---------------------------------------------------------------------------
 * Flash message (pesan sekali tampil)
 * ------------------------------------------------------------------------- */

function flash_set(string $type, string $message): void
{
    session_boot();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Mengambil lalu menghapus semua flash message. */
function flash_get(): array
{
    session_boot();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

/* ---------------------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------------------- */

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

/** Input tersembunyi untuk ditaruh di dalam <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    session_boot();

    return is_string($token) && $token !== ''
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/** Wajib dipanggil di awal setiap handler POST. Token salah -> HTTP 419. */
function csrf_check(): void
{
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Permintaan ditolak: token keamanan tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------------------------------------------------------------------------
 * Login / logout
 * ------------------------------------------------------------------------- */

function is_logged_in(): bool
{
    session_boot();

    return !empty($_SESSION['admin']);
}

/** Taruh di baris awal setiap halaman admin. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

/** Login dinonaktifkan bila admin_pass kosong (mencegah password bawaan di Azure). */
function login_enabled(): bool
{
    return (string) config('admin_pass') !== '';
}

/** Sisa detik penguncian login; 0 jika tidak terkunci. */
function login_locked_seconds(): int
{
    session_boot();
    $until = (int) ($_SESSION['login_lock_until'] ?? 0);

    return max(0, $until - time());
}

/**
 * Mencoba login. Mengembalikan null jika berhasil, atau pesan error jika gagal.
 */
function login_attempt(string $username, string $password): ?string
{
    session_boot();

    if (!login_enabled()) {
        return 'Login admin belum diaktifkan. Atur ADMIN_PASSWORD terlebih dahulu.';
    }

    if (($wait = login_locked_seconds()) > 0) {
        return "Terlalu banyak percobaan. Coba lagi dalam {$wait} detik.";
    }

    // Kedua perbandingan selalu dijalankan agar waktunya tidak membocorkan mana yang salah
    $userOk = hash_equals((string) config('admin_user'), $username);
    $passOk = hash_equals((string) config('admin_pass'), $password);

    if ($userOk && $passOk) {
        session_regenerate_id(true); // cegah session fixation
        unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
        $_SESSION['admin']         = ['user' => $username, 'since' => time()];
        $_SESSION['csrf']          = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();

        return null;
    }

    $fails = (int) ($_SESSION['login_fails'] ?? 0) + 1;
    $_SESSION['login_fails'] = $fails;

    if ($fails >= LOGIN_MAX_ATTEMPTS) {
        $_SESSION['login_lock_until'] = time() + LOGIN_LOCK_SECONDS;
        $_SESSION['login_fails']      = 0;
        error_log('[auth] login gagal berulang dari ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));

        return 'Terlalu banyak percobaan. Coba lagi dalam ' . LOGIN_LOCK_SECONDS . ' detik.';
    }

    return 'Username atau password salah.';
}

function logout(): void
{
    session_boot();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
