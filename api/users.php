<?php
/**
 * Users API
 * CRUD for user management (admin only)
 */
require_once __DIR__ . '/../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Fetch users
if ($method === 'GET') {
    requireAdmin();
    $stmt = $db->query("SELECT id, username, nama_lengkap, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    jsonResponse(['success' => true, 'data' => $users]);
}

// POST - Create/Update/Delete
if ($method === 'POST') {
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $action = isset($input['action']) ? $input['action'] : 'create';
    
    if ($action === 'create') {
        // Check username exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$input['username']]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Username sudah digunakan']);
        }
        
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $input['username'],
            $hashedPassword,
            $input['nama_lengkap'],
            $input['role']
        ]);
        jsonResponse(['success' => true, 'message' => 'User berhasil ditambahkan']);
    }
    
    if ($action === 'update') {
        // Check if username conflict
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$input['username'], $input['id']]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Username sudah digunakan']);
        }
        
        if (!empty($input['password'])) {
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username=?, password=?, nama_lengkap=?, role=? WHERE id=?");
            $stmt->execute([$input['username'], $hashedPassword, $input['nama_lengkap'], $input['role'], $input['id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username=?, nama_lengkap=?, role=? WHERE id=?");
            $stmt->execute([$input['username'], $input['nama_lengkap'], $input['role'], $input['id']]);
        }
        jsonResponse(['success' => true, 'message' => 'User berhasil diupdate']);
    }
    
    if ($action === 'delete') {
        // Don't allow deleting self
        if ($input['id'] == $_SESSION['user_id']) {
            jsonResponse(['success' => false, 'message' => 'Tidak bisa menghapus akun sendiri']);
        }
        
        // Check if user has transactions
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE user_id = ?");
        $stmt->execute([$input['id']]);
        $count = $stmt->fetch()['cnt'];
        
        if ($count > 0) {
            jsonResponse(['success' => false, 'message' => 'User memiliki riwayat transaksi, tidak bisa dihapus']);
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$input['id']]);
        jsonResponse(['success' => true, 'message' => 'User berhasil dihapus']);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
