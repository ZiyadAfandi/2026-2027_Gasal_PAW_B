<?php
require_once 'fungsi.php';
wajib_login(); // owner & kasir boleh mengakses

$barang    = db_all("SELECT id_barang, nama_barang, harga, stok FROM barang WHERE stok > 0 ORDER BY nama_barang");
$pelanggan = db_all("SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan");
$error     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelanggan = $_POST['id_pelanggan'] ?? '';
    $ids          = $_POST['id_barang'] ?? [];
    $qtys         = $_POST['qty'] ?? [];

    // Pelanggan boleh kosong (pembeli umum)
    if ($id_pelanggan === '') {
        $id_pelanggan = null;
    } elseif (!db_one("SELECT 1 FROM pelanggan WHERE id_pelanggan = ?", [$id_pelanggan])) {
        $error[] = "Pelanggan tidak valid.";
    }

    // Gabungkan item yang sama & validasi jumlah
    $item = [];
    foreach ((array) $ids as $i => $idb) {
        $q = trim($qtys[$i] ?? '');
        if ($idb === '' && $q === '') {
            continue; // baris kosong diabaikan
        }
        if ($idb === '' || !ctype_digit((string) $idb) || !ctype_digit($q) || (int) $q < 1) {
            $error[] = "Baris " . ($i + 1) . ": pilih barang dan isi jumlah (minimal 1).";
            continue;
        }
        $item[(int) $idb] = ($item[(int) $idb] ?? 0) + (int) $q;
    }
    if (!$item && !$error) {
        $error[] = "Tambahkan minimal satu barang.";
    }

    if (!$error) {
        global $connect;
        try {
            mysqli_begin_transaction($connect);

            $detail = [];
            $total  = 0;
            foreach ($item as $idb => $qty) {
                // Kunci baris agar stok tidak bentrok dengan transaksi lain
                $b = db_one("SELECT id_barang, nama_barang, harga, stok FROM barang WHERE id_barang = ? FOR UPDATE", [$idb]);
                if (!$b) {
                    throw new RuntimeException("Barang tidak ditemukan.");
                }
                if ($b['stok'] < $qty) {
                    throw new RuntimeException("Stok {$b['nama_barang']} tidak cukup (sisa {$b['stok']}).");
                }
                $sub     = $b['harga'] * $qty;
                $total  += $sub;
                $detail[] = [$idb, $b['harga'], $qty, $sub];
            }

            db_exec("INSERT INTO transaksi (id_pelanggan, id_user, total) VALUES (?, ?, ?)",
                [$id_pelanggan, $_SESSION['id_user'], $total]);
            $id_transaksi = mysqli_insert_id($connect);

            foreach ($detail as [$idb, $harga, $qty, $sub]) {
                db_exec("INSERT INTO detail_transaksi (id_transaksi, id_barang, harga, qty, subtotal) VALUES (?, ?, ?, ?, ?)",
                    [$id_transaksi, $idb, $harga, $qty, $sub]);
                db_exec("UPDATE barang SET stok = stok - ? WHERE id_barang = ?", [$qty, $idb]);
            }

            mysqli_commit($connect);
            set_flash('success', "Transaksi #$id_transaksi berhasil disimpan. Total " . rupiah($total) . ".");
            header("Location: laporan.php?detail=$id_transaksi");
            exit();
        } catch (Throwable $ex) {
            mysqli_rollback($connect);
            $error[] = $ex instanceof RuntimeException ? $ex->getMessage() : "Transaksi gagal disimpan.";
        }
    }
}

$judul = "Transaksi";
$menu  = "transaksi";
layout_awal($judul, $menu);
?>

<h4 class="mb-3">Transaksi Penjualan</h4>

<?php foreach ($error as $msg): ?>
    <div class="alert alert-danger py-2"><?= e($msg) ?></div>
<?php endforeach; ?>

<form method="post" class="card shadow-sm" id="formTransaksi">
    <div class="card-body">
        <div class="mb-3" style="max-width:360px;">
            <label class="form-label" for="id_pelanggan">Pelanggan</label>
            <select class="form-select" name="id_pelanggan" id="id_pelanggan">
                <option value="">Umum (tanpa pelanggan)</option>
                <?php foreach ($pelanggan as $p): ?>
                    <option value="<?= (int) $p['id_pelanggan'] ?>"
                        <?= (string) ($_POST['id_pelanggan'] ?? '') === (string) $p['id_pelanggan'] ? 'selected' : '' ?>>
                        <?= e($p['nama_pelanggan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="table align-middle" id="tabelItem">
            <thead class="table-light">
                <tr><th>Barang</th><th style="width:130px">Harga</th><th style="width:110px">Jumlah</th><th style="width:150px">Subtotal</th><th style="width:60px"></th></tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-semibold">Total</td>
                    <td class="fw-semibold" id="grandTotal">Rp 0</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <button type="button" class="btn btn-outline-primary btn-sm" id="tambahBaris">+ Tambah Barang</button>
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
    </div>
</form>

<script>
const barang = <?= json_encode($barang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const tbody = document.querySelector('#tabelItem tbody');
const rp = n => 'Rp ' + Number(n).toLocaleString('id-ID');

function tambahBaris(idb = '', qty = 1) {
    const tr = document.createElement('tr');
    const opsi = barang.map(b =>
        `<option value="${b.id_barang}" ${b.id_barang == idb ? 'selected' : ''}>${b.nama_barang} (stok ${b.stok})</option>`
    ).join('');
    tr.innerHTML = `
        <td><select class="form-select" name="id_barang[]"><option value="">-- Pilih barang --</option>${opsi}</select></td>
        <td class="harga">-</td>
        <td><input type="number" class="form-control" name="qty[]" min="1" value="${qty}"></td>
        <td class="subtotal">-</td>
        <td><button type="button" class="btn btn-outline-danger btn-sm hapus">&times;</button></td>`;
    tbody.appendChild(tr);
    hitung();
}

function hitung() {
    let total = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
        const b = barang.find(x => x.id_barang == tr.querySelector('select').value);
        const q = parseInt(tr.querySelector('input').value) || 0;
        tr.querySelector('.harga').textContent = b ? rp(b.harga) : '-';
        tr.querySelector('.subtotal').textContent = b ? rp(b.harga * q) : '-';
        if (b) total += b.harga * q;
    });
    document.getElementById('grandTotal').textContent = rp(total);
}

tbody.addEventListener('input', hitung);
tbody.addEventListener('click', e => {
    if (e.target.classList.contains('hapus')) {
        e.target.closest('tr').remove();
        if (!tbody.children.length) tambahBaris();
        hitung();
    }
});
document.getElementById('tambahBaris').addEventListener('click', () => tambahBaris());

<?php
// Pertahankan isian jika validasi server gagal
$ids_post = $_POST['id_barang'] ?? [];
$qty_post = $_POST['qty'] ?? [];
if ($ids_post && $error) {
    foreach ($ids_post as $i => $idb) {
        echo "tambahBaris(" . json_encode((string) $idb) . ", " . json_encode((string) ($qty_post[$i] ?? 1)) . ");\n";
    }
} else {
    echo "tambahBaris();\n";
}
?>
</script>

<?php layout_akhir(); ?>
