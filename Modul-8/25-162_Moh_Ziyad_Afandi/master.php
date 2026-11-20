<?php
// Halaman Data Master (barang, supplier, pelanggan, user) dalam satu file.
// Pilih modul lewat ?m=barang | supplier | pelanggan | user
require_once __DIR__ . '/fungsi.php';
wajib_level(1); // Data Master hanya boleh diakses owner

$semua = [
'barang' => [
    'judul'   => 'Data Barang',
    'tabel'   => 'barang',
    'pk'      => 'id_barang',
    'list_sql' => "SELECT b.*, s.nama_supplier
                   FROM barang b JOIN supplier s ON s.id_supplier = b.id_supplier
                   ORDER BY b.id_barang",
    'columns' => [
        'nama_barang'   => 'Nama Barang',
        'nama_supplier' => 'Supplier',
        'harga'         => 'Harga',
        'stok'          => 'Stok',
    ],
    'rupiah' => ['harga'],
    'fields' => [
        'nama_barang' => ['label' => 'Nama Barang', 'type' => 'text', 'required' => true, 'max' => 100],
        'id_supplier' => ['label' => 'Supplier', 'type' => 'select', 'required' => true,
                          'options_sql' => "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier"],
        'harga'       => ['label' => 'Harga (Rp)', 'type' => 'number', 'required' => true],
        'stok'        => ['label' => 'Stok', 'type' => 'number', 'required' => true],
    ],
],
'supplier' => [
    'judul'   => 'Data Supplier',
    'tabel'   => 'supplier',
    'pk'      => 'id_supplier',
    'list_sql' => "SELECT * FROM supplier ORDER BY id_supplier",
    'columns' => [
        'nama_supplier' => 'Nama Supplier',
        'telepon'       => 'Telepon',
        'alamat'        => 'Alamat',
    ],
    'fields' => [
        'nama_supplier' => ['label' => 'Nama Supplier', 'type' => 'text', 'required' => true, 'max' => 100],
        'telepon'       => ['label' => 'Telepon', 'type' => 'text', 'max' => 20],
        'alamat'        => ['label' => 'Alamat', 'type' => 'textarea', 'max' => 255],
    ],
],
'pelanggan' => [
    'judul'   => 'Data Pelanggan',
    'tabel'   => 'pelanggan',
    'pk'      => 'id_pelanggan',
    'list_sql' => "SELECT * FROM pelanggan ORDER BY id_pelanggan",
    'columns' => [
        'nama_pelanggan' => 'Nama Pelanggan',
        'telepon'        => 'Telepon',
        'alamat'         => 'Alamat',
    ],
    'fields' => [
        'nama_pelanggan' => ['label' => 'Nama Pelanggan', 'type' => 'text', 'required' => true, 'max' => 100],
        'telepon'        => ['label' => 'Telepon', 'type' => 'text', 'max' => 20],
        'alamat'         => ['label' => 'Alamat', 'type' => 'textarea', 'max' => 255],
    ],
],
'user' => [
    'judul'   => 'Data User',
    'tabel'   => 'user',
    'pk'      => 'id_user',
    'cegah_hapus_diri' => true,
    'list_sql' => "SELECT id_user, nama, username, level,
                          IF(level = 1, 'Owner', 'Kasir') AS level_text
                   FROM user ORDER BY id_user",
    'columns' => [
        'nama'       => 'Nama',
        'username'   => 'Username',
        'level_text' => 'Level',
    ],
    'fields' => [
        'nama'     => ['label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100],
        'username' => ['label' => 'Username', 'type' => 'text', 'required' => true, 'max' => 60, 'unique' => true],
        'password' => ['label' => 'Password', 'type' => 'password', 'required' => true],
        'level'    => ['label' => 'Level', 'type' => 'select', 'required' => true,
                       'options' => [1 => '1 - Owner', 2 => '2 - Kasir']],
    ],
],
];

$m = $_GET['m'] ?? 'barang';
if (!isset($semua[$m])) { $m = 'barang'; }
$cfg = $semua[$m];
$cfg['halaman'] = "master.php?m=$m";

$tabel    = $cfg['tabel'];
$pk       = $cfg['pk'];
$halaman  = $cfg['halaman'];
$fields   = $cfg['fields'];
$id_login = (int) $_SESSION['id_user'];

// Isi pilihan dropdown yang diambil dari database (mis. supplier pada barang)
foreach ($fields as $k => $f) {
    if (isset($f['options_sql'])) {
        $opt = [];
        foreach (db_all($f['options_sql']) as $r) {
            $v = array_values($r);
            $opt[$v[0]] = $v[1];
        }
        $fields[$k]['options'] = $opt;
    }
}

// ---------- HAPUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);

    if (!empty($cfg['cegah_hapus_diri']) && $id === $id_login) {
        set_flash('danger', 'Anda tidak bisa menghapus akun yang sedang dipakai login.');
    } else {
        try {
            db_exec("DELETE FROM `$tabel` WHERE `$pk` = ?", [$id]);
            set_flash('success', 'Data berhasil dihapus.');
        } catch (mysqli_sql_exception $ex) {
            set_flash('danger', 'Data tidak dapat dihapus karena sedang dipakai oleh data lain.');
        }
    }
    header("Location: $halaman");
    exit();
}

// ---------- TAMBAH / EDIT (simpan) ----------
$error = [];
$nilai = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $id   = (int) ($_POST['id'] ?? 0);
    $edit = $id > 0;
    $kolom = [];   // kolom => nilai yang akan disimpan

    foreach ($fields as $k => $f) {
        $v = trim($_POST[$k] ?? '');
        $nilai[$k] = ($f['type'] === 'password') ? '' : $v;

        // Password kosong saat edit = tidak diganti
        if ($f['type'] === 'password' && $edit && $v === '') {
            continue;
        }

        if ($v === '') {
            if (!empty($f['required'])) {
                $error[$k] = $f['label'] . " wajib diisi.";
            }
            $kolom[$k] = null;
            continue;
        }

        if (!empty($f['max']) && mb_strlen($v) > $f['max']) {
            $error[$k] = $f['label'] . " maksimal " . $f['max'] . " karakter.";
            continue;
        }

        if ($f['type'] === 'number' && (!ctype_digit($v) || strlen($v) > 9)) {
            $error[$k] = $f['label'] . " harus berupa angka bulat (0 atau lebih).";
            continue;
        }

        if ($f['type'] === 'select' && !isset($f['options'][$v])) {
            $error[$k] = $f['label'] . " tidak valid.";
            continue;
        }

        if ($f['type'] === 'password' && strlen($v) < 6) {
            $error[$k] = $f['label'] . " minimal 6 karakter.";
            continue;
        }

        if (!empty($f['unique'])) {
            $ada = db_one("SELECT 1 FROM `$tabel` WHERE `$k` = ? AND `$pk` <> ?", [$v, $id]);
            if ($ada) {
                $error[$k] = $f['label'] . " sudah dipakai.";
                continue;
            }
        }

        $kolom[$k] = ($f['type'] === 'password') ? password_hash($v, PASSWORD_DEFAULT) : $v;
    }

    if (!$error) {
        if ($edit) {
            $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($kolom)));
            db_exec("UPDATE `$tabel` SET $set WHERE `$pk` = ?", array_merge(array_values($kolom), [$id]));
            // Jika owner mengubah datanya sendiri, perbarui session
            if (!empty($cfg['cegah_hapus_diri']) && $id === $id_login) {
                $_SESSION['nama']     = $kolom['nama']     ?? $_SESSION['nama'];
                $_SESSION['username'] = $kolom['username'] ?? $_SESSION['username'];
                $_SESSION['level']    = (int) ($kolom['level'] ?? $_SESSION['level']);
            }
            set_flash('success', 'Data berhasil diperbarui.');
        } else {
            $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($kolom)));
            $qs   = implode(', ', array_fill(0, count($kolom), '?'));
            db_exec("INSERT INTO `$tabel` ($cols) VALUES ($qs)", array_values($kolom));
            set_flash('success', 'Data berhasil ditambahkan.');
        }
        header("Location: $halaman");
        exit();
    }
    $aksi = $edit ? 'edit' : 'tambah';
} else {
    $aksi = $_GET['aksi'] ?? 'list';
    $id   = (int) ($_GET['id'] ?? 0);

    if ($aksi === 'edit') {
        $row = db_one("SELECT * FROM `$tabel` WHERE `$pk` = ?", [$id]);
        if (!$row) {
            set_flash('danger', 'Data tidak ditemukan.');
            header("Location: $halaman");
            exit();
        }
        foreach ($fields as $k => $f) {
            $nilai[$k] = ($f['type'] === 'password') ? '' : ($row[$k] ?? '');
        }
    } elseif ($aksi !== 'tambah') {
        $aksi = 'list';
    }
}

$judul = $cfg['judul'];
$menu  = 'master';
layout_awal($judul, $menu);
?>

<?php if ($aksi === 'list'): ?>
    <?php $data = db_all($cfg['list_sql']); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?= e($cfg['judul']) ?></h4>
        <a href="<?= e($halaman) ?>&aksi=tambah" class="btn btn-primary btn-sm">+ Tambah</a>
    </div>

    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">No</th>
                    <?php foreach ($cfg['columns'] as $label): ?>
                        <th><?= e($label) ?></th>
                    <?php endforeach; ?>
                    <th style="width:150px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="<?= count($cfg['columns']) + 2 ?>" class="text-center text-muted py-4">Belum ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($data as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <?php foreach ($cfg['columns'] as $k => $label): ?>
                        <td><?= e(in_array($k, $cfg['rupiah'] ?? [], true) ? rupiah($row[$k]) : $row[$k]) ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="<?= e($halaman) ?>&aksi=edit&id=<?= (int) $row[$pk] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Yakin hapus data ini?');">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= (int) $row[$pk] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>

<?php else: ?>

    <h4 class="mb-3"><?= $aksi === 'edit' ? 'Edit' : 'Tambah' ?> <?= e($cfg['judul']) ?></h4>

    <div class="card shadow-sm" style="max-width:640px;"><div class="card-body">
        <form method="post" novalidate>
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?= $aksi === 'edit' ? (int) $id : 0 ?>">

            <?php foreach ($fields as $k => $f): ?>
                <div class="mb-3">
                    <label class="form-label" for="<?= e($k) ?>">
                        <?= e($f['label']) ?>
                        <?php if (!empty($f['required']) && !($f['type'] === 'password' && $aksi === 'edit')): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>

                    <?php $cls = 'form-control' . (isset($error[$k]) ? ' is-invalid' : ''); ?>

                    <?php if ($f['type'] === 'select'): ?>
                        <select class="form-select<?= isset($error[$k]) ? ' is-invalid' : '' ?>" id="<?= e($k) ?>" name="<?= e($k) ?>">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($f['options'] as $val => $teks): ?>
                                <option value="<?= e($val) ?>" <?= (string) ($nilai[$k] ?? '') === (string) $val ? 'selected' : '' ?>><?= e($teks) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($f['type'] === 'textarea'): ?>
                        <textarea class="<?= $cls ?>" id="<?= e($k) ?>" name="<?= e($k) ?>" rows="3"><?= e($nilai[$k] ?? '') ?></textarea>
                    <?php elseif ($f['type'] === 'password'): ?>
                        <input type="password" class="<?= $cls ?>" id="<?= e($k) ?>" name="<?= e($k) ?>" autocomplete="new-password">
                        <?php if ($aksi === 'edit'): ?>
                            <div class="form-text">Kosongkan jika tidak ingin mengganti password.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <input type="<?= $f['type'] === 'number' ? 'number' : 'text' ?>" <?= $f['type'] === 'number' ? 'min="0"' : '' ?>
                               class="<?= $cls ?>" id="<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e($nilai[$k] ?? '') ?>">
                    <?php endif; ?>

                    <?php if (isset($error[$k])): ?>
                        <div class="invalid-feedback d-block"><?= e($error[$k]) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="<?= e($halaman) ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div></div>

<?php endif; ?>

<?php layout_akhir(); ?>
