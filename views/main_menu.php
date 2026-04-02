<?php
/**
 * Main Menu - Card-based navigation hub
 */
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Menu Utama Sistem POS Kafe">
    <title>Menu Utama — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.common-material.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.material.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="main-menu-page">
        <div class="main-menu-header">
            <h1>Selamat Datang, <?= htmlspecialchars($user['nama_lengkap']) ?> 👋</h1>
            <p>Pilih menu untuk memulai</p>
        </div>

        <div class="menu-grid" id="menu-grid">
            <!-- Dashboard Card -->
            <div class="menu-card card-dashboard" onclick="window.location.href='/kasir/index.php?page=dashboard'" id="card-dashboard">
                <div class="card-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="card-title">Dashboard</div>
                <div class="card-desc">Ringkasan penjualan & analitik hari ini</div>
            </div>

            <!-- POS Card -->
            <div class="menu-card card-pos" onclick="window.location.href='/kasir/index.php?page=pos'" id="card-pos">
                <div class="card-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="card-title">Kasir</div>
                <div class="card-desc">Buat transaksi penjualan baru</div>
            </div>

            <?php if ($user['role'] === 'admin'): ?>
            <!-- Products Card -->
            <div class="menu-card card-products" onclick="window.location.href='/kasir/index.php?page=products'" id="card-products">
                <div class="card-icon">
                    <i class="fas fa-mug-hot"></i>
                </div>
                <div class="card-title">Produk</div>
                <div class="card-desc">Kelola katalog menu kafe</div>
            </div>

            <!-- Reports Card -->
            <div class="menu-card card-reports" onclick="window.location.href='/kasir/index.php?page=reports'" id="card-reports">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">Laporan</div>
                <div class="card-desc">Analitik penjualan lengkap</div>
            </div>

            <!-- User Management Card -->
            <div class="menu-card card-users" onclick="window.location.href='/kasir/index.php?page=users'" id="card-users">
                <div class="card-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="card-title">User Management</div>
                <div class="card-desc">Kelola akun staf & hak akses</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script src="/kasir/assets/kendo/js/kendo.all.min.js"></script>
</body>
</html>
