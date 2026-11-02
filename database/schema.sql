-- =====================================================================
-- database/schema.sql - Barbershop Native (PHP native + MySQL)
--
-- Cara pakai:
--   Lokal (XAMPP)   : phpMyAdmin -> buat database "barbershop_native"
--                     -> tab Import -> pilih file ini -> Go
--   Azure MySQL     : jalankan terhadap database yang sudah dibuat (Fase 4)
--
-- Aman dijalankan berulang: tabel memakai IF NOT EXISTS dan data contoh
-- hanya dimasukkan jika tabel masih kosong.
-- =====================================================================

CREATE TABLE IF NOT EXISTS services (
    id               INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    name             VARCHAR(100)      NOT NULL,
    description      VARCHAR(255)      NULL,
    price            INT UNSIGNED      NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active        TINYINT(1)        NOT NULL DEFAULT 1,
    created_at       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_services_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data contoh (hanya jika tabel masih kosong)
INSERT INTO services (name, description, price, duration_minutes)
SELECT t.name, t.description, t.price, t.duration_minutes
FROM (
    SELECT 'Potong Rambut Classic' AS name,
           'Potongan rapi sesuai bentuk wajah, termasuk styling akhir.' AS description,
           35000 AS price, 30 AS duration_minutes
    UNION ALL
    SELECT 'Potong + Keramas',
           'Potong rambut dilanjutkan keramas dan pijat kepala ringan.',
           50000, 45
    UNION ALL
    SELECT 'Cukur Jenggot & Kumis',
           'Merapikan jenggot dan kumis dengan handuk hangat.',
           25000, 20
    UNION ALL
    SELECT 'Hair Coloring',
           'Pewarnaan rambut dengan produk berkualitas, konsultasi warna gratis.',
           150000, 90
    UNION ALL
    SELECT 'Paket Komplit',
           'Potong rambut, cukur jenggot, keramas, dan pijat relaksasi.',
           90000, 60
) AS t
WHERE NOT EXISTS (SELECT 1 FROM services);
