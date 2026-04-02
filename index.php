<?php
/**
 * Main Router - Cafe POS System
 * Routes all requests to appropriate views
 */
require_once __DIR__ . '/config/database.php';
startSession();

$page = isset($_GET['page']) ? $_GET['page'] : 'main_menu';

// If not logged in, force login page
if (!isLoggedIn() && $page !== 'login') {
    header('Location: /kasir/index.php?page=login');
    exit;
}

// If logged in and trying to access login, redirect to main menu
if (isLoggedIn() && $page === 'login') {
    header('Location: /kasir/index.php?page=main_menu');
    exit;
}

// Allowed pages
$allowedPages = ['login', 'main_menu', 'pos', 'products', 'reports', 'users', 'dashboard'];

// Role-based access control
$kasirPages = ['login', 'main_menu', 'pos', 'dashboard'];

if (!in_array($page, $allowedPages)) {
    $page = 'main_menu';
}

// If kasir tries to access admin pages
if (isLoggedIn() && $_SESSION['role'] === 'kasir' && !in_array($page, $kasirPages)) {
    header('Location: /kasir/index.php?page=main_menu');
    exit;
}

// Load the page view
$viewFile = __DIR__ . '/views/' . $page . '.php';
if (file_exists($viewFile)) {
    require_once $viewFile;
} else {
    echo '<h1>404 - Page Not Found</h1>';
}
