<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$koneksi = new mysqli("localhost", "root", "", "praktikum6");  // ubah sesuai server Anda
$koneksi->set_charset("utf8mb4");

session_start();

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

function set_flash($tipe, $pesan) { $_SESSION['flash'] = [$tipe, $pesan]; }
function tampil_flash() {
    if (!empty($_SESSION['flash'])) {
        [$tipe, $pesan] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="notif ' . e($tipe) . '">' . e($pesan) . '</div>';
    }
}

function hitung_ulang_total($koneksi, $transaksi_id) {
    $stmt = $koneksi->prepare(
        "UPDATE transaksi
            SET total = (SELECT COALESCE(SUM(harga * qty), 0)
                           FROM transaksi_detail
                          WHERE transaksi_id = ?)
          WHERE id = ?"
    );
    $stmt->bind_param("ii", $transaksi_id, $transaksi_id);
    $stmt->execute();
    $stmt->close();
}

function html_awal($judul) { ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?></title>
<style>
  body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #222; }
  nav { background: #1f4e79; padding: 12px 24px; }
  nav a { color: #fff; text-decoration: none; margin-right: 20px; font-weight: bold; }
  nav a:hover { text-decoration: underline; }
  .wrap { max-width: 960px; margin: 24px auto; padding: 0 16px; }
  .card { background: #fff; border-radius: 6px; padding: 18px 20px; margin-bottom: 20px;
          box-shadow: 0 1px 3px rgba(0,0,0,.12); }
  h1 { margin-top: 0; font-size: 22px; } h2 { margin-top: 0; font-size: 18px; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #d0d7de; padding: 8px 10px; text-align: left; }
  th { background: #e8eef4; }
  td.num { text-align: right; }
  label { display: block; margin: 10px 0 4px; font-weight: bold; }
  input, select, textarea { padding: 7px; width: 100%; max-width: 420px; box-sizing: border-box; }
  button, .btn { background: #1f4e79; color: #fff; border: 0; padding: 7px 14px; border-radius: 4px;
                 cursor: pointer; text-decoration: none; font-size: 14px; display: inline-block; }
  button.merah { background: #b32d2e; } .btn.abu { background: #666; }
  .notif { padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
  .notif.sukses { background: #dff3e1; border: 1px solid #8fcf97; }
  .notif.error  { background: #fbe0e0; border: 1px solid #e29a9a; }
  .info td { border: 0; padding: 3px 10px 3px 0; }
</style>
</head>
<body>
<nav>
  <a href="transaksi.php">Transaksi</a>
  <a href="barang.php">Barang</a>
</nav>
<div class="wrap">
<?php }

function html_akhir() { ?>
</div>
</body>
</html>
<?php }
