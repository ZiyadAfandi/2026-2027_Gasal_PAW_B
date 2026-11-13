<?php
require_once 'koneksi.php';

$mulai   = $_GET['tanggal_mulai']   ?? '';
$selesai = $_GET['tanggal_selesai'] ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selesai) || $mulai > $selesai) {
    http_response_code(400);
    die("Parameter tanggal tidak valid.");
}

$lap = ambilLaporan($koneksi, $mulai, $selesai);

// Header HTTP: memberi tahu browser bahwa respons adalah file Excel yang harus diunduh
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Rekap_Penjualan_{$mulai}_sd_{$selesai}.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
    <h3>Rekap Laporan Penjualan</h3>
    <p>Periode: <?= $mulai ?> sampai <?= $selesai ?></p>
    <table border="1">
        <thead>
            <tr style="background:#d9ebf7;">
                <th>No</th><th>Total Pendapatan</th><th>Tanggal Transaksi</th>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($lap['rekap'] as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= (int)$row['total_harian'] ?></td>
                <td><?= $row['tgl'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="2"><b>Jumlah Pelanggan Unik</b></td><td><?= $lap['total_pelanggan'] ?> Orang</td></tr>
            <tr><td colspan="2"><b>Total Pendapatan Keseluruhan</b></td><td><?= (int)$lap['total_pendapatan'] ?></td></tr>
        </tfoot>
    </table>
</body>
</html>
