<?php
/**
 * Database Configuration - Cafe POS System
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'cafe_pos');
define('DB_USER', 'root');
define('DB_PASS', 'jedijaya01');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    startSession();
    if (!isLoggedIn()) return null;
    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role'         => $_SESSION['role']
    ];
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /kasir/index.php?page=login');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
        exit;
    }
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generate invoice number
 */
function generateInvoiceNo() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}
