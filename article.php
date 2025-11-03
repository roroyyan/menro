<?php
// article.php
if (session_status() === PHP_SESSION_NONE) session_start();
include "koneksi.php";

// Cek akses
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Tentukan Izin (Variabel ini diambil dari 'admin.php', tapi kita definisikan ulang di sini untuk keamanan)
$role = $_SESSION['role'] ?? 'mahasiswa';
$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_superadmin = (int)($_SESSION['is_superadmin'] ?? 0); // <-- PENTING UNTUK TOMBOL HAPUS
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
          <!-- ▼▼▼ PERBAIKAN V19: 'align-middle' ditambahkan ke tabel ▼▼▼ -->
          <table class="table table-bordered table-striped table-hover align-middle">
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
                
                <?php if ($can_ubah_status): ?><th style="width:160px">Aksi Eksekutor</th><?php endif; ?>
                <?php if ($role === 'admin'): ?><th style="width:100px">Aksi Lain</th><?php endif; ?>
                
                <!-- ▼▼▼ TAMBAHAN BARU: Kolom Hapus ▼▼▼ -->
                <?php if ($is_superadmin): ?><th style="width:80px">Aksi Hapus</th><?php endif; ?>
                <!-- ▲▲▲ SELESAI TAMBAHAN ▲▲▲ -->

              </tr>
            </thead>
            <tbody>
              <?php
              if ($resList && $resList->num_rows > 0):
                $i = 1;
                while ($row = $resList->fetch_assoc()):
                  
                  // ▼▼▼ LOGIKA FLAG (DARI SEBELUMNYA) ▼▼▼
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
                          $button_disabled = '';
                          $button_title = $is_flagged ? 'Batal Tandai (Superadmin)' : 'Tandai (Superadmin)';
                      
                      } elseif (!$can_ubah_status) { 
                          if (!$is_flagged) {
                              $button_disabled = '';
                              $button_title = 'Tandai Aspirasi Ini (Monitor)';
                          } else {
                              $button_title = 'Aspirasi sudah ditandai. Hanya Eksekutor/Superadmin yang bisa Un-Flag.';
                          }
                      
                      } elseif ($can_ubah_status) { 
                          if ($is_flagged) {
                              if ($status === 'Selesai') {
                                  $button_disabled = '';
                                  $button_title = 'Batal Tandai (Tugas Selesai)';
                              } else {
                                  $button_title = 'Hanya bisa Un-Flag jika status aspirasi sudah Selesai.';
                              }
                          } else {
                              $button_title = 'Hanya Monitor/Superadmin yang bisa Flag.';
                          }
                      }
                  }
                  // ▲▲▲ SELESAI LOGIKA FLAG ▲▲▲
              ?>
                <tr>
                  <td class="text-center"><?= $i++ ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['nama']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['nim']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['jurusan']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($row['nama_gedung']) ?></td>
                  
                  <!-- ▼▼▼ PERBAIKAN V19: Kolom Aspirasi (Flexbox) ▼▼▼ -->
                  <td style="max-width:380px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                      <?php if ($row['is_flagged'] == 1): ?>
                          <span class="badge bg-danger mb-1">
                            🚩 PERLU TINDAKAN
                          </span>
                      <?php endif; ?>
                      <!-- Menghapus <span> pembungkus dan trim() data -->
                      <?= htmlspecialchars($row['isi_aspirasi']) ?>
                  </td>
                  <!-- ▲▲▲ SELESAI PERBAIKAN V19 ▲▲▲ -->

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
                    <td class="text-center"> 
                      <form method="post" action="update_status.php">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="return_page" value="article&category_id=<?= $selectedCategoryId ?>">
                        
                        <div class="d-flex gap-1 justify-content-center">
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

                  <!-- Kolom Aksi Lain (Flag) -->
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

                  <!-- ▼▼▼ TAMBAHAN BARU: Tombol Hapus (Hanya Superadmin) ▼▼▼ -->
                  <?php if ($is_superadmin): ?>
                    <td class="text-center">
                      <!-- Form tersembunyi untuk modal -->
                      <form method="POST" action="hapus_aspirasi.php" id="form-hapus-aspirasi-<?= (int)$row['id'] ?>">
                        <input type="hidden" name="submit_form_id" value="form-hapus-aspirasi-<?= (int)$row['id'] ?>">
                        <input type="hidden" name="category_id" value="<?= $selectedCategoryId ?>">
                      </form>
                      <!-- Tombol pemicu modal (data-bs-target harus #konfirmasiHapusModal) -->
                      <button type="button" class="btn btn-danger btn-sm" 
                              data-bs-toggle="modal" 
                              data-bs-target="#konfirmasiHapusModal" 
                              data-pesan="Anda yakin ingin HAPUS PERMANEN aspirasi ini (ID: <?= (int)$row['id'] ?>)?"
                              data-form-id="form-hapus-aspirasi-<?= (int)$row['id'] ?>"
                              title="Hapus Aspirasi Permanen">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                          <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5.5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
                        </svg>
                      </button>
                    </td>
                  <?php endif; ?>
                  <!-- ▲▲▲ SELESAI TAMBAHAN ▲▲▲ -->

                </tr>
              <?php endwhile; else: ?>
                
                <!-- ▼▼▼ PERBAIKAN: Colspan dinamis ▼▼▼ -->
                <?php
                  $colspan = 9; // Kolom dasar
                  if ($can_ubah_status) $colspan++; // Tambah 1 jika Eksekutor
                  if ($role === 'admin') $colspan++; // Tambah 1 jika Admin (Monitor/Eksekutor/Superadmin)
                  if ($is_superadmin) $colspan++; // Tambah 1 jika Superadmin (untuk Hapus)
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

