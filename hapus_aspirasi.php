<?php
session_start();
include "koneksi.php";

// 1. Cek apakah dia Superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    // Jika bukan, tendang dengan pesan error
    header("Location: admin.php?page=article&msg=fail_perm");
    exit;
}

// 2. Cek apakah form disubmit dengan benar (dari modal)
if (isset($_POST['submit_form_id']) && strpos($_POST['submit_form_id'], 'form-hapus-aspirasi-') === 0) {
    
    // Ambil ID aspirasi dari nama form
    $aspirasi_id = (int)str_replace('form-hapus-aspirasi-', '', $_POST['submit_form_id']);
    
    // Ambil ID kategori untuk redirect kembali
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    if ($aspirasi_id > 0) {
        // 3. Siapkan query DELETE
        $stmt = $koneksi->prepare("DELETE FROM aspirasi WHERE id = ?");
        $stmt->bind_param('i', $aspirasi_id);
        
        if ($stmt->execute()) {
            // 4. Berhasil
            header("Location: admin.php?page=article&category_id=$category_id&msg=success_del");
            exit;
        } else {
            // 5. Gagal
            header("Location: admin.php?page=article&category_id=$category_id&msg=fail");
            exit;
        }
    }
}

// 6. Jika akses langsung ke file ini, tendang saja
header("Location: admin.php?page=article");
exit;
?>

