<?php
/**
 * Checkout API
 * POST - Process transaction
 */
require_once __DIR__ . '/../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || empty($input['items'])) {
    jsonResponse(['success' => false, 'message' => 'Data pesanan tidak valid']);
}

$db = getDB();

try {
    $db->beginTransaction();
    
    $invoiceNo = generateInvoiceNo();
    $totalHarga = floatval($input['total_harga']);
    $uangBayar = floatval($input['uang_bayar']);
    $kembalian = floatval($input['kembalian']);
    $userId = $_SESSION['user_id'];
    
    // Insert transaction
    $stmt = $db->prepare("INSERT INTO transactions (invoice_no, user_id, total_harga, uang_bayar, kembalian) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$invoiceNo, $userId, $totalHarga, $uangBayar, $kembalian]);
    $transactionId = $db->lastInsertId();
    
    // Insert transaction details
    $stmt = $db->prepare("INSERT INTO transaction_details (transaction_id, product_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($input['items'] as $item) {
        $qty = intval($item['qty']);
        $hargaSatuan = floatval($item['harga_satuan']);
        $subtotal = $qty * $hargaSatuan;
        $stmt->execute([$transactionId, $item['product_id'], $qty, $hargaSatuan, $subtotal]);
    }
    
    $db->commit();
    
    jsonResponse([
        'success' => true,
        'message' => 'Transaksi berhasil',
        'invoice_no' => $invoiceNo,
        'transaction_id' => $transactionId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success' => false, 'message' => 'Gagal memproses transaksi: ' . $e->getMessage()], 500);
}
