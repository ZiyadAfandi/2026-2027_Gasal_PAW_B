<?php
require "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);

    $s = $koneksi->prepare("SELECT nama_barang FROM barang WHERE id = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $brg = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$brg) {
        set_flash('error', 'Barang tidak ditemukan.');
    } else {
        $s = $koneksi->prepare("SELECT COUNT(*) AS n FROM transaksi_detail WHERE barang_id = ?");
        $s->bind_param("i", $id);
        $s->execute();
        $dipakai = (int)$s->get_result()->fetch_assoc()['n'];
        $s->close();

        if ($dipakai > 0) {
            set_flash('error',
                "Barang \"{$brg['nama_barang']}\" tidak dapat dihapus karena masih digunakan pada "
                . "$dipakai transaksi.");
        } else {
            // 2b) Belum dipakai -> hapus
            try {
                $s = $koneksi->prepare("DELETE FROM barang WHERE id = ?");
                $s->bind_param("i", $id);
                $s->execute();
                $s->close();
                set_flash('sukses', "Barang \"{$brg['nama_barang']}\" berhasil dihapus.");
            } catch (mysqli_sql_exception $ex) {
                set_flash('error', "Barang \"{$brg['nama_barang']}\" tidak dapat dihapus karena masih terkait data lain.");
            }
        }
    }
    header("Location: barang.php");
    exit;
}

$daftar = $koneksi->query(
    "SELECT b.id, b.kode_barang, b.nama_barang, b.harga, b.stok, s.nama AS nama_supplier,
            (SELECT COUNT(*) FROM transaksi_detail d WHERE d.barang_id = b.id) AS dipakai
       FROM barang b
       LEFT JOIN supplier s ON s.id = b.supplier_id
      ORDER BY b.id"
);

html_awal("Data Barang");
tampil_flash();
?>
<div class="card">
  <h1>Data Barang</h1>
  <table>
    <tr>
      <th>ID</th><th>Kode</th><th>Nama Barang</th><th>Harga</th><th>Stok</th>
      <th>Supplier</th><th>Dipakai di Transaksi</th><th>Aksi</th>
    </tr>
    <?php while ($r = $daftar->fetch_assoc()): ?>
    <tr>
      <td><?= e($r['id']) ?></td>
      <td><?= e($r['kode_barang']) ?></td>
      <td><?= e($r['nama_barang']) ?></td>
      <td class="num"><?= rupiah($r['harga']) ?></td>
      <td class="num"><?= e($r['stok']) ?></td>
      <td><?= e($r['nama_supplier'] ?? '-') ?></td>
      <td class="num"><?= e($r['dipakai']) ?></td>
      <td>
        <form method="post" onsubmit="return confirm('Yakin hapus barang ini?');">
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button type="submit" class="merah">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php html_akhir(); ?>
