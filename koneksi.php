<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "nnic"; // ⚠️ GANTI sesuai nama database kamu di phpMyAdmin

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . mysqli_connect_error()]));
}
?>
