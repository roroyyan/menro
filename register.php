<?php
session_start();
include 'koneksi.php';
$error = '';
$success = '';

if (isset($_POST['register'])) {
    // Membersihkan spasi aneh (jika ada)
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $role = 'mahasiswa'; // 🔹 role otomatis mahasiswa

    if ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($username) < 4 || strlen($password) < 5) {
        $error = 'Username minimal 4 karakter dan password minimal 5 karakter.';
    } else {
        // cek apakah username sudah digunakan
        $cek = $koneksi->prepare("SELECT * FROM user WHERE username = ?");
        $cek->bind_param('s', $username);
        $cek->execute();
        $res = $cek->get_result();

        if ($res->num_rows > 0) {
            $error = 'Username sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $koneksi->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $hash, $role);

            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  
  <!-- ▼▼▼ PERBAIKAN 1: TAMBAHKAN VIEWPORT AGAR TIDAK DI-ZOOM OUT DI HP ▼▼▼ -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- ▲▲▲ SELESAI PERBAIKAN 1 ▲▲▲ -->

  <title>Registrasi - Aspirasi Mahasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #4facfe, #00f2fe);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      /* Tambahkan padding sedikit agar card tidak menempel di tepi HP */
      padding: 10px 0;
    }
    .login-card {
      /* ▼▼▼ PERBAIKAN 2: UBAH 'width' MENJADI 'max-width' (SAMA SEPERTI LOGIN.PHP) ▼▼▼ */
      max-width: 380px; /* Lebar maksimal di desktop */
      width: 90%;       /* Lebar di HP (90% agar ada spasi di samping) */
      /* ▲▲▲ SELESAI PERBAIKAN 2 ▲▲▲ */

      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
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
  <div class="login-card">
    <div class="card-header">
      <img src="logo.jpg" alt="Logo Kampus" class="logo rounded-circle">
      <h5 class="mb-0">Registrasi Akun Mahasiswa</h5>
    </div>
    <div class="card-body">
      <?php if($error): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
      <?php elseif($success): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input name="username" required class="form-control" placeholder="Masukkan username baru">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input name="password" type="password" required class="form-control" placeholder="Masukkan password">
        </div>
        <div class="mb-3">
          <label class="form-label">Konfirmasi Password</label>
          <input name="confirm" type="password" required class="form-control" placeholder="Ulangi password">
        </div>

        <!-- role ditampilkan hanya sebagai info -->
        <div class="mb-3">
          <label class="form-label">Peran</label>
          <input type="text" class="form-control" value="Mahasiswa" readonly>
        </div>

        <div class="d-grid">
          <button name="register" class="btn btn-primary">Daftar</button>
        </div>
        <div class="text-center mt-3">
          <a href="login.php" class="text-decoration-none text-muted">← Kembali ke Login</a>
        </div>
      </form>
    </div>
  </div>

  <!-- ▼▼▼ PERBAIKAN 3: TAMBAHKAN BOOTSTRAP JS (SAMA SEPERTI LOGIN.PHP) ▼▼▼ -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- ▲▲▲ SELESAI PERBAIKAN 3 ▲▲▲ -->

</body>
</html>
