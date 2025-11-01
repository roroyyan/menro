<?php
session_start();
include 'koneksi.php';

// Cek 1: Harus login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Cek 2: Harus mahasiswa
if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: admin.php?page=dashboard&msg=fail_auth');
    exit;
}

// Cek 3: Harus metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- PENGAMBILAN DATA (VERSI BARU) ---
    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $isi = trim($_POST['isi_aspirasi'] ?? '');

    // Ini adalah 2 field BARU dari form
    $kategori_id = $_POST['kategori_id'] ?? null;
    $gedung_id = $_POST['gedung_id'] ?? null;

    // --- VALIDASI (VERSI BARU) ---
    // Cek semua data wajib diisi
    if (empty($nama) || empty($nim) || empty($isi) || empty($kategori_id) || empty($gedung_id)) {
        header('Location: admin.php?page=dashboard&msg=fail_empty');
        exit;
    }

    // Cek apakah ID adalah angka yang valid
    if (!is_numeric($kategori_id) || !is_numeric($gedung_id)) {
        header('Location: admin.php?page=dashboard&msg=fail_invalid_data');
        exit;
    }

    // Ubah ke integer
    $kategori_id_int = intval($kategori_id);
    $gedung_id_int = intval($gedung_id);


    // --- QUERY (VERSI BARU) ---
    // Query INSERT baru sesuai skema database
    $sql = "INSERT INTO aspirasi (nama, nim, jurusan, kategori_id, gedung_id, isi_aspirasi) 
            VALUES (?, ?, ?, ?, ?, ?)";
            
    $stmt = $koneksi->prepare($sql);

    // Tipe data binding BARU: 'sssiss'
    // s = nama (string)
    // s = nim (string)
    // s = jurusan (string)
    // i = kategori_id (integer)
    // i = gedung_id (integer)
    // s = isi_aspirasi (string)
    $stmt->bind_param('sssiss', $nama, $nim, $jurusan, $kategori_id_int, $gedung_id_int, $isi);

    // --- EKSEKUSI ---
    if ($stmt->execute()) {
        header('Location: admin.php?page=dashboard&msg=success');
    } else {
        // Jika gagal, kirim pesan error spesifik
        // error_log("Gagal insert: " . $stmt->error); // (opsional: untuk debug)
        header('Location: admin.php?page=dashboard&msg=fail_db');
    }
    
    $stmt->close();
    $koneksi->close();
    exit;
}

// Jika diakses langsung (bukan POST), kembalikan ke dashboard
header('Location: admin.php?page=dashboard');
exit;
?>
