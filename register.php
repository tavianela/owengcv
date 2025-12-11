<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');
    if ($password !== $confirm) {
        flash('error', 'Konfirmasi password tidak cocok.');
    } else if ($name && $email && $password) {
        if (auth_register($name, $email, $password)) {
            header('Location: /FozGym/login.php');
            exit;
        }
    } else {
        flash('error', 'Isi semua field.');
    }
}
include __DIR__ . '/partials/header.php';
?>
<h2 class="mb-3">Daftar Akun</h2>
<form method="post" class="row g-3" style="max-width:520px">
  <div class="col-12">
    <label class="form-label">Nama Lengkap</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Konfirmasi Password</label>
    <input type="password" name="confirm" class="form-control" required>
  </div>
  <div class="col-12">
    <button class="btn btn-success">Buat Akun</button>
    <a class="ms-2" href="/FozGym/login.php">Sudah punya akun?</a>
  </div>
</form>
<?php include __DIR__ . '/partials/footer.php'; ?>