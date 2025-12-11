<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_login();
include __DIR__ . '/partials/header.php';
$pdo = get_pdo();
$member_count = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$class_count  = (int)$pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$enroll_count = (int)$pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
?>
<h2 class="mb-4">Dashboard</h2>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <div class="card-icon"><img src="img/4.jpeg" alt="Total Member Logo" class="card-icon-img"></div>
      <h5 class="card-title">Total Member</h5>
      <p class="display-6 mb-0"><?= $member_count; ?></p>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <div class="card-icon"><img src="img/6.jpeg" alt="Total Kelas Logo" class="card-icon-img"></div>
      <h5 class="card-title">Total Kelas</h5>
      <p class="display-6 mb-0"><?= $class_count; ?></p>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <div class="card-icon"><img src="img/5.jpeg" alt="Total Pendaftaran Logo" class="card-icon-img"></div>
      <h5 class="card-title">Total Pendaftaran</h5>
      <p class="display-6 mb-0"><?= $enroll_count; ?></p>
    </div></div>
  </div>
</div>
<div class="mt-4">
  <a class="btn btn-primary me-2" href="/FozGym/members/index.php">Kelola Member</a>
  <a class="btn btn-secondary me-2" href="/FozGym/classes/index.php">Kelola Kelas</a>
  <a class="btn btn-info me-2" href="/FozGym/enrollments/index.php">Kelola Pendaftaran</a>
  <a class="btn btn-success" href="/FozGym/ai.php">AI Coach</a>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>