<?php
/**
 * Auth API - Login/Logout
 */
require_once __DIR__ . '/../config/database.php';
startSession();

// Handle logout via GET
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: /kasir/index.php?page=login');
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Username dan password wajib diisi.']);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Username atau password salah.']);
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];

    jsonResponse(['success' => true, 'message' => 'Login berhasil', 'user' => [
        'nama_lengkap' => $user['nama_lengkap'],
        'role' => $user['role']
    ]]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
