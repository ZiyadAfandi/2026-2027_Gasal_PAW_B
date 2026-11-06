<?php

$id = (int)($_GET['id'] ?? 0);

// ---- Data master ----
$stmt = $koneksi->prepare(
    "SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, p.nama AS nama_pelanggan
       FROM transaksi t LEFT JOIN pelanggan p ON p.id = t.pelanggan_id
      WHERE t.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$master = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$master) {
    set_flash('error', 'Transaksi tidak ditemukan.');
    header("Location: transaksi.php");
    exit;
}

// ---- Proses form ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $barang_id = (int)($_POST['barang_id'] ?? 0);
        $qty       = (int)($_POST['qty'] ?? 0);

        if ($barang_id <= 0 || $qty <= 0) {
            set_flash('error', 'Pilih barang dan isi qty minimal 1.');
        } else {
            try {
                $koneksi->begin_transaction();

                // Harga diambil dari tabel barang di server (bukan dari input browser)
                $s = $koneksi->prepare("SELECT harga FROM barang WHERE id = ?");
                $s->bind_param("i", $barang_id);
                $s->execute();
                $brg = $s->get_result()->fetch_assoc();
                $s->close();
                if (!$brg) { throw new Exception("Barang tidak ditemukan."); }

                // Cek di sisi server: barang tidak boleh dobel dalam satu transaksi
                $s = $koneksi->prepare(
                    "SELECT COUNT(*) AS n FROM transaksi_detail WHERE transaksi_id = ? AND barang_id = ?"
                );
                $s->bind_param("ii", $id, $barang_id);
                $s->execute();
                $sudah = $s->get_result()->fetch_assoc()['n'];
                $s->close();
                if ($sudah > 0) { throw new Exception("Barang tersebut sudah ada di transaksi ini."); }

                $harga = (int)$brg['harga'];
                $s = $koneksi->prepare(
                    "INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES (?, ?, ?, ?)"
                );
                $s->bind_param("iiii", $id, $barang_id, $harga, $qty);
                $s->execute();
                $s->close();

                // Perbarui total pada tabel transaksi
                hitung_ulang_total($koneksi, $id);

                $koneksi->commit();
                set_flash('sukses', 'Detail transaksi disimpan dan total transaksi diperbarui.');
            } catch (Throwable $ex) {
                $koneksi->rollback();
                set_flash('error', 'Gagal menyimpan: ' . $ex->getMessage());
            }
        }
    }

    if ($aksi === 'hapus_detail') {
        $barang_id = (int)($_POST['barang_id'] ?? 0);
        try {
            $koneksi->begin_transaction();
            $s = $koneksi->prepare("DELETE FROM transaksi_detail WHERE transaksi_id = ? AND barang_id = ?");
            $s->bind_param("ii", $id, $barang_id);
            $s->execute();
            $s->close();
            hitung_ulang_total($koneksi, $id);   // total tetap konsisten setelah hapus
            $koneksi->commit();
            set_flash('sukses', 'Item dihapus dari transaksi dan total diperbarui.');
        } catch (Throwable $ex) {
            $koneksi->rollback();
            set_flash('error', 'Gagal menghapus item: ' . $ex->getMessage());
        }
    }

    header("Location: detail.php?id=" . $id);   // hindari submit ulang saat refresh
    exit;
}

$stmt = $koneksi->prepare(
    "SELECT d.barang_id, b.kode_barang, b.nama_barang, d.harga, d.qty, (d.harga * d.qty) AS subtotal
       FROM transaksi_detail d
       INNER JOIN barang b ON b.id = d.barang_id
      WHERE d.transaksi_id = ?
      ORDER BY b.nama_barang"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $koneksi->prepare(
    "SELECT id, kode_barang, nama_barang, harga
       FROM barang
      WHERE id NOT IN (SELECT barang_id FROM transaksi_detail WHERE transaksi_id = ?)
      ORDER BY nama_barang"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$pilihan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

html_awal("Detail Transaksi #" . $id);
tampil_flash();
?>
<div class="card">
  <h1>Transaksi #<?= e($master['id']) ?> (Master)</h1>
  <table class="info">
    <tr><td><b>Tanggal</b></td><td>: <?= e($master['waktu_transaksi']) ?></td></tr>
    <tr><td><b>Pelanggan</b></td><td>: <?= e($master['nama_pelanggan'] ?? '-') ?></td></tr>
    <tr><td><b>Keterangan</b></td><td>: <?= e($master['keterangan']) ?></td></tr>
    <tr><td><b>Total</b></td><td>: <b><?= rupiah($master['total']) ?></b></td></tr>
  </table>
  <p><a class="btn abu" href="transaksi.php">&laquo; Kembali ke daftar transaksi</a></p>
</div>

<div class="card">
  <h2>Detail Barang</h2>
  <table>
    <tr><th>Kode</th><th>Nama Barang</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th>Aksi</th></tr>
    <?php if (!$detail): ?>
      <tr><td colspan="6">Belum ada barang pada transaksi ini.</td></tr>
    <?php endif; ?>
    <?php foreach ($detail as $d): ?>
    <tr>
      <td><?= e($d['kode_barang']) ?></td>
      <td><?= e($d['nama_barang']) ?></td>
      <td class="num"><?= rupiah($d['harga']) ?></td>
      <td class="num"><?= e($d['qty']) ?></td>
      <td class="num"><?= rupiah($d['subtotal']) ?></td>
      <td>
        <form method="post" style="display:inline" onsubmit="return confirm('Hapus item ini dari transaksi?');">
          <input type="hidden" name="aksi" value="hapus_detail">
          <input type="hidden" name="barang_id" value="<?= (int)$d['barang_id'] ?>">
          <button type="submit" class="merah">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <th colspan="4" style="text-align:right">Total</th>
      <th class="num"><?= rupiah($master['total']) ?></th><th></th>
    </tr>
  </table>
</div>

<div class="card">
  <h2>Tambah Barang ke Transaksi</h2>
  <?php if (!$pilihan): ?>
    <p>Semua barang sudah dimasukkan ke transaksi ini.</p>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="aksi" value="tambah">

    <label for="barang">Barang</label>
    <select id="barang" name="barang_id" required>
      <option value="">-- pilih barang --</option>
      <?php foreach ($pilihan as $b): ?>
        <option value="<?= (int)$b['id'] ?>" data-harga="<?= (int)$b['harga'] ?>">
          <?= e($b['kode_barang']) ?> - <?= e($b['nama_barang']) ?> (<?= rupiah($b['harga']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label for="harga">Harga satuan</label>
    <input type="text" id="harga" readonly placeholder="otomatis dari barang">

    <label for="qty">Qty</label>
    <input type="number" id="qty" name="qty" min="1" value="1" required>

    <p><button type="submit">Simpan Detail</button></p>
  </form>
  <script>

    document.getElementById('barang').addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      var h = opt.getAttribute('data-harga');
      document.getElementById('harga').value = h ? 'Rp ' + Number(h).toLocaleString('id-ID') : '';
    });
  </script>
  <?php endif; ?>
</div>
<?php html_akhir(); ?>
