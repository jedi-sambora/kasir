<?php
/**
 * Products API
 * GET - Returns all active products with category info (for POS)
 * Also supports manage operations for admin
 */
require_once __DIR__ . '/../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET - Fetch products
if ($method === 'GET') {
    // Get action
    $action = isset($_GET['action']) ? $_GET['action'] : 'active';
    
    if ($action === 'all') {
        // Admin: all products with category name
        $stmt = $db->query("SELECT p.*, c.nama_kategori as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY c.nama_kategori, p.nama_produk");
        $products = $stmt->fetchAll();
        
        $catStmt = $db->query("SELECT * FROM categories ORDER BY nama_kategori");
        $categories = $catStmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $products, 'categories' => $categories]);
    } else {
        // POS: only active products
        $stmt = $db->query("SELECT p.*, c.nama_kategori as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 1 ORDER BY c.nama_kategori, p.nama_produk");
        $products = $stmt->fetchAll();
        
        $catStmt = $db->query("SELECT DISTINCT c.* FROM categories c JOIN products p ON c.id = p.category_id WHERE p.status = 1 ORDER BY c.nama_kategori");
        $categories = $catStmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $products, 'categories' => $categories]);
    }
}

// POST - Create product (admin only)
if ($method === 'POST') {
    requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $action = isset($input['action']) ? $input['action'] : 'create';
    
    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO products (category_id, nama_produk, harga, image_url, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['category_id'],
            $input['nama_produk'],
            $input['harga'],
            isset($input['image_url']) ? $input['image_url'] : null,
            isset($input['status']) ? $input['status'] : 1
        ]);
        jsonResponse(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'id' => $db->lastInsertId()]);
    }
    
    if ($action === 'update') {
        $stmt = $db->prepare("UPDATE products SET category_id=?, nama_produk=?, harga=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([
            $input['category_id'],
            $input['nama_produk'],
            $input['harga'],
            isset($input['image_url']) ? $input['image_url'] : null,
            $input['status'],
            $input['id']
        ]);
        jsonResponse(['success' => true, 'message' => 'Produk berhasil diupdate']);
    }
    
    if ($action === 'delete') {
        // Get current product to clean up image
        $stmt = $db->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$input['id']]);
        $product = $stmt->fetch();
        
        // Check if product has transactions
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM transaction_details WHERE product_id = ?");
        $stmt->execute([$input['id']]);
        $count = $stmt->fetch()['cnt'];
        
        if ($count > 0) {
            // Soft delete - just deactivate
            $stmt = $db->prepare("UPDATE products SET status = 0 WHERE id = ?");
            $stmt->execute([$input['id']]);
            jsonResponse(['success' => true, 'message' => 'Produk dinonaktifkan (memiliki riwayat transaksi)']);
        } else {
            // Delete image file if exists
            if ($product && $product['image_url']) {
                $imagePath = __DIR__ . '/..' . $product['image_url'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$input['id']]);
            jsonResponse(['success' => true, 'message' => 'Produk berhasil dihapus']);
        }
    }
    
    // Category management
    if ($action === 'create_category') {
        $stmt = $db->prepare("INSERT INTO categories (nama_kategori) VALUES (?)");
        $stmt->execute([$input['nama_kategori']]);
        jsonResponse(['success' => true, 'message' => 'Kategori berhasil ditambahkan', 'id' => $db->lastInsertId()]);
    }
    
    if ($action === 'delete_category') {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE category_id = ?");
        $stmt->execute([$input['id']]);
        $count = $stmt->fetch()['cnt'];
        
        if ($count > 0) {
            jsonResponse(['success' => false, 'message' => 'Kategori masih memiliki produk, tidak bisa dihapus']);
        }
        
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$input['id']]);
        jsonResponse(['success' => true, 'message' => 'Kategori berhasil dihapus']);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
