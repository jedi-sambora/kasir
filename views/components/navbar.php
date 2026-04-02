<?php
/**
 * Navbar Component
 * Displays top navigation with user info and actions
 */
$user = getCurrentUser();
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'main_menu';
?>
<nav class="app-navbar" id="app-navbar">
    <div class="navbar-brand">
        <a href="/kasir/index.php?page=main_menu" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
            <div class="brand-icon">
                <i class="fas fa-mug-hot"></i>
            </div>
            <h2>Café POS</h2>
        </a>
    </div>
    <div class="navbar-actions">
        <?php if ($currentPage !== 'main_menu'): ?>
        <a href="/kasir/index.php?page=main_menu" class="nav-btn" id="btn-back-menu">
            <i class="fas fa-th-large"></i>
            <span>Menu Utama</span>
        </a>
        <?php endif; ?>
        <div class="navbar-user">
            <div class="user-avatar">
                <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                <div class="user-role"><?= ucfirst($user['role']) ?></div>
            </div>
        </div>
        <button class="nav-btn btn-danger" id="btn-logout" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </button>
    </div>
</nav>

<!-- Logout Confirmation Dialog -->
<div id="logout-dialog" style="display:none;">
    <div style="text-align:center;padding:20px 10px;">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,#FDE8E8,#FBBFBF);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <i class="fas fa-sign-out-alt" style="font-size:24px;color:#D94F4F;"></i>
        </div>
        <h3 style="font-family:var(--font-display,Georgia,serif);font-size:1.2rem;color:#2C1D12;margin-bottom:8px;">Konfirmasi Logout</h3>
        <p style="color:#6B5B4E;font-size:0.9rem;margin-bottom:24px;">Apakah Anda yakin ingin keluar<br>dari sistem?</p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button class="btn btn-outline" id="logout-cancel" style="min-width:120px;justify-content:center;">
                <i class="fas fa-times"></i> Batal
            </button>
            <button class="btn btn-danger" id="logout-confirm" style="min-width:120px;justify-content:center;background:linear-gradient(135deg,#D94F4F,#C43E3E);color:white;box-shadow:0 4px 12px rgba(217,79,79,0.25);">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </button>
        </div>
    </div>
</div>

<script>
var logoutWindow = null;

function logout() {
    if (!logoutWindow) {
        logoutWindow = $('#logout-dialog').kendoWindow({
            title: false,
            width: '380px',
            modal: true,
            visible: false,
            actions: [],
            draggable: false,
            resizable: false,
            animation: {
                open: { effects: "fadeIn", duration: 200 },
                close: { effects: "fadeOut", duration: 150 }
            }
        }).data('kendoWindow');

        // Bind button events
        $('#logout-cancel').on('click', function() {
            logoutWindow.close();
        });
        $('#logout-confirm').on('click', function() {
            window.location.href = '/kasir/api/auth.php?action=logout';
        });
    }
    logoutWindow.center().open();
}
</script>
