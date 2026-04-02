<?php
/**
 * Upload API - Handle product image uploads
 * Accepts multipart/form-data with 'image' field
 */
require_once __DIR__ . '/../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Tidak ada file yang diupload';
    if (isset($_FILES['image'])) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        ];
        $errorMsg = isset($errors[$_FILES['image']['error']]) ? $errors[$_FILES['image']['error']] : 'Error upload tidak diketahui';
    }
    jsonResponse(['success' => false, 'message' => $errorMsg]);
}

$file = $_FILES['image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    jsonResponse(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP']);
}

// Validate file size (max 2MB)
$maxSize = 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!$ext) {
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $ext = $extMap[$mimeType] ?? 'jpg';
}
$filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);

$uploadDir = __DIR__ . '/../uploads/products/';
$targetPath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(['success' => false, 'message' => 'Gagal menyimpan file']);
}

// Return the relative URL path
$imageUrl = '/kasir/uploads/products/' . $filename;

jsonResponse([
    'success' => true,
    'message' => 'Gambar berhasil diupload',
    'image_url' => $imageUrl,
    'filename' => $filename
]);
