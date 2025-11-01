<?php
session_start();
include "koneksi.php";
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Hanya halaman yang diizinkan
$allowed_pages = ['dashboard', 'article', 'galeri', 'reporting', 'manajemen_admin']; // Tambahkan 'manajemen_admin'
$page = 'dashboard';
if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
    $page = $_GET['page'];
}

// Ambil info superadmin dari session
$is_superadmin = (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Aspirasi Mahasiswa - Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    :root {
      --biru-tua: #004c97;
      --biru-muda: #6ec6ff;
    }

    body {
      background: linear-gradient(135deg, #e6f2ff, #ffffff);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
    }

    nav.navbar {
      background: linear-gradient(90deg, var(--biru-tua), var(--biru-muda));
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    nav .navbar-brand {
      font-weight: 600;
      color: white !important;
      letter-spacing: 0.5px;
    }

    nav .nav-link {
      color: #f8f9fa !important;
      font-weight: 500;
      margin-right: 10px;
    }

    nav .nav-link:hover {
      text-decoration: underline;
    }

    section {
      background-color: rgba(255,255,255,0.85);
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      margin-top: 30px;
      padding: 25px;
    }

    footer {
      text-align: center;
      padding: 10px;
      margin-top: 50px;
      color: var(--biru-tua);
      font-size: 0.9rem;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-sm sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="admin.php">
      <img src="logo.jpg" alt="Logo" height="40" class="me-2 rounded-circle">
      Aspirasi Mahasiswa
    </a>
    <div class="collapse navbar-collapse show">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="admin.php?page=dashboard">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="admin.php?page=article">List Aspirasi</a></li>
        <li class="nav-item"><a class="nav-link" href="admin.php?page=reporting">Reporting</a></li>
        
        <!-- Menu Manajemen Admin (Hanya Superadmin) -->
        <?php if ($is_superadmin): ?>
            <li class="nav-item">
                <a class="nav-link" href="admin.php?page=manajemen_admin">Manajemen Admin</a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-semibold text-white" href="#" role="button" data-bs-toggle="dropdown">
            <?= htmlspecialchars($_SESSION['username']) ?>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- KONTEN -->
<section class="container">
  <?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] === 'success'): ?>
      <div class="alert alert-success text-center">✅ Aksi berhasil dilakukan.</div>
    <?php elseif($_GET['msg'] === 'fail'): ?>
      <div class="alert alert-danger text-center">❌ Terjadi kesalahan saat memproses data.</div>
    <?php elseif($_GET['msg'] === 'success_flag'): ?>
      <div class="alert alert-info text-center">🚩 Status Flag aspirasi berhasil diubah.</div>
    <?php elseif($_GET['msg'] === 'fail_perm'): ?>
      <div class="alert alert-danger text-center">❌ Anda tidak memiliki izin untuk melakukan aksi ini.</div>
    <?php endif; ?>
  <?php endif; ?>

  <h4 class="fw-bold text-primary mb-4"><?= ucfirst($page) ?></h4>
  <?php include $page . ".php"; ?>
</section>

<footer>
  © 2025 Universitas Dian Nuswantoro | Sistem Aspirasi Mahasiswa
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

