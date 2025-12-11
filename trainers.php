<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_login(); // All logged-in users can see this page

$pdo = get_pdo();
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE role = 'trainer' ORDER BY name");
$stmt->execute();
$trainers = $stmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<h2 class="mb-4">Pelatih Berpengalaman</h2>

<?php if (empty($trainers)): ?>
    <div class="alert alert-info">Saat ini belum ada pelatih yang terdaftar.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($trainers as $trainer): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="img/2.png" alt="Trainer" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                        <h5 class="card-title"><?= htmlspecialchars($trainer['name']); ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars($trainer['email']); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
