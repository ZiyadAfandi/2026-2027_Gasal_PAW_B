<?php
require_once 'fungsi.php';
wajib_login(); // owner & kasir boleh mengakses

// Filter tanggal (default: bulan berjalan)
function tanggal_valid($t)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $t);
    return $d && $d->format('Y-m-d') === $t;
}
$dari   = tanggal_valid($_GET['dari'] ?? '')   ? $_GET['dari']   : date('Y-m-01');
$sampai = tanggal_valid($_GET['sampai'] ?? '') ? $_GET['sampai'] : date('Y-m-d');

$data = db_all(
    "SELECT t.id_transaksi, t.tanggal, t.total,
            COALESCE(p.nama_pelanggan, 'Umum') AS pelanggan,
            u.nama AS kasir
     FROM transaksi t
     LEFT JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
     JOIN user u ON u.id_user = t.id_user
     WHERE DATE(t.tanggal) BETWEEN ? AND ?
     ORDER BY t.tanggal DESC, t.id_transaksi DESC",
    [$dari, $sampai]
);
$grand = array_sum(array_column($data, 'total'));

// Detail satu transaksi
$detail_id = (int) ($_GET['detail'] ?? 0);
$trx = null;
$items = [];
if ($detail_id) {
    $trx = db_one(
        "SELECT t.*, COALESCE(p.nama_pelanggan, 'Umum') AS pelanggan, u.nama AS kasir
         FROM transaksi t
         LEFT JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
         JOIN user u ON u.id_user = t.id_user
         WHERE t.id_transaksi = ?", [$detail_id]);
    if ($trx) {
        $items = db_all(
            "SELECT d.*, b.nama_barang FROM detail_transaksi d
             JOIN barang b ON b.id_barang = d.id_barang
             WHERE d.id_transaksi = ?", [$detail_id]);
    }
}

$judul = "Laporan";
$menu  = "laporan";
layout_awal($judul, $menu);
?>

<h4 class="mb-3">Laporan Penjualan</h4>

<?php if ($trx): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between">
        <span>Detail Transaksi #<?= (int) $trx['id_transaksi'] ?></span>
        <a href="laporan.php?dari=<?= e($dari) ?>&sampai=<?= e($sampai) ?>" class="text-white small">Tutup</a>
    </div>
    <div class="card-body">
        <div class="row mb-3 small">
            <div class="col-md-4"><span class="text-muted">Tanggal:</span> <?= e(date('d/m/Y H:i', strtotime($trx['tanggal']))) ?></div>
            <div class="col-md-4"><span class="text-muted">Pelanggan:</span> <?= e($trx['pelanggan']) ?></div>
            <div class="col-md-4"><span class="text-muted">Kasir:</span> <?= e($trx['kasir']) ?></div>
        </div>
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Barang</th><th class="text-end">Harga</th><th class="text-end">Jumlah</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['nama_barang']) ?></td>
                    <td class="text-end"><?= e(rupiah($it['harga'])) ?></td>
                    <td class="text-end"><?= (int) $it['qty'] ?></td>
                    <td class="text-end"><?= e(rupiah($it['subtotal'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end"><?= e(rupiah($trx['total'])) ?></th></tr></tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label small mb-0" for="dari">Dari tanggal</label>
        <input type="date" class="form-control" id="dari" name="dari" value="<?= e($dari) ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0" for="sampai">Sampai tanggal</label>
        <input type="date" class="form-control" id="sampai" name="sampai" value="<?= e($sampai) ?>">
    </div>
    <div class="col-auto"><button class="btn btn-primary">Tampilkan</button></div>
</form>

<div class="card shadow-sm"><div class="table-responsive">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light">
            <tr><th>No. Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th>Kasir</th><th class="text-end">Total</th><th style="width:90px"></th></tr>
        </thead>
        <tbody>
        <?php if (!$data): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada transaksi pada periode ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $r): ?>
            <tr>
                <td>#<?= (int) $r['id_transaksi'] ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($r['tanggal']))) ?></td>
                <td><?= e($r['pelanggan']) ?></td>
                <td><?= e($r['kasir']) ?></td>
                <td class="text-end"><?= e(rupiah($r['total'])) ?></td>
                <td><a class="btn btn-outline-primary btn-sm"
                       href="laporan.php?dari=<?= e($dari) ?>&sampai=<?= e($sampai) ?>&detail=<?= (int) $r['id_transaksi'] ?>">Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="4" class="text-end">Total Penjualan (<?= count($data) ?> transaksi)</th>
                <th class="text-end"><?= e(rupiah($grand)) ?></th><th></th></tr>
        </tfoot>
    </table>
</div></div>

<?php layout_akhir(); ?>
