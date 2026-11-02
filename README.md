# Barbershop Native

Aplikasi web barbershop sederhana dengan **PHP native**, **PDO**, dan **MySQL**, tanpa framework.
Pengunjung melihat daftar harga layanan di halaman utama, sedangkan admin mengelola layanan
(tambah, lihat, ubah, hapus) setelah login.

Dibuat untuk praktikum **Komputasi Awan** (Semester Ganjil 2026-2027) oleh **Sulthoni**,
dan dirancang agar kode yang sama berjalan di **XAMPP** (laptop) maupun **Azure App Service**.

## Fitur

- **Halaman utama**: daftar harga layanan aktif, diurutkan dari termurah.
- **Login admin**: satu akun admin, dengan batas percobaan login.
- **CRUD layanan** (Create, Read, Update, Delete): nama, deskripsi, harga, durasi, dan status aktif/nonaktif.
- **Layanan nonaktif** tetap tersimpan di database tetapi tidak tampil di halaman utama.
- **Endpoint `health.php`**: memeriksa koneksi database (detail hanya tampil saat mode debug).

## Teknologi

| Bagian | Yang dipakai |
|---|---|
| Bahasa | PHP 8.2 atau lebih baru (tanpa framework) |
| Database | MySQL / MariaDB lewat PDO |
| Tampilan | HTML dan CSS biasa (tanpa JavaScript framework) |
| Server lokal | XAMPP (Apache, PHP, MariaDB) |
| Server cloud | Azure App Service (Linux) dan Azure Database for MySQL |

## Struktur folder

```
barbershop-native/
├── index.php                     Halaman utama (daftar harga)
├── login.php                     Form login admin
├── logout.php                    Logout (hanya POST + token CSRF)
├── health.php                    Pemeriksaan koneksi database
├── admin/
│   ├── index.php                 Daftar semua layanan
│   ├── create.php                Tambah layanan
│   ├── edit.php                  Ubah layanan
│   └── delete.php                Hapus layanan (hanya POST + token CSRF)
├── includes/
│   ├── config.php                Pembaca pengaturan (default, file lokal, environment)
│   ├── config.local.example.php  Contoh pengaturan untuk laptop
│   ├── db.php                    Koneksi PDO (mendukung SSL untuk Azure)
│   ├── auth.php                  Sesi, login, token CSRF, fungsi bantu tampilan
│   ├── service_form.php          Validasi dan form layanan (dipakai create dan edit)
│   ├── header.php                Bagian atas halaman dan navigasi
│   └── footer.php                Bagian bawah halaman
├── assets/
│   └── style.css                 Seluruh tampilan
└── database/
    └── schema.sql                Tabel services dan 5 data contoh
```

## Menjalankan di XAMPP

1. Letakkan folder proyek di `C:\xampp\htdocs\barbershop-native`.
2. Buka **XAMPP Control Panel**, lalu Start **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin`, buat database `barbershop_native`
   (collation `utf8mb4_unicode_ci`), lalu tab **Import** dan pilih `database/schema.sql`.
4. Salin file pengaturan lokal. Di CMD:

   ```
   cd C:\xampp\htdocs\barbershop-native\includes
   copy config.local.example.php config.local.php
   ```

5. Buka `config.local.php`, lalu ganti `admin_pass` dengan password pilihanmu
   (minimal 8 karakter, huruf dan angka).
6. Buka `http://localhost/barbershop-native/`.
   Halaman admin ada di `http://localhost/barbershop-native/login.php`.

Untuk memeriksa koneksi database, buka `http://localhost/barbershop-native/health.php`.
Hasilnya harus `"status": "ok"`.

## Pengaturan

Pengaturan dibaca dari tiga lapis. Lapis yang lebih bawah menimpa yang di atasnya:

1. Nilai bawaan di `includes/config.php` (cocok untuk XAMPP).
2. `includes/config.local.php` (khusus laptop, **tidak ikut Git**).
3. Environment variable (dipakai di Azure lewat *Application settings*).

| Kunci di `config.local.php` | Environment variable | Bawaan | Keterangan |
|---|---|---|---|
| `app_name` | `APP_NAME` | `Barbershop` | Nama yang tampil di header dan judul tab |
| `app_env` | `APP_ENV` | `production` | Nama lingkungan |
| `app_debug` | `APP_DEBUG` | `false` | `true` menampilkan error dan detail di `health.php` |
| `timezone` | `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu aplikasi |
| `base_path` | `APP_BASE_PATH` | otomatis | Folder aplikasi di web server (dideteksi otomatis) |
| `db_host` | `DB_HOST` | `127.0.0.1` | Alamat server database |
| `db_port` | `DB_PORT` | `3306` | Port database |
| `db_name` | `DB_NAME` | `barbershop_native` | Nama database |
| `db_user` | `DB_USER` | `root` | Pengguna database |
| `db_pass` | `DB_PASSWORD` | kosong | Password database |
| `db_ssl` | `DB_SSL` | `false` | `true` wajib untuk Azure Database for MySQL |
| `db_ssl_ca` | `DB_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` | Berkas sertifikat CA |
| `db_ssl_verify` | `DB_SSL_VERIFY` | `true` | Memverifikasi sertifikat server database |
| `admin_user` | `ADMIN_USER` | `admin` | Username admin |
| `admin_pass` | `ADMIN_PASSWORD` | kosong | Password admin. **Kosong berarti login dinonaktifkan** |

## Catatan keamanan

- **Rahasia tidak masuk Git.** `includes/config.local.php` ada di `.gitignore`.
  Hanya `config.local.example.php` (berisi placeholder) yang ikut repository.
- **Login nonaktif bila password kosong**, sehingga aplikasi tidak pernah bisa dimasuki
  dengan password bawaan, termasuk di Azure.
- **Perlindungan CSRF** pada semua form POST, termasuk login, logout, dan hapus.
- **Prepared statement asli** (`EMULATE_PREPARES = false`) untuk semua query,
  sehingga aman dari SQL injection.
- **Semua data dinamis di-escape** sebelum tampil di HTML (fungsi `e()`), sehingga aman dari XSS.
- **Sesi aman**: cookie `HttpOnly` dan `SameSite=Lax`, `Secure` otomatis di HTTPS,
  ID sesi diganti saat login, dan logout otomatis setelah 30 menit tidak aktif.
- **Login dibatasi**: 5 kali gagal mengunci login selama 60 detik. Pembatasan ini tersimpan di sesi,
  jadi cukup untuk keperluan praktikum, tetapi untuk produksi sebaiknya berbasis IP atau database.
- **Pesan error umum** untuk pengguna. Rincian teknis hanya masuk ke log server.

## Deploy ke Azure

Bagian ini akan diisi setelah deploy ke Azure App Service dan Azure Database for MySQL selesai.
