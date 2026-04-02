<?php
/**
 * POS (Kasir) Page - Main transaction interface
 */
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Halaman Kasir POS - Buat transaksi penjualan">
    <title>Kasir — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.common-material.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.material.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>
    <?php include __DIR__ . '/components/struk_print.php'; ?>

    <div class="page-content">
        <div class="pos-layout">
            <!-- Products Panel -->
            <div class="pos-products-panel" id="pos-products-panel">
                <div class="pos-search-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" class="search-input" id="pos-search" placeholder="Cari menu...">
                    </div>
                </div>
                <div class="category-filter-bar" id="category-filter">
                    <button class="cat-btn active" data-category="all">Semua</button>
                </div>
                <div class="product-grid" id="product-grid">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Cart Overlay (mobile) -->
            <div class="cart-overlay" id="cart-overlay" onclick="toggleMobileCart(false)"></div>

            <!-- Cart Panel -->
            <div class="pos-cart-panel" id="pos-cart-panel">
                <div class="cart-drag-handle" id="cart-drag-handle"><span></span></div>
                <div class="cart-header">
                    <h3><i class="fas fa-shopping-cart"></i>&nbsp; Pesanan</h3>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="cart-count" id="cart-count">0</span>
                        <button class="cart-close-btn" id="cart-close-btn" onclick="toggleMobileCart(false)" title="Tutup">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="cart-items" id="cart-items">
                    <div class="cart-empty" id="cart-empty">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Belum ada pesanan</p>
                    </div>
                </div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span class="total-label">Total</span>
                        <span class="total-amount" id="cart-total">Rp 0</span>
                    </div>
                    <button class="btn-pay" id="btn-pay" disabled onclick="openPayment()">
                        <i class="fas fa-credit-card"></i>
                        Bayar Sekarang
                    </button>
                </div>
            </div>

            <!-- Floating Cart Bar (mobile) -->
            <div class="mobile-cart-bar" id="mobile-cart-bar" onclick="toggleMobileCart(true)">
                <div class="mobile-cart-bar-left">
                    <div class="mobile-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="mobile-cart-badge" id="mobile-cart-badge">0</span>
                    </div>
                    <span class="mobile-cart-label">Lihat Pesanan</span>
                </div>
                <div class="mobile-cart-bar-total" id="mobile-cart-total">Rp 0</div>
            </div>
        </div>
    </div>

    <!-- Payment Window (Kendo Window) -->
    <div id="payment-window" style="display:none;">
        <div class="payment-content">
            <div class="payment-total-display">
                <div class="label">Total Pembayaran</div>
                <div class="amount" id="pay-total-display">Rp 0</div>
            </div>

            <div class="payment-form">
                <div class="form-group">
                    <label>Uang Diterima</label>
                    <input type="number" class="form-input" id="pay-amount" placeholder="0" 
                           oninput="calculateChange()" min="0">
                </div>

                <div class="quick-cash" id="quick-cash-btns">
                    <!-- filled by JS -->
                </div>

                <div class="change-display" id="change-display">
                    <div class="label">Kembalian</div>
                    <div class="amount" id="change-amount">Rp 0</div>
                </div>

                <div class="payment-actions">
                    <button class="btn btn-outline" onclick="closePayment()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button class="btn btn-success btn-lg" id="btn-checkout" onclick="processCheckout()" disabled>
                        <i class="fas fa-check-circle"></i> Proses Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Window -->
    <div id="receipt-window" style="display:none;">
        <div class="payment-content">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="width:72px;height:72px;background:linear-gradient(135deg,#2D9F6F,#238B5C);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i class="fas fa-check" style="font-size:32px;color:white;"></i>
                </div>
                <h2 style="font-family:var(--font-display);color:var(--text-primary);">Pembayaran Berhasil!</h2>
            </div>
            <div class="receipt-preview" id="receipt-preview-content">
                <!-- filled by JS -->
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button class="btn btn-outline" style="flex:1;justify-content:center;" onclick="printReceipt()">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
                <button class="btn btn-primary" style="flex:1;justify-content:center;" onclick="newTransaction()">
                    <i class="fas fa-plus"></i> Transaksi Baru
                </button>
            </div>
        </div>
    </div>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script src="/kasir/assets/kendo/js/kendo.all.min.js"></script>
    <script>
    // ============================================
    // POS Application Logic
    // ============================================
    var allProducts = [];
    var categories = [];
    var cart = [];
    var paymentWindow = null;
    var receiptWindow = null;
    var lastTransaction = null;

    // Format currency
    function formatRupiah(num) {
        return 'Rp ' + parseInt(num).toLocaleString('id-ID');
    }

    // Category icon map
    function getCategoryIcon(catName) {
        var map = {
            'Kopi': '☕', 'Non-Kopi': '🥤', 'Makanan Ringan': '🍟',
            'Makanan Berat': '🍛', 'Dessert': '🍰'
        };
        return map[catName] || '🍽️';
    }

    // Load products
    function loadProducts() {
        $.getJSON('/kasir/api/products.php', function(res) {
            if (res.success) {
                allProducts = res.data;
                categories = res.categories;
                renderCategoryFilter();
                renderProducts(allProducts);
            }
        });
    }

    // Render category filter buttons
    function renderCategoryFilter() {
        var html = '<button class="cat-btn active" data-category="all">Semua</button>';
        categories.forEach(function(cat) {
            html += '<button class="cat-btn" data-category="' + cat.id + '">' +
                    getCategoryIcon(cat.nama_kategori) + ' ' + cat.nama_kategori + '</button>';
        });
        $('#category-filter').html(html);

        // Bind click
        $('.cat-btn').on('click', function() {
            $('.cat-btn').removeClass('active');
            $(this).addClass('active');
            filterProducts();
        });
    }

    // Filter products by category and search
    function filterProducts() {
        var catId = $('.cat-btn.active').data('category');
        var search = $('#pos-search').val().toLowerCase();
        var filtered = allProducts.filter(function(p) {
            var matchCat = (catId === 'all' || p.category_id == catId);
            var matchSearch = p.nama_produk.toLowerCase().indexOf(search) !== -1;
            return matchCat && matchSearch;
        });
        renderProducts(filtered);
    }

    // Render product grid
    function renderProducts(products) {
        if (products.length === 0) {
            $('#product-grid').html('<div class="cart-empty" style="grid-column:1/-1;padding:40px;"><i class="fas fa-search"></i><p>Produk tidak ditemukan</p></div>');
            return;
        }
        var html = '';
        products.forEach(function(p) {
            var icon = getCategoryIcon(p.category_name || '');
            var imgHtml;
            if (p.image_url) {
                imgHtml = '<div class="product-img has-photo"><img src="' + p.image_url + '" alt="' + escapeHtml(p.nama_produk) + '" onerror="this.parentElement.innerHTML=\'' + icon + '\';this.parentElement.classList.remove(\'has-photo\');"></div>';
            } else {
                imgHtml = '<div class="product-img">' + icon + '</div>';
            }
            html += '<div class="product-card" onclick="addToCart(' + p.id + ')" data-id="' + p.id + '">' +
                    imgHtml +
                    '<div class="product-name">' + escapeHtml(p.nama_produk) + '</div>' +
                    '<div class="product-price">' + formatRupiah(p.harga) + '</div>' +
                    '</div>';
        });
        $('#product-grid').html(html);
    }

    // Add to cart
    function addToCart(productId) {
        var product = allProducts.find(function(p) { return p.id == productId; });
        if (!product) return;

        var existing = cart.find(function(c) { return c.id == productId; });
        if (existing) {
            existing.qty++;
        } else {
            cart.push({
                id: product.id,
                nama_produk: product.nama_produk,
                harga: parseFloat(product.harga),
                qty: 1,
                category_name: product.category_name
            });
        }
        renderCart();
        showToast(product.nama_produk + ' ditambahkan', 'success');
    }

    // Toggle mobile cart
    function toggleMobileCart(open) {
        var panel = $('#pos-cart-panel');
        var overlay = $('#cart-overlay');
        if (open) {
            panel.addClass('cart-open');
            overlay.addClass('active');
            document.body.style.overflow = 'hidden';
        } else {
            panel.removeClass('cart-open');
            overlay.removeClass('active');
            document.body.style.overflow = '';
        }
    }

    // Remove from cart
    function removeFromCart(productId) {
        cart = cart.filter(function(c) { return c.id != productId; });
        renderCart();
    }

    // Update qty
    function updateQty(productId, delta) {
        var item = cart.find(function(c) { return c.id == productId; });
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) {
            removeFromCart(productId);
            return;
        }
        renderCart();
    }

    // Get cart total
    function getCartTotal() {
        return cart.reduce(function(sum, item) { return sum + (item.harga * item.qty); }, 0);
    }

    // Render cart
    function renderCart() {
        var total = getCartTotal();
        var totalItems = cart.reduce(function(sum, item) { return sum + item.qty; }, 0);
        
        $('#cart-count').text(totalItems);
        $('#cart-total').text(formatRupiah(total));
        $('#btn-pay').prop('disabled', cart.length === 0);

        // Update mobile floating bar
        $('#mobile-cart-badge').text(totalItems);
        $('#mobile-cart-total').text(formatRupiah(total));
        if (totalItems > 0) {
            $('#mobile-cart-bar').addClass('has-items');
        } else {
            $('#mobile-cart-bar').removeClass('has-items');
        }

        if (cart.length === 0) {
            $('#cart-items').html('<div class="cart-empty" id="cart-empty"><i class="fas fa-shopping-basket"></i><p>Belum ada pesanan</p></div>');
            return;
        }

        var html = '';
        cart.forEach(function(item) {
            html += '<div class="cart-item">' +
                    '<div class="cart-item-info">' +
                    '<div class="item-name">' + escapeHtml(item.nama_produk) + '</div>' +
                    '<div class="item-price">' + formatRupiah(item.harga) + '</div>' +
                    '</div>' +
                    '<div class="cart-item-qty">' +
                    '<button class="qty-btn" onclick="updateQty(' + item.id + ',-1)"><i class="fas fa-minus"></i></button>' +
                    '<span class="qty-value">' + item.qty + '</span>' +
                    '<button class="qty-btn" onclick="updateQty(' + item.id + ',1)"><i class="fas fa-plus"></i></button>' +
                    '</div>' +
                    '<div class="cart-item-subtotal">' + formatRupiah(item.harga * item.qty) + '</div>' +
                    '<button class="cart-item-remove" onclick="removeFromCart(' + item.id + ')"><i class="fas fa-trash-alt"></i></button>' +
                    '</div>';
        });
        $('#cart-items').html(html);
    }

    // Open payment window
    function openPayment() {
        if (cart.length === 0) return;
        var total = getCartTotal();
        $('#pay-total-display').text(formatRupiah(total));
        $('#pay-amount').val('').focus();
        $('#change-amount').text('Rp 0');
        $('#change-display').removeClass('insufficient');
        $('#btn-checkout').prop('disabled', true);

        // Generate quick cash buttons
        var quickAmounts = generateQuickCash(total);
        var qhtml = '';
        quickAmounts.forEach(function(amt) {
            qhtml += '<button class="quick-cash-btn" onclick="setPayAmount(' + amt + ')">' + formatRupiah(amt) + '</button>';
        });
        // Add exact amount button
        qhtml = '<button class="quick-cash-btn" onclick="setPayAmount(' + total + ')" style="background:var(--primary-50);border-color:var(--primary-200);">Uang Pas</button>' + qhtml;
        $('#quick-cash-btns').html(qhtml);

        if (!paymentWindow) {
            paymentWindow = $('#payment-window').kendoWindow({
                title: 'Pembayaran',
                width: '480px',
                modal: true,
                visible: false,
                actions: ['Close'],
                close: function() { },
                animation: { open: { effects: "fadeIn" }, close: { effects: "fadeOut" } }
            }).data('kendoWindow');
        }
        paymentWindow.center().open();
    }

    function closePayment() {
        if (paymentWindow) paymentWindow.close();
    }

    function generateQuickCash(total) {
        var amounts = [];
        var rounded = Math.ceil(total / 10000) * 10000;
        if (rounded > total) amounts.push(rounded);
        amounts.push(Math.ceil(total / 50000) * 50000);
        amounts.push(100000);
        if (total > 50000) amounts.push(200000);
        if (total > 100000) amounts.push(500000);
        // Remove duplicates and sort
        amounts = [...new Set(amounts)].filter(function(a) { return a >= total; }).sort(function(a,b) { return a-b; }).slice(0, 5);
        return amounts;
    }

    function setPayAmount(amount) {
        $('#pay-amount').val(amount);
        calculateChange();
    }

    function calculateChange() {
        var total = getCartTotal();
        var paid = parseFloat($('#pay-amount').val()) || 0;
        var change = paid - total;
        
        if (paid === 0) {
            $('#change-display').removeClass('insufficient');
            $('#change-amount').text('Rp 0');
            $('#btn-checkout').prop('disabled', true);
        } else if (change < 0) {
            $('#change-display').addClass('insufficient');
            $('#change-amount').text('Kurang ' + formatRupiah(Math.abs(change)));
            $('#btn-checkout').prop('disabled', true);
        } else {
            $('#change-display').removeClass('insufficient');
            $('#change-amount').text(formatRupiah(change));
            $('#btn-checkout').prop('disabled', false);
        }
    }

    // Process checkout
    function processCheckout() {
        var total = getCartTotal();
        var paid = parseFloat($('#pay-amount').val()) || 0;
        if (paid < total) return;

        $('#btn-checkout').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        var items = cart.map(function(item) {
            return {
                product_id: item.id,
                qty: item.qty,
                harga_satuan: item.harga
            };
        });

        $.ajax({
            url: '/kasir/api/checkout.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                items: items,
                total_harga: total,
                uang_bayar: paid,
                kembalian: paid - total
            }),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    closePayment();
                    lastTransaction = {
                        invoice_no: res.invoice_no,
                        items: cart.slice(),
                        total: total,
                        paid: paid,
                        change: paid - total,
                        date: new Date().toLocaleString('id-ID'),
                        kasir: '<?= htmlspecialchars($user['nama_lengkap']) ?>'
                    };
                    showReceiptWindow();
                } else {
                    showToast(res.message || 'Gagal memproses pembayaran', 'error');
                    $('#btn-checkout').prop('disabled', false).html('<i class="fas fa-check-circle"></i> Proses Pembayaran');
                }
            },
            error: function() {
                showToast('Terjadi kesalahan koneksi', 'error');
                $('#btn-checkout').prop('disabled', false).html('<i class="fas fa-check-circle"></i> Proses Pembayaran');
            }
        });
    }

    // Show receipt window
    function showReceiptWindow() {
        var t = lastTransaction;
        var receiptHtml = '<div class="receipt-header">' +
            '<h2>☕ CAFÉ POS</h2>' +
            '<p style="font-size:11px;color:#999;">Jl. Kopi Nusantara No.1</p>' +
            '</div>' +
            '<div class="receipt-info" style="margin:10px 0;padding-bottom:10px;border-bottom:1px dashed #ccc;font-size:11px;">' +
            '<div>No: ' + t.invoice_no + '</div>' +
            '<div>Kasir: ' + t.kasir + '</div>' +
            '<div>Tgl: ' + t.date + '</div>' +
            '</div>' +
            '<div class="receipt-items">';
        
        t.items.forEach(function(item) {
            receiptHtml += '<div class="receipt-item">' +
                '<span>' + item.qty + 'x ' + escapeHtml(item.nama_produk) + '</span>' +
                '<span>' + formatRupiah(item.harga * item.qty) + '</span>' +
                '</div>';
        });

        receiptHtml += '</div>' +
            '<div class="receipt-total-row"><span>Total</span><span>' + formatRupiah(t.total) + '</span></div>' +
            '<div class="receipt-total-row"><span>Bayar</span><span>' + formatRupiah(t.paid) + '</span></div>' +
            '<div class="receipt-total-row grand"><span>Kembali</span><span>' + formatRupiah(t.change) + '</span></div>' +
            '<div class="receipt-footer"><p>Terima kasih!</p></div>';

        $('#receipt-preview-content').html(receiptHtml);

        // Also fill print template
        $('#rp-invoice').text(t.invoice_no);
        $('#rp-kasir').text(t.kasir);
        $('#rp-date').text(t.date);
        var rpItems = '';
        t.items.forEach(function(item) {
            rpItems += '<div class="receipt-item"><span>' + item.qty + 'x ' + item.nama_produk + '</span><span>' + formatRupiah(item.harga * item.qty) + '</span></div>';
        });
        $('#rp-items').html(rpItems);
        $('#rp-total').text(formatRupiah(t.total));
        $('#rp-bayar').text(formatRupiah(t.paid));
        $('#rp-kembali').text(formatRupiah(t.change));

        if (!receiptWindow) {
            receiptWindow = $('#receipt-window').kendoWindow({
                title: 'Struk Pembayaran',
                width: '480px',
                modal: true,
                visible: false,
                actions: ['Close'],
                animation: { open: { effects: "fadeIn" }, close: { effects: "fadeOut" } }
            }).data('kendoWindow');
        }
        receiptWindow.center().open();
    }

    function printReceipt() {
        var printContent = document.getElementById('receipt-print-area');
        printContent.style.display = 'block';
        window.print();
        setTimeout(function() { printContent.style.display = 'none'; }, 1000);
    }

    function newTransaction() {
        cart = [];
        renderCart();
        if (receiptWindow) receiptWindow.close();
        lastTransaction = null;
        showToast('Siap untuk transaksi baru!', 'info');
    }

    // Toast notification
    function showToast(message, type) {
        var toast = $('<div class="toast-notification ' + (type || 'info') + '">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }

    // Escape HTML
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Init
    $(document).ready(function() {
        loadProducts();
        
        // Search debounce
        var searchTimer;
        $('#pos-search').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(filterProducts, 200);
        });
    });
    </script>
</body>
</html>
