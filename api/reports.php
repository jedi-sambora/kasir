<?php
/**
 * Reports / Dashboard Data API
 * Returns analytics data for charts and summaries
 */
require_once __DIR__ . '/../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

// Dashboard summary
if ($action === 'dashboard') {
    // Today's stats
    $today = date('Y-m-d');
    
    // Revenue today
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_harga), 0) as revenue FROM transactions WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $revenueToday = $stmt->fetch()['revenue'];
    
    // Transactions today
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $txToday = $stmt->fetch()['cnt'];
    
    // Total products active
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM products WHERE status = 1");
    $totalProducts = $stmt->fetch()['cnt'];
    
    // Average transaction value today
    $avgTx = $txToday > 0 ? round($revenueToday / $txToday) : 0;
    
    // Revenue this month
    $monthStart = date('Y-m-01');
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_harga), 0) as revenue FROM transactions WHERE DATE(created_at) >= ?");
    $stmt->execute([$monthStart]);
    $revenueMonth = $stmt->fetch()['revenue'];
    
    jsonResponse([
        'success' => true,
        'summary' => [
            'revenue_today' => $revenueToday,
            'transactions_today' => $txToday,
            'total_products' => $totalProducts,
            'avg_transaction' => $avgTx,
            'revenue_month' => $revenueMonth
        ]
    ]);
}

// Daily sales for chart
if ($action === 'daily_sales') {
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT DATE(created_at) as tanggal, 
               SUM(total_harga) as total, 
               COUNT(*) as jumlah_transaksi
        FROM transactions 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at) 
        ORDER BY tanggal ASC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// Top selling products
if ($action === 'top_products') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT p.nama_produk, SUM(td.qty) as total_qty, SUM(td.subtotal) as total_revenue
        FROM transaction_details td
        JOIN products p ON td.product_id = p.id
        JOIN transactions t ON td.transaction_id = t.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY td.product_id, p.nama_produk
        ORDER BY total_qty DESC
        LIMIT ?
    ");
    $stmt->execute([$dateFrom, $dateTo, $limit]);
    $data = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// Revenue by category
if ($action === 'category_revenue') {
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT c.nama_kategori, SUM(td.subtotal) as total_revenue
        FROM transaction_details td
        JOIN products p ON td.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN transactions t ON td.transaction_id = t.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY c.id, c.nama_kategori
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'data' => $data]);
}

// Transaction history
if ($action === 'transactions') {
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT t.*, u.nama_lengkap as kasir_name
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll();
    
    // Total
    $stmt2 = $db->prepare("
        SELECT COALESCE(SUM(total_harga), 0) as total_revenue, COUNT(*) as total_transactions
        FROM transactions 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt2->execute([$dateFrom, $dateTo]);
    $summary = $stmt2->fetch();
    
    jsonResponse(['success' => true, 'data' => $data, 'summary' => $summary]);
}

// Item-level detail report (all items across transactions)
if ($action === 'item_details') {
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d');
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT 
            t.invoice_no,
            t.created_at,
            u.nama_lengkap as kasir_name,
            c.nama_kategori as kategori,
            p.nama_produk,
            td.qty,
            td.harga_satuan,
            td.subtotal
        FROM transaction_details td
        JOIN transactions t ON td.transaction_id = t.id
        JOIN products p ON td.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        ORDER BY t.created_at DESC, t.id, td.id
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll();
    
    // Summary
    $stmt2 = $db->prepare("
        SELECT 
            COALESCE(SUM(td.subtotal), 0) as total_revenue,
            COALESCE(SUM(td.qty), 0) as total_items,
            COUNT(DISTINCT t.id) as total_transactions
        FROM transaction_details td
        JOIN transactions t ON td.transaction_id = t.id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
    ");
    $stmt2->execute([$dateFrom, $dateTo]);
    $summary = $stmt2->fetch();
    
    jsonResponse(['success' => true, 'data' => $data, 'summary' => $summary]);
}

// Transaction detail
if ($action === 'transaction_detail') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $stmt = $db->prepare("
        SELECT td.*, p.nama_produk
        FROM transaction_details td
        JOIN products p ON td.product_id = p.id
        WHERE td.transaction_id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'data' => $data]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
