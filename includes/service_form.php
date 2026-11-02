<?php

declare(strict_types=1);

/**
 * includes/service_form.php
 * Validasi dan tampilan form layanan, dipakai bersama oleh
 * admin/create.php (Tambah) dan admin/edit.php (Ubah).
 */

const SERVICE_NAME_MAX     = 100;       // VARCHAR(100)
const SERVICE_DESC_MAX     = 255;       // VARCHAR(255)
const SERVICE_PRICE_MAX    = 10000000;  // Rp 10 juta
const SERVICE_DURATION_MAX = 1440;      // 24 jam

/** Jumlah karakter (bukan byte), aman untuk huruf non-Latin dan emoji. */
function text_length(string $text): int
{
    return (int) preg_match_all('/./us', $text);
}

/**
 * Memvalidasi input form layanan.
 * Hasil: [bersih, error] -> $clean siap disimpan, $errors['field'] = pesan.
 */
function validate_service(array $input): array
{
    $name     = trim((string) ($input['name'] ?? ''));
    $desc     = trim((string) ($input['description'] ?? ''));
    $price    = trim((string) ($input['price'] ?? ''));
    $duration = trim((string) ($input['duration_minutes'] ?? ''));
    $active   = !empty($input['is_active']);

    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Nama layanan wajib diisi.';
    } elseif (text_length($name) > SERVICE_NAME_MAX) {
        $errors['name'] = 'Nama layanan maksimal ' . SERVICE_NAME_MAX . ' karakter.';
    } elseif (preg_match('/[\x00-\x1F\x7F]/', $name)) {
        $errors['name'] = 'Nama layanan tidak boleh berisi baris baru atau karakter kontrol.';
    }

    if (text_length($desc) > SERVICE_DESC_MAX) {
        $errors['description'] = 'Deskripsi maksimal ' . SERVICE_DESC_MAX . ' karakter.';
    } elseif (preg_match('/[\x00-\x1F\x7F]/', $desc)) {
        $errors['description'] = 'Deskripsi tidak boleh berisi baris baru atau karakter kontrol.';
    }

    if ($price === '') {
        $errors['price'] = 'Harga wajib diisi.';
    } elseif (!ctype_digit($price) || strlen($price) > 9) {
        $errors['price'] = 'Harga harus berupa angka bulat tanpa titik, koma, atau huruf. Contoh: 35000.';
    } elseif ((int) $price < 1 || (int) $price > SERVICE_PRICE_MAX) {
        $errors['price'] = 'Harga harus antara Rp 1 dan ' . rupiah(SERVICE_PRICE_MAX) . '.';
    }

    if ($duration === '') {
        $errors['duration_minutes'] = 'Durasi wajib diisi.';
    } elseif (!ctype_digit($duration) || strlen($duration) > 5) {
        $errors['duration_minutes'] = 'Durasi harus berupa angka bulat dalam menit. Contoh: 30.';
    } elseif ((int) $duration < 1 || (int) $duration > SERVICE_DURATION_MAX) {
        $errors['duration_minutes'] = 'Durasi harus antara 1 dan ' . SERVICE_DURATION_MAX . ' menit.';
    }

    $clean = [
        'name'             => $name,
        'description'      => $desc === '' ? null : $desc,
        'price'            => ctype_digit($price) && strlen($price) <= 9 ? (int) $price : 0,
        'duration_minutes' => ctype_digit($duration) && strlen($duration) <= 5 ? (int) $duration : 0,
        'is_active'        => $active ? 1 : 0,
    ];

    return [$clean, $errors];
}

/** Menampilkan satu pesan error di bawah field. */
function field_error(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p class="field-error" id="err-' . e($field) . '">' . e($errors[$field]) . '</p>';
}

/** Atribut untuk input yang error (kelas + aria). */
function field_attrs(array $errors, string $field): string
{
    return isset($errors[$field])
        ? ' class="invalid" aria-invalid="true" aria-describedby="err-' . e($field) . '"'
        : '';
}

/**
 * Menampilkan form layanan.
 *   $values  : nilai yang tampil di field (string/angka apa adanya)
 *   $errors  : hasil validate_service()
 *   $action  : URL tujuan POST
 *   $submit  : teks tombol simpan
 *   $id      : isi hanya pada form Edit
 */
function render_service_form(array $values, array $errors, string $action, string $submit, ?int $id = null): void
{
    ?>
    <form method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>
        <?php if ($id !== null): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Nama layanan</label>
            <input type="text" id="name" name="name" value="<?= e($values['name'] ?? '') ?>"
                   maxlength="<?= SERVICE_NAME_MAX ?>" required<?= field_attrs($errors, 'name') ?>>
            <?= field_error($errors, 'name') ?>
        </div>

        <div class="form-group">
            <label for="description">Deskripsi <span class="optional">(boleh dikosongkan)</span></label>
            <textarea id="description" name="description" rows="3"
                      maxlength="<?= SERVICE_DESC_MAX ?>"<?= field_attrs($errors, 'description') ?>><?= e($values['description'] ?? '') ?></textarea>
            <?= field_error($errors, 'description') ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Harga (Rp)</label>
                <input type="text" id="price" name="price" inputmode="numeric" value="<?= e($values['price'] ?? '') ?>"
                       placeholder="35000" required<?= field_attrs($errors, 'price') ?>>
                <?= field_error($errors, 'price') ?>
            </div>
            <div class="form-group">
                <label for="duration_minutes">Durasi (menit)</label>
                <input type="text" id="duration_minutes" name="duration_minutes" inputmode="numeric"
                       value="<?= e($values['duration_minutes'] ?? '') ?>" placeholder="30" required<?= field_attrs($errors, 'duration_minutes') ?>>
                <?= field_error($errors, 'duration_minutes') ?>
            </div>
        </div>

        <div class="form-group">
            <label class="check">
                <input type="checkbox" name="is_active" value="1"<?= !empty($values['is_active']) ? ' checked' : '' ?>>
                Tampilkan di halaman utama
            </label>
            <p class="help">Layanan nonaktif tetap tersimpan, hanya tidak muncul di daftar harga.</p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn"><?= e($submit) ?></button>
            <a class="btn btn-outline" href="<?= e(url('admin/')) ?>">Batal</a>
        </div>
    </form>
    <?php
}
