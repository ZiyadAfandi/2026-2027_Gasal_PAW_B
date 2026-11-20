<?php
$host     = "127.0.0.1";
$username = "root";
$password = "";
$dbname   = "penjualan";
$port     = 3308;

try {
    $connect = mysqli_connect($host, $username, $password, $dbname, $port);
    mysqli_set_charset($connect, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("GAGAL KONEKSI KE DATABASE: " . $e->getMessage());
}