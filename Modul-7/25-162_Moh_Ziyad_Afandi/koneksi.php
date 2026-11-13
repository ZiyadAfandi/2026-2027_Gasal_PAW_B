<?php
// Koneksi ke database MySQL (sesuaikan dengan konfigurasi Anda)
$koneksi = mysqli_connect("localhost", "root", "", "db_toko");

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($koneksi, "utf8mb4");

/**
 * Mengambil data laporan penjualan pada rentang tanggal tertentu.
 * Dipakai bersama oleh laporan.php dan export_excel.php.
 */
function ambilLaporan(mysqli $koneksi, string $mulai, string $selesai): array
{
    $hasil = [
        'rekap'            => [],
        'total_pendapatan' => 0,
        'total_pelanggan'  => 0,
    ];

    // 1) Agregasi total pendapatan harian (SUM + GROUP BY + DATE)
    $sql = "SELECT DATE(waktu_transaksi) AS tgl, SUM(total) AS total_harian
            FROM transaksi
            WHERE DATE(waktu_transaksi) BETWEEN ? AND ?
            GROUP BY DATE(waktu_transaksi)
            ORDER BY tgl ASC";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $mulai, $selesai);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $hasil['rekap'][] = $r;
        $hasil['total_pendapatan'] += (int)$r['total_harian'];
    }
    mysqli_stmt_close($stmt);

    // 2) Jumlah pelanggan unik (COUNT DISTINCT)
    $sql2 = "SELECT COUNT(DISTINCT pelanggan_id) AS jml
             FROM transaksi
             WHERE DATE(waktu_transaksi) BETWEEN ? AND ?";
    $stmt2 = mysqli_prepare($koneksi, $sql2);
    mysqli_stmt_bind_param($stmt2, "ss", $mulai, $selesai);
    mysqli_stmt_execute($stmt2);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    $hasil['total_pelanggan'] = (int)($row['jml'] ?? 0);
    mysqli_stmt_close($stmt2);

    return $hasil;
}

function rupiah($angka): string
{
    return "Rp. " . number_format((float)$angka, 0, ',', '.');
}

function tglIndo(string $tgl): string
{
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tgl);
    return date('d', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}
