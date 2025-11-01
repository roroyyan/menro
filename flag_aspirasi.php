<?php
session_start();
include 'koneksi.php';

// Hanya admin (Monitor, Eksekutor, atau Superadmin) yang boleh flag
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Tentukan halaman kembali (default ke dashboard)
$return_page = 'admin.php?page=dashboard';
if (isset($_POST['return_page'])) {
    // Sanitasi sederhana untuk parameter halaman kembali
    $return_to = preg_replace('/[^a-zA-Z0-9_=&]/', '', $_POST['return_page']);
    $return_page = 'admin.php?page=' . $return_to;
}

// Ambil Izin dari Session
$is_superadmin = (int)($_SESSION['is_superadmin'] ?? 0);
$can_ubah_status = ($is_superadmin == 1) || (isset($_SESSION['can_ubah_status']) && $_SESSION['can_ubah_status'] == 1);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aspirasi_id'])) {
    $id = (int)$_POST['aspirasi_id'];

    if ($id <= 0) {
        header("Location: $return_page&msg=fail");
        exit;
    }
    
    // 1. Ambil status aspirasi saat ini
    $stmt_check = $koneksi->prepare("SELECT status, is_flagged FROM aspirasi WHERE id = ?");
    $stmt_check->bind_param('i', $id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    
    if ($res_check->num_rows === 0) {
        header("Location: $return_page&msg=fail"); // Aspirasi tidak ditemukan
        exit;
    }
    
    $aspirasi = $res_check->fetch_assoc();
    $current_status = $aspirasi['status'];
    $current_is_flagged = $aspirasi['is_flagged'];
    
    // Tentukan aksi: 'flag' atau 'unflag'
    $action_to_do = $current_is_flagged ? 'unflag' : 'flag';

    // 2. Tentukan apakah aksi diizinkan
    $allowed = false;
    
    if ($is_superadmin) {
        // Superadmin boleh melakukan apa saja
        $allowed = true;
    } elseif ($action_to_do === 'flag') {
        // Aksi: MENAMBAH Flag
        if (!$can_ubah_status) { // Hanya MONITOR (yg TIDAK BISA ubah status)
            $allowed = true;
        }
    } elseif ($action_to_do === 'unflag') {
        // Aksi: MENGHAPUS Flag
        if ($can_ubah_status && $current_status === 'Selesai') { // Hanya EKSEKUTOR (yg BISA ubah status) DAN status = Selesai
            $allowed = true;
        }
    }

    // 3. Eksekusi jika diizinkan
    if ($allowed) {
        $sql = "UPDATE aspirasi SET is_flagged = !is_flagged WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            header("Location: $return_page&msg=success_flag");
        } else {
            header("Location: $return_page&msg=fail");
        }
        exit;
    } else {
        // Aksi tidak diizinkan
        header("Location: $return_page&msg=fail_perm");
        exit;
    }
}

// Jika request tidak valid, kembalikan
header("Location: $return_page&msg=fail");
exit;

