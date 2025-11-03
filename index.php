<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aspirasi Mahasiswa - UDINUS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
      height: 100vh;
      margin: 0;
      font-family: 'Poppins', sans-serif;
      color: #fff;
      overflow: hidden;
      position: relative;
    }
    .overlay {
      position: absolute;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.25);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      top: 50%;
      transform: translateY(-50%);
    }
    h1 {
      font-weight: 700;
      font-size: 2.8rem;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
    }
    p.subtitle {
      font-size: 1.1rem;
      margin-bottom: 25px;
      color: #f5f5f5;
    }
    .btn-custom {
      background-color: #ffc107;
      border: none;
      color: #000;
      font-weight: 600;
      padding: 10px 30px;
      border-radius: 30px;
      transition: all 0.3s ease;
    }
    .btn-custom:hover {
      background-color: #ffca2c;
      transform: scale(1.05);
    }
    footer {
      position: absolute;
      bottom: 15px;
      width: 100%;
      text-align: center;
      font-size: 0.9rem;
      color: #f8f9fa;
      z-index: 2;
    }
    .logo {
      width: 130px;
      height: auto;
      border-radius: 50%;
      background: white;
      padding: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="overlay"></div>

  <div class="hero-content container">
    <img src="logo.jpg" alt="Logo UDINUS" class="logo">
    <h1>Aspirasi Mahasiswa</h1>
    <p class="subtitle">Suara Anda untuk Kemajuan Universitas Dian Nuswantoro</p>
    <a href="login.php" class="btn btn-custom shadow">Masuk ke Sistem</a>
  </div>

  <footer>
    &copy; <?= date('Y') ?> Sistem Aspirasi Mahasiswa | Universitas Dian Nuswantoro
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
