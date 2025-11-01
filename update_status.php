<?php
session_start();
include 'koneksi.php';

// =========================================================================
// ▼▼▼ TAMBAHAN BARU: Cek Izin Ubah Status ▼▼▼
// =========================================================================
// Cek apakah user login
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Tentukan apakah user boleh mengubah status
$is_superadmin = (int)($_SESSION['is_superadmin'] ?? 0);
$can_ubah_status = (isset($_SESSION['can_ubah_status']) && $_SESSION['can_ubah_status'] == 1);
$user_allowed_to_edit = ($is_superadmin == 1) || ($can_ubah_status == 1);

// Jika tidak diizinkan, tendang keluar
if (!$user_allowed_to_edit) {
    header('Location: admin.php?page=dashboard&msg=fail_perm'); // Buat pesan error baru
    exit;
}
// =========================================================================
// ▲▲▲ SELESAI TAMBAHAN ▲▲▲
// =========================================================================


// Tentukan halaman kembali (default ke dashboard)
$return_page = 'admin.php?page=dashboard';
if (isset($_POST['return_page'])) {
    // Sanitasi sederhana untuk parameter halaman kembali
    $return_to = preg_replace('/[^a-zA-Z0-9_=&]/', '', $_POST['return_page']);
    $return_page = 'admin.php?page=' . $return_to;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Menunggu';
    $allowed_statuses = ['Menunggu', 'Diproses', 'Selesai'];

    // Validasi
    if ($id > 0 && in_array($status, $allowed_statuses)) {
        
        // TODO: (Opsional) Cek lebih lanjut apakah admin ini berhak mengubah ID aspirasi ini
        // (Untuk saat ini, kita percaya $can_ubah_status sudah cukup)

        $sql = "UPDATE aspirasi SET status = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            header("Location: $return_page&msg=success");
        } else {
            header("Location: $return_page&msg=fail");
        }
        exit;
    }
}

// Jika request tidak valid, kembalikan
header("Location: $return_page&msg=fail");
exit;
