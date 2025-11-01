<?php
// get_report_data.php
// File ini dipanggil oleh reporting.php untuk mengambil data JSON

session_start();
include 'koneksi.php';
header('Content-Type: application/json');

// --- 1. Keamanan & Penentuan Scope Admin ---
// Cek apakah user admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Hanya admin.']);
    exit;
}

// Ambil data session
$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_superadmin = (int)($_SESSION['is_superadmin'] ?? 0);

// Tentukan scope admin (WHERE clause)
// SAMA PERSIS DENGAN logic di article.php
$whereAdminScope = "1"; // Default '1' (true) untuk superadmin
if ($is_superadmin == 0) {
    // Admin biasa, cari penugasannya
    $scopes = [];
    $resScope = $koneksi->query("SELECT kategori_id, gedung_id FROM admin_penugasan WHERE user_id = $user_id");
    if ($resScope && $resScope->num_rows > 0) {
        while ($row = $resScope->fetch_assoc()) {
            $scopes[] = "(a.kategori_id = " . (int)$row['kategori_id'] . " AND a.gedung_id = " . (int)$row['gedung_id'] . ")";
        }
    }
    
    if (empty($scopes)) {
        $whereAdminScope = "0"; // '0' (false) jika tidak punya tugas
    } else {
        $whereAdminScope = "(" . implode(' OR ', $scopes) . ")";
    }
}
// --- Selesai Penentuan Scope ---


// --- 2. Ambil Filter Waktu dari GET ---
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));


// --- 3. Buat Query SQL ---
// Gabungkan filter Waktu dan filter Scope Admin
$whereTanggal = "MONTH(a.tanggal) = $month AND YEAR(a.tanggal) = $year";
$whereFinal = "($whereTanggal) AND ($whereAdminScope)";

$total = 0;
$categories = [];
$statusSummary = [];

// Query 1: Hitung Total (untuk persentase)
$sqlTotal = "SELECT COUNT(a.id) AS total FROM aspirasi a WHERE $whereFinal";
$resTotal = $koneksi->query($sqlTotal);
if ($resTotal) {
    $total = (int) $resTotal->fetch_assoc()['total'];
}

// Hanya jalankan query detail jika ada data
if ($total > 0) {
    
    // Query 2: Berdasarkan Kategori (Bergabung dengan tabel kategori)
    $sqlCat = "
        SELECT k.nama_kategori, COUNT(a.id) AS total_kategori
        FROM aspirasi a
        JOIN kategori k ON a.kategori_id = k.id
        WHERE $whereFinal
        GROUP BY k.nama_kategori
        ORDER BY total_kategori DESC
    ";
    
    $resCat = $koneksi->query($sqlCat);
    if ($resCat) {
        while ($row = $resCat->fetch_assoc()) {
            $categories[] = [
                'kategori' => $row['nama_kategori'],
                'total' => (int) $row['total_kategori'],
                'percent' => ((int) $row['total_kategori'] / $total) * 100
            ];
        }
    }

    // Query 3: Berdasarkan Status
    $sqlStat = "
        SELECT a.status, COUNT(a.id) AS total_status
        FROM aspirasi a
        WHERE $whereFinal
        GROUP BY a.status
    ";
    $resStat = $koneksi->query($sqlStat);
    if ($resStat) {
        // Pastikan kita punya 3 status
        $statusCounts = ['Menunggu' => 0, 'Diproses' => 0, 'Selesai' => 0];
        while ($row = $resStat->fetch_assoc()) {
            if (isset($statusCounts[$row['status']])) {
                 $statusCounts[$row['status']] = (int)$row['total_status'];
            }
        }
        
        foreach ($statusCounts as $status => $count) {
             if ($count > 0) { // Hanya tampilkan status yg ada datanya
                $statusSummary[] = [
                    'status' => $status,
                    'total' => $count,
                    'percent' => ($count / $total) * 100
                ];
             }
        }
    }
}

// --- 4. Kembalikan data sebagai JSON ---
echo json_encode([
    'success' => true,
    'total' => $total,
    'categories' => $categories,
    'statusSummary' => $statusSummary
]);

?>
