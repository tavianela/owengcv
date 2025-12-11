<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function auth_register(string $name, string $email, string $password): bool {
    $pdo = get_pdo();
    // First user becomes owner, others default to customer
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $role = $count === 0 ? 'owner' : 'customer';

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flash('error', 'Email sudah terdaftar.');
        return false;
    }

    try {
        $pdo->beginTransaction();

        // Insert into users table
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt_users = $pdo->prepare('INSERT INTO users(name, email, password_hash, role) VALUES(?,?,?,?)');
        $stmt_users->execute([$name, $email, $hash, $role]);
        $user_id = $pdo->lastInsertId();

        // Insert corresponding entry into members table
        // The member ID should match the user ID to maintain consistency
        $stmt_members = $pdo->prepare('INSERT INTO members(id, name, email, phone, fitness_level, active) VALUES(?,?,?,?,?,?)');
        $stmt_members->execute([$user_id, $name, $email, '', 'Beginner', 1]);
        
        $pdo->commit();
        
        flash('success', 'Registrasi berhasil. Silakan login.');
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Registrasi gagal: ' . $e->getMessage());
        return false;
    }
}

function auth_login(string $email, string $password): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', 'Email atau password salah.');
        return false;
    }
    // Map legacy roles to new roles for compatibility
    $role = $user['role'];
    if ($role === 'admin') { $role = 'owner'; }
    elseif ($role === 'staff') { $role = 'trainer'; }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $role,
    ];
    return true;
}

function auth_logout() {
    unset($_SESSION['user']);
    session_destroy();
}

function is_admin(): bool {
    // Backward compatibility: treat 'admin' as owner if exists
    return is_owner();
}