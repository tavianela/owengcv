<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $login_as = trim($_POST['login_as'] ?? '');
    if ($email && $password && auth_login($email, $password)) {
        $actual = current_user()['role'] ?? 'customer';
        $allowed_map = [
            'owner'   => ['owner','trainer','customer'],
            'trainer' => ['trainer','customer'],
            'customer'=> ['customer']
        ];
        $allowed = $allowed_map[$actual] ?? ['customer'];
        if ($login_as === '') { $login_as = $actual; }
        if (!in_array($login_as, $allowed, true)) {
            flash('error', 'Anda tidak diizinkan login sebagai peran tersebut.');
            auth_logout();
        } else {
            $_SESSION['user']['role'] = $login_as;
            header('Location: /FozGym/dashboard.php');
            exit;
        }
    }
}
include __DIR__ . '/partials/header.php';
?>
<h2 class="mb-3">Login</h2>
<form method="post" class="row g-3" style="max-width:480px">
  <div class="col-12">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Masuk sebagai</label>
    <select name="login_as" class="form-select">
      <option value="">(Default sesuai peran akun)</option>
      <option value="owner">Owner</option>
      <option value="trainer">Trainer</option>
      <option value="customer">Customer</option>
    </select>
  </div>
  <div class="col-12 d-flex justify-content-between align-items-center">
    <button class="btn btn-primary">Masuk</button>
    <a href="/FozGym/register.php">Belum punya akun?</a>
  </div>
</form>
<?php include __DIR__ . '/partials/footer.php'; ?>