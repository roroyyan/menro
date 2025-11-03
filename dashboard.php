<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "koneksi.php";

// Cek sesi
if (!isset($_SESSION['role'])) {
    echo '<div class="alert alert-warning text-center mt-5">Session tidak ditemukan. Silakan login ulang.</div>';
    exit;
}

// Ambil data user dari Sesi
// !! PENTING: Pastikan file login.php Anda menyimpan 'user_id' dan 'is_superadmin' ke Sesi
$role = $_SESSION['role'];
$admin_id = $_SESSION['user_id'] ?? 0; // Kita butuh ID admin
$is_superadmin = $_SESSION['is_superadmin'] ?? 0; // Kita butuh status superadmin

// Jika role admin -> tampilkan dashboard admin
if ($role === 'admin') {

    // =======================================================
    // LOGIKA BARU UNTUK FILTER DATA ADMIN
    // =======================================================
    $where = "0"; // Default-nya, admin tidak bisa melihat apa-apa

    if ($is_superadmin == 1) {
        // Superadmin (Pusat) bisa lihat semua
        $where = "1";
    } else {
        // Admin biasa (per gedung/kategori)
        // Kita cek apa saja penugasan dia dari tabel admin_penugasan
        
        $sql_tugas = "SELECT kategori_id, gedung_id FROM admin_penugasan WHERE user_id = ?";
        $stmt_tugas = $koneksi->prepare($sql_tugas);
        $stmt_tugas->bind_param("i", $admin_id);
        $stmt_tugas->execute();
        $hasil_tugas = $stmt_tugas->get_result();
        
        $scopes = [];
        if ($hasil_tugas->num_rows > 0) {
            while ($tugas = $hasil_tugas->fetch_assoc()) {
                // Kumpulkan semua "scope" kerja dia
                // Cth: (kategori_id = 1 AND gedung_id = 6)
                $scopes[] = "(a.kategori_id = " . intval($tugas['kategori_id']) . " AND a.gedung_id = " . intval($tugas['gedung_id']) . ")";
            }
            // Gabungkan semua scope
            // Cth: (k_id=1 AND g_id=6) OR (k_id=2 AND g_id=6)
            $where = implode(" OR ", $scopes);
        }
        // Jika $scopes kosong, $where akan tetap "0" (tidak bisa lihat data)
    }
    // =======================================================
    // AKHIR LOGIKA BARU
    // =======================================================

    // Statistik (Query tetap, hanya $where yang berubah)
    // Kita tambahkan alias 'a' untuk tabel aspirasi agar konsisten
    $total = $koneksi->query("SELECT COUNT(*) AS total FROM aspirasi a WHERE $where")->fetch_assoc()['total'] ?? 0;
    $menunggu = $koneksi->query("SELECT COUNT(*) AS total FROM aspirasi a WHERE a.status='Menunggu' AND ($where)")->fetch_assoc()['total'] ?? 0;
    $diproses = $koneksi->query("SELECT COUNT(*) AS total FROM aspirasi a WHERE a.status='Diproses' AND ($where)")->fetch_assoc()['total'] ?? 0;
    $selesai = $koneksi->query("SELECT COUNT(*) AS total FROM aspirasi a WHERE a.status='Selesai' AND ($where)")->fetch_assoc()['total'] ?? 0;

    // Data terbaru
    // --- QUERY DIUBAH ---
    // Kita harus JOIN ke tabel kategori dan gedung untuk dapat NAMA-nya
    $sql_aspirasi = "SELECT a.*, k.nama_kategori, g.nama_gedung 
                     FROM aspirasi a 
                     LEFT JOIN kategori k ON a.kategori_id = k.id
                     LEFT JOIN gedung g ON a.gedung_id = g.id
                     WHERE $where 
                     ORDER BY a.tanggal DESC 
                     LIMIT 5";
    $aspirasi = $koneksi->query($sql_aspirasi);
    ?>

    <!-- =================== DASHBOARD ADMIN (Versi Baru) =================== -->
    <div class="container-fluid px-4">
      <h3 class="mt-3 mb-4 text-center fw-bold text-primary">Dashboard Aspirasi Mahasiswa</h3>

      <!-- Statistik -->
      <div class="row g-4 mb-4">
        <div class="col-md-3">
          <div class="card text-bg-primary shadow-sm border-0">
            <div class="card-body text-center">
              <h5>Total Aspirasi</h5>
              <h2><?= $total ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-bg-warning shadow-sm border-0">
            <div class="card-body text-center">
              <h5>Menunggu</h5>
              <h2><?= $menunggu ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-bg-info shadow-sm border-0">
            <div class="card-body text-center">
              <h5>Diproses</h5>
              <h2><?= $diproses ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-bg-success shadow-sm border-0">
            <div class="card-body text-center">
              <h5>Selesai</h5>
              <h2><?= $selesai ?></h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Daftar Aspirasi Terbaru -->
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white fw-semibold">
          📋 Aspirasi Terbaru (Scope: <?= $is_superadmin ? 'Semua' : 'Sesuai Penugasan' ?>)
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Nama</th>
                  <th>NIM</th>
                  <th>Jurusan</th>
                  <th>Gedung</th> <!-- KOLOM BARU -->
                  <th>Kategori</th> <!-- KOLOM DIUBAH -->
                  <th>Aspirasi</th>
                  <th>Status</th>
                  <th>Tanggal</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($aspirasi && $aspirasi->num_rows > 0): ?>
                  <?php $i=1; while ($row = $aspirasi->fetch_assoc()): ?>
                    <tr>
                      <td><?= $i++ ?></td>
                      <td><?= htmlspecialchars($row['nama']) ?></td>
                      <td><?= htmlspecialchars($row['nim']) ?></td>
                      <td><?= htmlspecialchars($row['jurusan']) ?></td>
                      <td><?= htmlspecialchars($row['nama_gedung'] ?? 'N/A') ?></td> <!-- DATA BARU -->
                      <td><?= htmlspecialchars($row['nama_kategori'] ?? 'N/A') ?></td> <!-- DATA DIUBAH -->
                      <td style="max-width:300px;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($row['isi_aspirasi'])) ?></td>
                      <td>
                        <?php
                        $badge = 'secondary';
                        if ($row['status'] === 'Menunggu') $badge = 'warning';
                        if ($row['status'] === 'Diproses') $badge = 'info';
                        if ($row['status'] === 'Selesai') $badge = 'success';
                        ?>
                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
                      </td>
                      <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="9" class="text-center text-muted">Belum ada aspirasi yang masuk sesuai scope Anda</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="text-center mt-3">
            <a href="admin.php?page=article" class="btn btn-outline-primary">Lihat Semua</a>
          </div>
        </div>
      </div>
    </div>

<?php
// =================== AKHIR DASHBOARD ADMIN ===================
} else {
    // =================== DASHBOARD MAHASISWA (Formulir) ===================
    
    // --- LOGIKA BARU: Ambil data Gedung & Kategori dari DB ---
    $gedung_list = $koneksi->query("SELECT * FROM gedung ORDER BY nama_gedung ASC");
    $kategori_list = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    // --- AKHIR LOGIKA BARU ---
    
    ?>

    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <title>Form Aspirasi Mahasiswa - UDINUS</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <!-- (Salin semua <style> Anda yang lama ke sini, tidak ada yg berubah) -->
       <style>
         :root {
           --biru-tua: #004c97;
           --biru-muda: #6ec6ff;
           --gradient: linear-gradient(135deg, #b3e5ff, #6ec6ff, #004c97);
         }

         body {
           background: var(--gradient);
           background-size: 200% 200%;
           animation: gradientMove 10s ease infinite;
           font-family: 'Poppins', sans-serif;
           margin: 0;
           display: flex;
           flex-direction: column;
           min-height: 100vh;
         }

         @keyframes gradientMove {
           0% { background-position: 0% 50%; }
           50% { background-position: 100% 50%; }
           100% { background-position: 0% 50%; }
         }

         header {
           width: 100%;
           background: linear-gradient(135deg, #3fa9f5, #004c97);
           color: white;
           text-align: center;
           padding: 18px 0;
           font-size: 1.2rem;
           font-weight: 600;
           letter-spacing: 0.5px;
           box-shadow: 0 3px 10px rgba(0,0,0,0.2);
         }

         main {
           flex: 1;
           display: flex;
           justify-content: center;
           align-items: center;
           padding: 40px 15px;
         }

         .form-wrapper {
           width: 100%;
           max-width: 360px;
           background: rgba(255, 255, 255, 0.15);
           backdrop-filter: blur(10px);
           border-radius: 16px;
           padding: 25px 20px;
           box-shadow: 0 8px 20px rgba(0, 76, 151, 0.25);
           animation: fadeIn 0.8s ease;
         }

         @keyframes fadeIn {
           from { opacity: 0; transform: translateY(25px); }
           to { opacity: 1; transform: translateY(0); }
         }

         h4 {
           text-align: center;
           color: #004c97;
           font-weight: 600;
           margin-bottom: 20px;
         }

         .form-label {
           font-weight: 500;
           color: #003366;
           font-size: 0.9rem;
         }

         .form-control, .form-select {
           border: 1.4px solid #cde6ff;
           border-radius: 10px;
           padding: 8px 10px;
           font-size: 0.9rem;
           background: #ffffff;
           transition: 0.3s;
         }

         .form-control:focus, .form-select:focus {
           border-color: #3fa9f5;
           box-shadow: 0 0 0 0.2rem rgba(63,169,245,0.25);
         }

         .btn-primary {
           background: linear-gradient(135deg, #3fa9f5, #004c97);
           border: none;
           border-radius: 8px;
           transition: all 0.3s ease;
           font-size: 0.9rem;
           padding: 10px;
         }

         .btn-primary:hover {
           background: linear-gradient(135deg, #66ccff, #005bbb);
           transform: translateY(-2px);
           box-shadow: 0 6px 15px rgba(0,76,151,0.3);
         }

         #progress {
           transition: 0.3s;
           font-size: 0.9rem;
         }
       </style>
    </head>
    <body>
    <header>Sistem Aspirasi Mahasiswa UDINUS</header>
    <main>
      <div class="form-wrapper">
        <h4>Formulir Aspirasi</h4>
        <form method="post" action="proses_aspirasi.php" onsubmit="showProgress()">
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input name="nama" class="form-control" value="<?= $_SESSION['username'] ?? '' ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">NIM</label>
            <input name="nim" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Jurusan</label>
            <input name="jurusan" class="form-control" placeholder="Contoh: Teknik Informatika">
          </div>

          <!-- ============================================== -->
          <!-- ▼▼▼ BLOK FORM YANG DIUBAH (GEDUNG & KATEGORI) ▼▼▼ -->
          <!-- ============================================== -->

          <div class="mb-3">
            <label class="form-label">Gedung / Lokasi</label>
            <select name="gedung_id" class="form-select" required>
              <option value="">-- Pilih Gedung --</option>
              <?php while($gedung = $gedung_list->fetch_assoc()): ?>
                <option value="<?= $gedung['id'] ?>">
                  <?= htmlspecialchars($gedung['nama_gedung']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Kategori</label>
            <select name="kategori_id" class="form-select" required>
              <option value="">-- Pilih Kategori Aspirasi --</option>
              <?php while($kategori = $kategori_list->fetch_assoc()): ?>
                <option value="<?= $kategori['id'] ?>">
                  <?= htmlspecialchars($kategori['nama_kategori']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          
          <!-- ============================================== -->
          <!-- ▲▲▲ AKHIR BLOK FORM YANG DIUBAH ▲▲▲ -->
          <!-- ============================================== -->

          <div class="mb-3">
            <label class="form-label">Isi Aspirasi</label>
            <textarea name="isi_aspirasi" rows="3" class="form-control" required></textarea>
          </div>

          <div id="progress" class="mb-3 text-center" style="display:none;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Mengirim aspirasi...</p>
          </div>

          <div class="d-grid">
            <button class="btn btn-primary" type="submit">Kirim Aspirasi</button>
          </div>
        </form>
      </div>
    </main>
    <script>
    function showProgress() {
      document.getElementById('progress').style.display = 'block';
    }
    </script>
    </body>
    </html>

<?php
} // end if role === 'admin'
?>