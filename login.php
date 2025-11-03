<?php
session_start();
include 'koneksi.php';
$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $stored = $user['password'];
        $ok = false;

        if (strlen($stored) === 32) {
            if (md5($password) === $stored) $ok = true;
        } else {
            if (password_verify($password, $stored)) $ok = true;
        }

        if ($ok) {
            // =======================================================
            // ▼▼▼ PERUBAHAN LOGIKA SESSION (INI YANG PENTING) ▼▼▼
            // =======================================================
            $_SESSION['user_id'] = $user['id']; // Simpan ID
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_superadmin'] = $user['is_superadmin'];
            // ▼▼▼ TAMBAHAN BARU: Simpan izin ubah status ▼▼▼
            $_SESSION['can_ubah_status'] = $user['can_ubah_status'];
            // Hapus session 'divisi' yang lama
            unset($_SESSION['divisi']); 
            // =======================================================
            // ▲▲▲ SELESAI PERUBAHAN ▲▲▲
            // =======================================================
            
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Username tidak ditemukan.';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <!-- ▼▼▼ PERUBAHAN 1: TAMBAHKAN VIEWPORT META TAG INI ▼▼▼ -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- ▲▲▲ SELESAI PERUBAHAN 1 ▲▲▲ -->
  <title>Login - Aspirasi Mahasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #4facfe, #00f2fe);
      min-height: 100vh; /* Ganti 'height' jadi 'min-height' agar bisa scroll jika perlu */
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem 0; /* Beri sedikit padding atas/bawah */
    }

    /* ▼▼▼ PERUBAHAN 2: UBAH STYLE LOGIN-CARD ▼▼▼ */
    .login-card {
      /* HAPUS 'width: 360px;' */
      /* width: 360px; */ 

      /* GANTI DENGAN INI: */
      max-width: 380px;  /* Lebar maksimalnya 380px */
      width: 100%;      /* Tapi di HP, lebarnya 100% */
      margin: 0 15px;   /* Beri jarak 15px di kanan-kiri layar HP */
      /* ▲▲▲ SELESAI PERUBAHAN 2 ▲▲▲ */

      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    /* (Sisa style Anda yang lain sudah bagus) */
    .logo {
      width: 80px;
      height: 80px;
      margin-bottom: 15px;
    }
    .card-header {
      background-color: #e3f2fd;
      text-align: center;
      padding: 20px;
    }
    .card-body {
      background: #ffffff;
      padding: 25px;
    }
    .btn-primary {
      background-color: #4facfe;
      border: none;
    }
    .btn-primary:hover {
      background-color: #00f2fe;
    }
  </style>
</head>
<body>
  <!-- Stuktur HTML Anda di sini sudah SANGAT BAGUS. -->
  <!-- Anda sudah pakai class Bootstrap seperti 'mb-3', 'form-control', 'd-grid', 'alert' -->
  <!-- Jadi, tidak ada yang perlu diubah di bagian HTML body. -->
  <div class="login-card">
    <div class="card-header">
      <img src="logo.jpg" alt="Logo Kampus" class="logo rounded-circle">
      <h5 class="mb-0">Login Aspirasi Mahasiswa</h5>
    </div>
    <div class="card-body">
      <?php if($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" action="">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input name="username" required class="form-control" placeholder="Masukkan username">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input name="password" type="password" required class="form-control" placeholder="Masukkan password">
        </div>
        <div class="d-grid">
          <button name="login" class="btn btn-primary">Login</button>
        </div>
        <div class="d-grid mt-2">
          <a href="register.php" class="btn btn-outline-primary">Registrasi</a>
        </div>
        <div class="text-center mt-3">
          <a href="index.php" class="text-decoration-none text-muted">← Kembali ke Beranda</a>
        </div>
      </form>
      <hr>

    </div>
  </div>
  <!-- Kita tidak butuh Bootstrap JS di sini karena tidak ada dropdown/modal -->
</body>
</html>

