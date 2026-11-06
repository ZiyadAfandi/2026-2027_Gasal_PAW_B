<?php
require "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal    = $_POST['waktu_transaksi'] ?? '';
    $pelanggan  = $_POST['pelanggan_id'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($tanggal === '' || $pelanggan === '') {
        set_flash('error', 'Tanggal dan pelanggan wajib diisi.');
        header("Location: transaksi.php");
        exit;
    }

    $stmt = $koneksi->prepare(
        "INSERT INTO transaksi (waktu_transaksi, keterangan, total, pelanggan_id) VALUES (?, ?, 0, ?)"
    );
    $stmt->bind_param("sss", $tanggal, $keterangan, $pelanggan);
    $stmt->execute();
    $id_baru = $koneksi->insert_id;
    $stmt->close();

    set_flash('sukses', 'Transaksi baru berhasil dibuat. Silakan isi detail barangnya.');
    header("Location: detail.php?id=" . $id_baru);
    exit;
}

$daftar = $koneksi->query(
    "SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, p.nama AS nama_pelanggan,
            (SELECT COUNT(*) FROM transaksi_detail d WHERE d.transaksi_id = t.id) AS jml_item
       FROM transaksi t
       LEFT JOIN pelanggan p ON p.id = t.pelanggan_id
      ORDER BY t.id DESC"
);
$pelanggan = $koneksi->query("SELECT id, nama FROM pelanggan ORDER BY nama");

html_awal("Data Transaksi");
tampil_flash();
?>
<div class="card">
  <h1>Data Transaksi (Master)</h1>
  <table>
    <tr>
      <th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Keterangan</th>
      <th>Jumlah Item</th><th>Total</th><th>Aksi</th>
    </tr>
    <?php while ($r = $daftar->fetch_assoc()): ?>
    <tr>
      <td><?= e($r['id']) ?></td>
      <td><?= e($r['waktu_transaksi']) ?></td>
      <td><?= e($r['nama_pelanggan'] ?? '-') ?></td>
      <td><?= e($r['keterangan']) ?></td>
      <td class="num"><?= e($r['jml_item']) ?></td>
      <td class="num"><?= rupiah($r['total']) ?></td>
      <td><a class="btn" href="detail.php?id=<?= (int)$r['id'] ?>">Lihat / Isi Detail</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<div class="card">
  <h2>Tambah Transaksi Baru</h2>
  <form method="post">
    <label for="tgl">Tanggal</label>
    <input type="date" id="tgl" name="waktu_transaksi" value="<?= date('Y-m-d') ?>" required>

    <label for="plg">Pelanggan</label>
    <select id="plg" name="pelanggan_id" required>
      <option value="">-- pilih pelanggan --</option>
      <?php while ($p = $pelanggan->fetch_assoc()): ?>
        <option value="<?= e($p['id']) ?>"><?= e($p['nama']) ?> (<?= e($p['id']) ?>)</option>
      <?php endwhile; ?>
    </select>

    <label for="ket">Keterangan</label>
    <textarea id="ket" name="keterangan" rows="2"></textarea>

    <p><button type="submit">Simpan &amp; Isi Detail</button></p>
  </form>
</div>
<?php html_akhir(); ?>
