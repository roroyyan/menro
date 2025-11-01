<?php
// article.php
if (session_status() === PHP_SESSION_NONE) session_start();
include "koneksi.php";

// Cek akses
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Tentukan Izin
$role = $_SESSION['role'] ?? 'mahasiswa'; // <-- $role akan jadi 'mahasiswa' untuk rienn
$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_superadmin = (int)($_SESSION['is_superadmin'] ?? 0);
// Tentukan apakah dia boleh ubah status (Superadmin Boleh, Eksekutor Boleh, Monitor Tidak Boleh)
$can_ubah_status = ($is_superadmin == 1) || (isset($_SESSION['can_ubah_status']) && $_SESSION['can_ubah_status'] == 1);


// Tentukan scope admin (WHERE clause)
$whereAdminScope = "1"; // Default '1' (true) untuk superadmin & mahasiswa
if ($role === 'admin' && $is_superadmin == 0) {
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
        // (Tugas 1) OR (Tugas 2) ...
        $whereAdminScope = "(" . implode(' OR ', $scopes) . ")";
    }
}

// Ambil Kategori yang Boleh Dilihat (untuk kotak-kotak)
$visibleKategori = [];
$sqlKat = "
    SELECT DISTINCT k.id, k.nama_kategori
    FROM kategori k
    LEFT JOIN aspirasi a ON a.kategori_id = k.id
    WHERE $whereAdminScope
    ORDER BY k.nama_kategori
";
// Jika mahasiswa, $whereAdminScope = 1, tapi kita hanya mau tunjukkan kategori yg ADA isinya
if ($role === 'mahasiswa') {
     $sqlKat = "
        SELECT DISTINCT k.id, k.nama_kategori
        FROM kategori k
        JOIN aspirasi a ON a.kategori_id = k.id
        WHERE 1
        ORDER BY k.nama_kategori
    ";
}


$resKat = $koneksi->query($sqlKat);
if ($resKat) {
    while($row = $resKat->fetch_assoc()) {
        $visibleKategori[$row['id']] = $row['nama_kategori'];
    }
}

// Ambil hitungan (Count) untuk setiap kategori yang boleh dilihat
$counts = array_fill_keys(array_keys($visibleKategori), 0);
if (!empty($counts)) {
    $kat_ids_string = implode(',', array_keys($counts));
    
    $whereCount = "a.kategori_id IN ($kat_ids_string)";
    // Jika admin, $whereCount harus menghargai scope
    if ($role === 'admin') {
         $whereCount .= " AND ($whereAdminScope)";
    }

    $sqlCount = "
        SELECT a.kategori_id, COUNT(a.id) AS cnt
        FROM aspirasi a
        WHERE $whereCount
        GROUP BY a.kategori_id
    ";
    $resCount = $koneksi->query($sqlCount);
    if ($resCount) {
        while ($r = $resCount->fetch_assoc()) {
            if (isset($counts[$r['kategori_id']])) {
                $counts[$r['kategori_id']] = (int)$r['cnt'];
            }
        }
    }
}

// Ambil filter Kategori dari URL
$selectedCategoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Validasi apakah dia boleh lihat kategori yang dipilih
if ($selectedCategoryId > 0 && !isset($visibleKategori[$selectedCategoryId])) {
    // Jika admin, reset. Jika mahasiswa, biarkan saja (karena dia lihat semua)
    if ($role === 'admin') {
        $selectedCategoryId = 0; // Reset jika tidak diizinkan
    }
}

// Siapkan query untuk daftar aspirasi
$sqlList = "";
$whereList = $whereAdminScope; // Mulai dengan scope admin

if ($selectedCategoryId > 0) {
    // Jika kategori dipilih
    if ($role === 'admin') {
        $whereList = "($whereList) AND a.kategori_id = $selectedCategoryId";
    } else {
        // Mahasiswa hanya filter by kategori
        $whereList = "a.kategori_id = $selectedCategoryId";
    }
    
    $sqlList = "
        SELECT a.*, k.nama_kategori, g.nama_gedung, a.is_flagged
        FROM aspirasi a
        JOIN kategori k ON a.kategori_id = k.id
        JOIN gedung g ON a.gedung_id = g.id
        WHERE $whereList
        ORDER BY a.is_flagged DESC, a.tanggal DESC
    ";
} elseif ($role === 'mahasiswa') {
    // Mahasiswa lihat semua JIKA TIDAK ADA KATEGORI DIPILIH
     $sqlList = "
        SELECT a.*, k.nama_kategori, g.nama_gedung, a.is_flagged
        FROM aspirasi a
        JOIN kategori k ON a.kategori_id = k.id
        JOIN gedung g ON a.gedung_id = g.id
        WHERE 1
        ORDER BY a.is_flagged DESC, a.tanggal DESC
    ";
}
// Jika admin tapi tidak pilih kategori, $sqlList tetap kosong (tidak tampil tabel)

$resList = false;
$error = '';
if (!empty($sqlList)) {
    $resList = $koneksi->query($sqlList);
    if ($resList === false) {
        $error = $koneksi->error;
    }
}
?>

<div class="card">
  <div class="card-body">
    <!-- Tampilkan Kotak Kategori (jika tidak ada kategori dipilih) -->
    <?php if ($selectedCategoryId === 0): ?>
      <div class="mb-4">
        <h5 class="fw-semibold">
            <?php if ($role === 'admin'): ?>
                Kategori Aspirasi (Sesuai Scope Anda)
            <?php else: ?>
                Kategori Aspirasi
            <?php endif; ?>
        </h5>
        <div class="row g-3">
          <?php if (empty($visibleKategori)): ?>
            <div class="col-12">
              <div class="alert alert-warning">
                <?php if ($role === 'admin'): ?>
                    Tidak ada aspirasi yang sesuai dengan penugasan Anda.
                <?php else: ?>
                    Belum ada aspirasi yang masuk.
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($visibleKategori as $kat_id => $kat_nama): ?>
              <div class="col-6 col-md-3">
                <a href="admin.php?page=article&category_id=<?= $kat_id ?>" class="text-decoration-none">
                  <div class="border rounded p-3 h-100 shadow-sm" style="background:#ffffff;">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fw-semibold"><?= htmlspecialchars($kat_nama) ?></div>
                        <div class="small text-muted">Klik untuk lihat detail</div>
                      </div>
                      <div class="text-end">
                        <span class="badge bg-primary rounded-pill" style="font-size:0.9rem;"><?= $counts[$kat_id] ?? 0 ?></span>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- Tampilkan Tabel Aspirasi (jika kategori DIPILIH) -->
      
      <!-- Breadcrumb / Tombol Kembali -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="mb-0">Kategori: <span class="text-primary"><?= htmlspecialchars($visibleKategori[$selectedCategoryId]) ?></span></h5>
          <small class="text-muted">
            <?php if ($role === 'admin'): ?>
                <?= ($counts[$selectedCategoryId] ?? 0) ?> laporan (sesuai scope Anda)
            <?php else: ?>
                 <?= ($counts[$selectedCategoryId] ?? 0) ?> laporan
            <?php endif; ?>
          </small>
        </div>
        <div>
          <a href="admin.php?page=article" class="btn btn-outline-secondary btn-sm">Kembali ke Kategori</a>
        </div>
      </div>

      <!-- Tabel Aspirasi -->
      <div class="table-responsive">
        <?php if ($error): ?>
          <div class="alert alert-danger">Query error: <?= htmlspecialchars($error) ?></div>
        <?php else: ?>
          <table class="table table-bordered align-middle">
            <thead>
              <tr class="table-light text-center">
                <th style="width:40px">#</th>
                <th>Nama</th>
                <th>NIM</th>
                <th>Jurusan</th>
                <th>Kategori</th>
                <th>Gedung</th> <!-- Kolom Gedung -->
                <th>Aspirasi</th>
                <th style="width:120px">Status</th>
                <th style="width:150px">Tanggal</th>
                
                <!-- ▼▼▼ PERBAIKAN: Sembunyikan kolom ini dari mahasiswa ▼▼▼ -->
                <?php if ($can_ubah_status): ?><th style="width:160px">Aksi Eksekutor</th><?php endif; ?>
                <?php if ($role === 'admin'): ?><th style="width:100px">Aksi Lain</th><?php endif; ?>
                <!-- ▲▲▲ SELESAI PERBAIKAN ▲▲▲ -->

              </tr>
            </thead>
            <tbody>
              <?php
              if ($resList && $resList->num_rows > 0):
                $i = 1;
                while ($row = $resList->fetch_assoc()):
                  
                  // ▼▼▼ PERBAIKAN: Logika Flag hanya untuk admin ▼▼▼
                  $button_disabled = 'disabled';
                  $button_title = 'Hanya admin yang bisa menggunakan fitur ini.';
                  $button_action_text = 'Flag';
                  $button_class = 'btn-outline-danger';
                  $status = $row['status'];
                  $is_flagged = $row['is_flagged'];

                  if ($role === 'admin') {
                      $button_action_text = $is_flagged ? 'Un-Flag' : 'Flag';
                      $button_class = $is_flagged ? 'btn-danger' : 'btn-outline-danger';

                      if ($is_superadmin) {
                          // Superadmin bisa melakukan apa saja
                          $button_disabled = '';
                          $button_title = $is_flagged ? 'Batal Tandai (Superadmin)' : 'Tandai (Superadmin)';
                      
                      } elseif (!$can_ubah_status) { 
                          // Ini adalah MONITOR (Admin Gedung)
                          if (!$is_flagged) {
                              // Monitor HANYA bisa MENAMBAH flag
                              $button_disabled = '';
                              $button_title = 'Tandai Aspirasi Ini (Monitor)';
                          } else {
                              // Monitor TIDAK BISA Un-Flag
                              $button_title = 'Aspirasi sudah ditandai. Hanya Eksekutor/Superadmin yang bisa Un-Flag.';
                          }
                      
                      } elseif ($can_ubah_status) { 
                          // Ini adalah EKSEKUTOR (Admin Kategori)
                          if ($is_flagged) {
                              // Eksekutor HANYA bisa MENGHAPUS flag
                              if ($status === 'Selesai') {
                                  $button_disabled = '';
                                  $button_title = 'Batal Tandai (Tugas Selesai)';
                              } else {
                                  $button_title = 'Hanya bisa Un-Flag jika status aspirasi sudah Selesai.';
                              }
                          } else {
                              // Eksekutor TIDAK BISA Flag
                              $button_title = 'Hanya Monitor/Superadmin yang bisa Flag.';
                          }
                      }
                  }
                  // ▲▲▲ SELESAI PERBAIKAN ▲▲▲
              ?>
                <tr>
                  <td class="text-center"><?= $i++ ?></td>
                  <td><?= htmlspecialchars($row['nama']) ?></td>
                  <td><?= htmlspecialchars($row['nim']) ?></td>
                  <td><?= htmlspecialchars($row['jurusan']) ?></td>
                  <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                  <td><?= htmlspecialchars($row['nama_gedung']) ?></td>
                  
                  <!-- ▼▼▼ PERBAIKAN RATA TENGAH (v6) ▼▼▼ -->
                  <!-- Pindahkan 'text-center' ke DIV bagian dalam -->
                  <td style="max-width:380px;white-space:pre-wrap;"> 
                    <div class="text-center"> <!-- text-center ditaruh di sini -->
                      <?php if ($row['is_flagged'] == 1): ?>
                          <span class="badge bg-danger mb-1">🚩 PERLU TINDAKAN</span><br>
                      <?php endif; ?>
                      <?= nl2br(htmlspecialchars($row['isi_aspirasi'])) ?>
                    </div>
                  </td>
                  <!-- ▲▲▲ SELESAI PERBAIKAN ▲▲▲ -->

                  <td class="text-center">
                    <?php
                    $badge = 'secondary';
                    if ($row['status'] === 'Menunggu') $badge = 'warning';
                    if ($row['status'] === 'Diproses') $badge = 'info';
                    if ($row['status'] === 'Selesai') $badge = 'success';
                    ?>
                    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
                  </td>
                  <td class="text-center"><?= htmlspecialchars(date('d M Y, H:i', strtotime($row['tanggal']))) ?></td>

                  <!-- Kolom Aksi Eksekutor (Ubah Status) -->
                  <?php if ($can_ubah_status): ?>
                    <td>
                      <form method="post" action="update_status.php">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="return_page" value="article&category_id=<?= $selectedCategoryId ?>">
                        
                        <div class="d-flex gap-1">
                          <select name="status" class="form-select form-select-sm" style="width:110px;">
                            <option value="Menunggu" <?= $row['status']=='Menunggu'?'selected':'' ?>>Menunggu</option>
                            <option value="Diproses" <?= $row['status']=='Diproses'?'selected':'' ?>>Diproses</option>
                            <option value="Selesai" <?= $row['status']=='Selesai'?'selected':'' ?>>Selesai</option>
                          </select>
                          <button class="btn btn-sm btn-primary">Ubah</button>
                        </div>
                      </form>
                    </td>
                  <?php endif; ?>

                  <!-- ▼▼▼ PERBAIKAN: Sembunyikan kolom ini dari mahasiswa ▼▼▼ -->
                  <?php if ($role === 'admin'): ?>
                    <td class="text-center">
                        <form method="post" action="flag_aspirasi.php">
                            <input type="hidden" name="aspirasi_id" value="<?= (int)$row['id'] ?>">
                            <input type="hidden" name="return_page" value="article&category_id=<?= $selectedCategoryId ?>">
                            <button type="submit" class="btn btn-sm <?= $button_class ?>" 
                                    title="<?= htmlspecialchars($button_title) ?>" <?= $button_disabled ?>>
                                🚩 <?= $button_action_text ?>
                            </button>
                        </form>
                    </td>
                  <?php endif; ?>
                  <!-- ▲▲▲ SELESAI PERBAIKAN ▲▲▲ -->

                </tr>
              <?php endwhile; else: ?>
                
                <!-- ▼▼▼ PERBAIKAN: Colspan dinamis ▼▼▼ -->
                <?php
                  $colspan = 9; // Kolom dasar
                  if ($can_ubah_status) $colspan++; // Tambah 1 jika Eksekutor
                  if ($role === 'admin') $colspan++; // Tambah 1 jika Admin (Monitor/Eksekutor/Superadmin)
                ?>
                <tr><td colspan="<?= $colspan ?>" class="text-center text-muted">Belum ada aspirasi (sesuai scope Anda).</td></tr>
                <!-- ▲▲▲ SELESAI PERBAIKAN ▲▲▲ -->

              <?php endif; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

