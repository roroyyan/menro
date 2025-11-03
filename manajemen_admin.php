<?php
// manajemen_admin.php

// Cek sesi dan pastikan dia superadmin
// --- ▼▼▼ PERBAIKAN V18: Tambahkan pengecekan session_start ▼▼▼ ---
if (session_status() === PHP_SESSION_NONE) session_start();
// --- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ ---
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    echo '<div class="alert alert-danger">Anda tidak memiliki hak akses ke halaman ini.</div>';
    exit;
}

// Inisialisasi pesan
$msg = '';
$error = '';

// =========================================================================
// PROSES FORM (PINDAH KE ATAS SEBELUM HTML)
// =========================================================================

// 1. PROSES: Tambah Admin Baru
if (isset($_POST['tambah_admin'])) {
    // --- ▼▼▼ PERBAIKAN V18: Bersihkan kode PHP dari spasi aneh ▼▼▼ ---
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $can_ubah_status = isset($_POST['can_ubah_status']) ? 1 : 0;
    // --- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ ---

    if (!empty($username) && !empty($password)) {
        // Cek username
        $cek = $koneksi->prepare("SELECT id FROM user WHERE username = ?");
        $cek->bind_param('s', $username);
        $cek->execute();
        $res = $cek->get_result();
        if ($res->num_rows > 0) {
            $error = 'Username sudah terdaftar. Gunakan username lain.';
        } else {
            // Buat admin baru
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $koneksi->prepare("INSERT INTO user (username, password, role, can_ubah_status) VALUES (?, ?, 'admin', ?)");
            $stmt->bind_param('ssi', $username, $hash, $can_ubah_status);
            if ($stmt->execute()) {
                $msg = 'Admin baru berhasil dibuat.';
            } else {
                $error = 'Gagal membuat admin baru.';
            }
        }
    } else {
        $error = 'Username dan password tidak boleh kosong.';
    }
}

// 2. PROSES: Tambah Penugasan (Manual)
if (isset($_POST['tambah_penugasan'])) {
    $user_id = (int)$_POST['user_id'];
    $kategori_id = (int)$_POST['kategori_id'];
    $gedung_id = (int)$_POST['gedung_id'];

    if ($user_id > 0 && $kategori_id > 0 && $gedung_id > 0) {
        // Cek duplikat
        $cek = $koneksi->prepare("SELECT id FROM admin_penugasan WHERE user_id = ? AND kategori_id = ? AND gedung_id = ?");
        $cek->bind_param('iii', $user_id, $kategori_id, $gedung_id);
        $cek->execute();
        $res = $cek->get_result();
        if ($res->num_rows > 0) {
            $error = 'Penugasan tersebut sudah ada.';
        } else {
            // Tambah penugasan
            $stmt = $koneksi->prepare("INSERT INTO admin_penugasan (user_id, kategori_id, gedung_id) VALUES (?, ?, ?)");
            $stmt->bind_param('iii', $user_id, $kategori_id, $gedung_id);
            if ($stmt->execute()) {
                $msg = 'Penugasan berhasil ditambahkan.';
            } else {
                $error = 'Gagal menambahkan penugasan.';
            }
        }
    } else {
        $error = 'Semua field penugasan harus diisi.';
    }
}

// 3. PROSES: Hapus Penugasan
// --- ▼▼▼ PERBAIKAN V18: Ganti logika 'confirm()' dengan 'submit_form_id' ▼▼▼ ---
if (isset($_POST['submit_form_id']) && strpos($_POST['submit_form_id'], 'form-hapus-penugasan-') === 0) {
    $penugasan_id = (int)str_replace('form-hapus-penugasan-', '', $_POST['submit_form_id']);
// --- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ ---
    $stmt = $koneksi->prepare("DELETE FROM admin_penugasan WHERE id = ?");
    $stmt->bind_param('i', $penugasan_id);
    if ($stmt->execute()) {
        $msg = 'Penugasan berhasil dihapus.';
    } else {
        $error = 'Gagal menghapus penugasan.';
    }
}

// 4. PROSES: Hapus Admin (Permanen)
// --- ▼▼▼ PERBAIKAN V18: Ganti logika 'confirm()' dengan 'submit_form_id' ▼▼▼ ---
if (isset($_POST['submit_form_id']) && strpos($_POST['submit_form_id'], 'form-hapus-admin-') === 0) {
    $user_id_to_delete = (int)str_replace('form-hapus-admin-', '', $_POST['submit_form_id']);
// --- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ ---
    $current_user_id = (int)$_SESSION['user_id'];

    if ($user_id_to_delete === $current_user_id) {
        $error = 'Anda tidak bisa menghapus akun Anda sendiri.';
    } 
    elseif ($user_id_to_delete === 1) {
        $error = 'Admin utama (ID 1) tidak boleh dihapus.';
    } 
    else {
        // Hapus penugasannya dulu
        $stmtDelTugas = $koneksi->prepare("DELETE FROM admin_penugasan WHERE user_id = ?");
        $stmtDelTugas->bind_param('i', $user_id_to_delete);
        $stmtDelTugas->execute(); // Jalankan dulu

        // Baru hapus user-nya
        $stmt = $koneksi->prepare("DELETE FROM user WHERE id = ? AND role = 'admin'");
        $stmt->bind_param('i', $user_id_to_delete);
        if ($stmt->execute()) {
            $msg = 'Akun admin dan semua penugasannya berhasil dihapus permanen.';
        } else {
            $error = 'Gagal menghapus akun admin.';
        }
    }
}

// 5. PROSES: Penugasan Otomatis (Bulk)
if (isset($_POST['tambah_penugasan_otomatis'])) {
    $user_id = (int)$_POST['user_id'];
    $mode = $_POST['mode_penugasan'] ?? '';
    $counter = 0;

    if ($user_id > 0 && !empty($mode)) {
        
        $all_kategori_ids = [];
        $res_kat = $koneksi->query("SELECT id FROM kategori");
        while($row = $res_kat->fetch_assoc()) $all_kategori_ids[] = (int)$row['id'];
        
        $all_gedung_ids = [];
        $res_ged = $koneksi->query("SELECT id FROM gedung");
        while($row = $res_ged->fetch_assoc()) $all_gedung_ids[] = (int)$row['id'];

        $stmtCek = $koneksi->prepare("SELECT id FROM admin_penugasan WHERE user_id = ? AND kategori_id = ? AND gedung_id = ?");
        $stmtIns = $koneksi->prepare("INSERT INTO admin_penugasan (user_id, kategori_id, gedung_id) VALUES (?, ?, ?)");

        if ($mode === 'bulk_gedung') {
            $gedung_id = (int)$_POST['gedung_id_bulk'];
            if ($gedung_id > 0) {
                foreach ($all_kategori_ids as $kategori_id) {
                    $stmtCek->bind_param('iii', $user_id, $kategori_id, $gedung_id);
                    $stmtCek->execute();
                    $res = $stmtCek->get_result();
                    if ($res->num_rows == 0) { 
                        $stmtIns->bind_param('iii', $user_id, $kategori_id, $gedung_id);
                        $stmtIns->execute();
                        $counter++;
                    }
                }
                $msg = "$counter penugasan baru berhasil ditambahkan untuk gedung yang dipilih.";
            } else {
                $error = 'Silakan pilih gedung.';
            }

        } elseif ($mode === 'bulk_kategori') {
            $kategori_id = (int)$_POST['kategori_id_bulk'];
            if ($kategori_id > 0) {
                foreach ($all_gedung_ids as $gedung_id) {
                    $stmtCek->bind_param('iii', $user_id, $kategori_id, $gedung_id);
                    $stmtCek->execute();
                    $res = $stmtCek->get_result();
                    if ($res->num_rows == 0) { 
                        $stmtIns->bind_param('iii', $user_id, $kategori_id, $gedung_id);
                        $stmtIns->execute();
                        $counter++;
                    }
                }
                $msg = "$counter penugasan baru berhasil ditambahkan untuk kategori yang dipilih.";
            } else {
                $error = 'Silakan pilih kategori.';
            }
        }
        $stmtCek->close();
        $stmtIns->close();
    } else {
        $error = 'Silakan pilih admin dan mode penugasan.';
    }
}
// =========================================================================
// SELESAI PROSES FORM
// =========================================================================


// =========================================================================
// AMBIL DATA UNTUK DROPDOWN
// =========================================================================
$list_admin_data = [];
$res_admin = $koneksi->query("SELECT id, username FROM user WHERE role = 'admin' AND is_superadmin = 0 ORDER BY username ASC");
while($row = $res_admin->fetch_assoc()) $list_admin_data[] = $row;

$list_kategori_data = [];
$res_kat = $koneksi->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
while($row = $res_kat->fetch_assoc()) $list_kategori_data[] = $row;

$list_gedung_data = [];
$res_ged = $koneksi->query("SELECT id, nama_gedung FROM gedung ORDER BY nama_gedung ASC");
while($row = $res_ged->fetch_assoc()) $list_gedung_data[] = $row;


// =========================================================================
// LOGIKA FILTER TABEL PENUGASAN 
// =========================================================================
$filter_gedung_id = isset($_GET['filter_gedung_id']) ? (int)$_GET['filter_gedung_id'] : 0;
$filter_kategori_id = isset($_GET['filter_kategori_id']) ? (int)$_GET['filter_kategori_id'] : 0;
$filter_user_id = isset($_GET['filter_user_id']) ? (int)$_GET['filter_user_id'] : 0;

$where_penugasan = [];
$where_sql = "1"; 

if ($filter_gedung_id > 0) {
    $where_penugasan[] = "p.gedung_id = $filter_gedung_id";
}
if ($filter_kategori_id > 0) {
    $where_penugasan[] = "p.kategori_id = $filter_kategori_id";
}
if ($filter_user_id > 0) {
    $where_penugasan[] = "p.user_id = $filter_user_id";
}

if (!empty($where_penugasan)) {
    $where_sql = implode(' AND ', $where_penugasan);
}

// Ambil data untuk tabel penugasan (DENGAN FILTER)
$list_penugasan = $koneksi->query("
    SELECT p.id, u.username, k.nama_kategori, g.nama_gedung
    FROM admin_penugasan p
    JOIN user u ON p.user_id = u.id
    JOIN kategori k ON p.kategori_id = k.id
    JOIN gedung g ON p.gedung_id = g.id
    WHERE $where_sql
    ORDER BY g.nama_gedung, k.nama_kategori, u.username
");

$daftar_semua_admin = $koneksi->query("
    SELECT id, username, is_superadmin, can_ubah_status 
    FROM user 
    WHERE role = 'admin' 
    ORDER BY username ASC
");

?>

<!-- Tampilkan pesan sukses/error -->
<?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>


<div class="row">
    <!-- Kolom Kiri: Form -->
    <div class="col-md-5">
        
        <!-- Form 1: Tambah Admin Baru -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-semibold">
                1. Tambah Admin Baru
            </div>
            <div class="card-body">
                <form method="POST" action="admin.php?page=manajemen_admin&<?= http_build_query($_GET) ?>">
                    <div class="mb-3">
                        <label class="form-label">Username Admin Baru</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="can_ubah_status" value="1" id="checkUbahStatus">
                        <label class="form-check-label" for="checkUbahStatus">
                            Beri izin Ubah Status Aksi (Peran: Admin Kategori)
                        </label>
                    </div>
                    <button type="submit" name="tambah_admin" class="btn btn-primary">Buat Admin</button>
                </form>
            </div>
        </div>

        <!-- Form 2: Tambah Penugasan (Manual) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white fw-semibold">
                2. Tambah Penugasan (Manual)
            </div>
            <div class="card-body">
                <form method="POST" action="admin.php?page=manajemen_admin&<?= http_build_query($_GET) ?>">
                    <div class="mb-3">
                        <label class="form-label">Pilih Admin</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Pilih Admin --</option>
                            <?php foreach($list_admin_data as $row): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($list_kategori_data as $row): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Gedung</label>
                        <select name="gedung_id" class="form-select" required>
                            <option value="">-- Pilih Gedung --</option>
                            <?php foreach($list_gedung_data as $row): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_gedung']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="tambah_penugasan" class="btn btn-info">Tugaskan</button>
                </form>
            </div>
        </div>
        
        <!-- Form 3: Penugasan Otomatis (Bulk) -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white fw-semibold">
                3. Tambah Penugasan Otomatis (Bulk)
            </div>
            <div class="card-body">
                <form method="POST" action="admin.php?page=manajemen_admin&<?= http_build_query($_GET) ?>" id="form-bulk-assign">
                    <!-- Pilih Admin (Wajib) -->
                    <div class="mb-3">
                        <label class="form-label">Pilih Admin</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Pilih Admin --</option>
                            <?php foreach($list_admin_data as $row): ?>
                                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <!-- Pilihan Mode -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode_penugasan" id="mode-bulk-gedung" value="bulk_gedung" required>
                            <label class="form-check-label" for="mode-bulk-gedung">
                                Tugaskan ke <strong>SEMUA KATEGORI</strong> di <strong>1 Gedung</strong> (Peran: Admin Gedung)
                            </label>
                        </div>
                        <div id="div-bulk-gedung" class="ms-4 mt-2" style="display:none;">
                            <label>Pilih Gedung</label>
                            <select name="gedung_id_bulk" class="form-select">
                                <option value="">-- Pilih Gedung --</option>
                                <?php foreach($list_gedung_data as $row): ?>
                                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_gedung']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="mode_penugasan" id="mode-bulk-kategori" value="bulk_kategori" required>
                            <label class="form-check-label" for="mode-bulk-kategori">
                                Tugaskan ke <strong>1 KATEGORI</strong> di <strong>Semua Gedung</strong> (Peran: Admin Kategori)
                            </label>
                        </div>
                        <div id="div-bulk-kategori" class="ms-4 mt-2" style="display:none;">
                            <label>Pilih Kategori</label>
                            <select name="kategori_id_bulk" class="form-select">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach($list_kategori_data as $row): ?>
                                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="tambah_penugasan_otomatis" class="btn btn-success">Tugaskan Otomatis</button>
                </form>
                
                <!-- JS untuk menampilkan/menyembunyikan dropdown -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const radioGedung = document.getElementById('mode-bulk-gedung');
                        const radioKategori = document.getElementById('mode-bulk-kategori');
                        const divGedung = document.getElementById('div-bulk-gedung');
                        const divKategori = document.getElementById('div-bulk-kategori');
                        const selectGedung = divGedung.querySelector('select');
                        const selectKategori = divKategori.querySelector('select');

                        function toggleAssignMode() {
                            if (radioGedung.checked) {
                                divGedung.style.display = 'block';
                                selectGedung.required = true;
                                divKategori.style.display = 'none';
                                selectKategori.required = false;
                            } else if (radioKategori.checked) {
                                divGedung.style.display = 'none';
                                selectGedung.required = false;
                                divKategori.style.display = 'block';
                                selectKategori.required = true;
                            }
                        }
                        radioGedung.addEventListener('change', toggleAssignMode);
                        radioKategori.addEventListener('change', toggleAssignMode);
                    });
                </script>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar Penugasan -->
    <div class="col-md-7">

        <!-- Form Filter (Kode ini tetap sama) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light fw-semibold">
                Filter Daftar Penugasan
            </div>
            <div class="card-body">
                <form method="GET" action="admin.php">
                    <input type="hidden" name="page" value="manajemen_admin">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Filter Gedung</label>
                            <select name="filter_gedung_id" class="form-select">
                                <option value="0">-- Semua Gedung --</option>
                                <?php foreach($list_gedung_data as $row): ?>
                                    <option value="<?= $row['id'] ?>" <?= ($filter_gedung_id == $row['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['nama_gedung']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Filter Kategori</label>
                            <select name="filter_kategori_id" class="form-select">
                                <option value="0">-- Semua Kategori --</option>
                                <?php foreach($list_kategori_data as $row): ?>
                                    <option value="<?= $row['id'] ?>" <?= ($filter_kategori_id == $row['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Filter Admin</label>
                            <select name="filter_user_id" class="form-select">
                                <option value="0">-- Semua Admin --</option>
                                <?php foreach($list_admin_data as $row): ?>
                                    <option value="<?= $row['id'] ?>" <?= ($filter_user_id == $row['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="admin.php?page=manajemen_admin" class="btn btn-outline-secondary me-2">Reset Filter</a>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabel Penugasan (Kode ini tetap sama) -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white fw-semibold">
                Daftar Penugasan Admin (Telah Difilter)
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-hover">
                        <!-- ▼▼▼ PERUBAHAN DI SINI ▼▼▼ -->
                        <!-- 'table-light' diganti 'bg-white' agar background solid (tidak tembus) -->
                        <!-- Tambahkan inline style untuk z-index -->
                        <thead class="bg-white sticky-top" style="z-index: 1010;">
                        <!-- ▲▲▲ SELESAI PERUBAHAN ▲▲▲ -->
                            <tr>
                                <th>Admin</th>
                                <th>Kategori</th>
                                <th>Gedung</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list_penugasan->num_rows > 0): ?>
                                <?php while($row = $list_penugasan->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_gedung']) ?></td>
                                        <td>
                                            <!-- ▼▼▼ PERBAIKAN V18: Logika Modal untuk Tombol Hapus ▼▼▼ -->
                                            <form method="POST" action="admin.php?page=manajemen_admin&<?= http_build_query($_GET) ?>" id="form-hapus-penugasan-<?= $row['id'] ?>">
                                                <input type="hidden" name="submit_form_id" value="form-hapus-penugasan-<?= $row['id'] ?>">
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#konfirmasiHapusModal" 
                                                    data-pesan="Anda yakin ingin HAPUS penugasan (Admin: <?= htmlspecialchars($row['username']) ?>, Kategori: <?= htmlspecialchars($row['nama_kategori']) ?>)?"
                                                    data-form-id="form-hapus-penugasan-<?= $row['id'] ?>"
                                                    title="Hapus Penugasan">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                                  <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5.5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
                                                </svg>
                                            </button>
                                            <!-- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ -->
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada penugasan admin (sesuai filter).</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Hapus Admin Permanen -->
<hr class="my-4">

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white fw-semibold">
                Daftar Akun Admin (Hapus Permanen)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($daftar_semua_admin->num_rows > 0): ?>
                                <?php while($admin = $daftar_semua_admin->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($admin['username']) ?></td>
                                        <td>
                                            <?php if ($admin['is_superadmin'] == 1): ?>
                                                <span class="badge bg-primary">Admin Pusat</span>
                                            <?php elseif ($admin['can_ubah_status'] == 1): ?>
                                                <span class="badge bg-success">Admin Kategori (Eksekutor)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Admin Gedung (Monitor)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- ▼▼▼ PERBAIKAN V18: Logika Modal untuk Tombol Hapus ▼▼▼ -->
                                            <form method="POST" action="admin.php?page=manajemen_admin&<?= http_build_query($_GET) ?>" id="form-hapus-admin-<?= (int)$admin['id'] ?>">
                                                <input type="hidden" name="submit_form_id" value="form-hapus-admin-<?= (int)$admin['id'] ?>">
                                            </form>
                                            
                                            <?php 
                                            if ((int)$admin['id'] === (int)$_SESSION['user_id'] || (int)$admin['id'] === 1): 
                                            ?>
                                                <button type="button" class="btn btn-danger btn-sm" disabled>Hapus</button>
                                                <small class="text-muted">(Tidak bisa dihapus)</small>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-danger btn-sm"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#konfirmasiHapusModal" 
                                                        data-pesan="PERINGATAN: Menghapus admin '<?= htmlspecialchars($admin['username']) ?>' bersifat PERMANEN dan akan menghapus semua penugasannya. Anda yakin?"
                                                        data-form-id="form-hapus-admin-<?= (int)$admin['id'] ?>">
                                                    Hapus
                                                </button>
                                            <?php endif; ?>
                                            <!-- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ -->
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Tidak ada akun admin.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ▼▼▼ PERBAIKAN V18: HTML untuk Modal Konfirmasi ▼▼▼ -->
<div class="modal fade" id="konfirmasiHapusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="pesanModalBody">Anda yakin ingin menghapus ini?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="tombolHapusModal">Ya, Hapus</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const konfirmasiHapusModal = document.getElementById('konfirmasiHapusModal');
    const tombolHapusModal = document.getElementById('tombolHapusModal');
    const pesanModalBody = document.getElementById('pesanModalBody');
    let formIdToSubmit = null;

    konfirmasiHapusModal.addEventListener('show.bs.modal', function (event) {
        // Tombol yang memicu modal
        const button = event.relatedTarget;
        
        // Ambil data dari atribut data-bs-*
        const pesan = button.getAttribute('data-pesan');
        formIdToSubmit = button.getAttribute('data-form-id');
        
        // Perbarui konten modal
        pesanModalBody.textContent = pesan;
    });

    // Saat tombol "Ya, Hapus" di dalam modal diklik
    tombolHapusModal.addEventListener('click', function () {
        if (formIdToSubmit) {
            const formToSubmit = document.getElementById(formIdToSubmit);
            if (formToSubmit) {
                formToSubmit.submit();
            }
        }
    });
});
</script>
<!-- ▲▲▲ SELESAI PERBAIKAN V18 ▲▲▲ -->

