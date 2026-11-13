<?php
require_once 'koneksi.php';

$mulai   = $_GET['tanggal_mulai']   ?? '';
$selesai = $_GET['tanggal_selesai'] ?? '';

$valid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selesai);
$pesan = '';
$labels = $dataTotal = [];
$rekap = [];
$totalPendapatan = $totalPelanggan = 0;

if ($mulai !== '' || $selesai !== '') {
    if (!$valid) {
        $pesan = "Format tanggal tidak valid.";
    } elseif ($mulai > $selesai) {
        $pesan = "Tanggal mulai tidak boleh lebih besar dari tanggal selesai.";
        $valid = false;
    } else {
        $lap = ambilLaporan($koneksi, $mulai, $selesai);
        $rekap = $lap['rekap'];
        $totalPendapatan = $lap['total_pendapatan'];
        $totalPelanggan  = $lap['total_pelanggan'];
        foreach ($rekap as $r) {
            $labels[]    = $r['tgl'];
            $dataTotal[] = (int)$r['total_harian'];
        }
    }
}
$tampil = $valid && $pesan === '' && $mulai !== '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penjualan</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h1 { font-size: 22px; margin-bottom: 12px; }
        h2 { font-size: 18px; margin: 24px 0 8px; }
        .filter-box { background: #f5f7fa; border: 1px solid #dde3ea; padding: 14px; border-radius: 6px; }
        .filter-box label { margin-right: 6px; }
        .filter-box input[type=date] { padding: 6px; margin-right: 12px; }
        button, .btn { padding: 7px 14px; border: 0; border-radius: 4px; cursor: pointer;
                       background: #0d6efd; color: #fff; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-cetak { background: #e8590c; }
        .btn-excel { background: #198754; }
        .aksi { margin: 14px 0; display: flex; gap: 8px; }
        .error { color: #b00020; margin-top: 10px; }
        .chart-wrap { width: 100%; max-width: 800px; height: 320px; }
        table { border-collapse: collapse; width: 100%; margin-top: 8px; }
        th, td { border: 1px solid #ccd; padding: 8px; text-align: left; }
        thead th { background: #d9ebf7; }
        .cards { display: flex; gap: 16px; margin-top: 18px; flex-wrap: wrap; }
        .card { flex: 1; min-width: 220px; background: #e7f3fb; border: 1px solid #b9d8ee;
                border-radius: 6px; padding: 14px 18px; }
        .card small { color: #456; display: block; margin-bottom: 4px; }
        .card strong { font-size: 22px; }
        .judul-cetak { display: none; }

        /* Pengaturan saat dicetak: sembunyikan tombol, form filter, dsb. */
        @media print {
            .no-print { display: none !important; }
            .judul-cetak { display: block; }
            body { margin: 0; }
            .card, thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <h1>Filter Laporan Penjualan</h1>
    <form method="GET" class="filter-box">
        <label for="tanggal_mulai">Tanggal Mulai:</label>
        <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= htmlspecialchars($mulai) ?>" required>
        <label for="tanggal_selesai">Tanggal Selesai:</label>
        <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="<?= htmlspecialchars($selesai) ?>" required>
        <button type="submit">Tampilkan</button>
    </form>
    <?php if ($pesan): ?><p class="error"><?= htmlspecialchars($pesan) ?></p><?php endif; ?>

    <?php if ($tampil): ?>
    <div class="aksi">
        <button type="button" class="btn-cetak" onclick="window.print()">Cetak</button>
        <a class="btn btn-excel"
           href="export_excel.php?tanggal_mulai=<?= urlencode($mulai) ?>&tanggal_selesai=<?= urlencode($selesai) ?>">Excel</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($tampil): ?>
    <h1 class="judul-cetak">Rekap Laporan Penjualan</h1>
    <h2>Rekap Laporan Penjualan
        <small style="font-weight:normal;color:#666;font-size:14px;"><?= $mulai ?> sampai <?= $selesai ?></small>
    </h2>

    <?php if (empty($rekap)): ?>
        <p>Tidak ada transaksi pada rentang tanggal tersebut.</p>
    <?php else: ?>

    <!-- Grafik -->
    <div class="chart-wrap"><canvas id="grafikPenjualan"></canvas></div>

    <!-- Tabel rekap -->
    <h2>Rekapitulasi</h2>
    <table>
        <thead>
            <tr><th>No</th><th>Total Pendapatan</th><th>Tanggal Transaksi</th></tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($rekap as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= rupiah($row['total_harian']) ?></td>
                <td><?= tglIndo($row['tgl']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Ringkasan total kumulatif -->
    <div class="cards">
        <div class="card"><small>Jumlah Pelanggan Unik</small><strong><?= $totalPelanggan ?> Orang</strong></div>
        <div class="card"><small>Total Pendapatan Keseluruhan</small><strong><?= rupiah($totalPendapatan) ?></strong></div>
    </div>

    <script>
        new Chart(document.getElementById('grafikPenjualan').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Total Pendapatan',
                    data: <?= json_encode($dataTotal) ?>,
                    backgroundColor: 'rgba(13,110,253,0.6)',
                    borderColor: 'rgba(13,110,253,1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false, // agar grafik langsung tampil saat dicetak
                scales: {
                    x: { title: { display: true, text: 'Tanggal Transaksi' } },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Total Pendapatan (Rp)' },
                        ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
