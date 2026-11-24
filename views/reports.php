<?php
/**
 * Reports Page - Advanced analytics & transaction history
 * Admin only - includes per-item detail and Excel export
 */
requireLogin();
requireAdmin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Laporan Penjualan - Analitik lengkap">
    <title>Laporan — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.common-material.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.material.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="page-content">
        <div class="page-title-bar">
            <h1><i class="fas fa-chart-line" style="color:var(--warning);"></i>&nbsp; Laporan Penjualan</h1>
        </div>

        <!-- Filters -->
        <div class="report-filters">
            <div class="filter-group">
                <label>Dari Tanggal</label>
                <input type="date" id="filter-from" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="filter-group">
                <label>Sampai Tanggal</label>
                <input type="date" id="filter-to" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn btn-primary" onclick="loadReport()">
                <i class="fas fa-filter"></i> Terapkan
            </button>
            <button class="btn btn-success" onclick="exportTransactionsExcel()" id="btn-export-tx" style="margin-left:auto;">
                <i class="fas fa-file-excel"></i> Export Transaksi (.xlsx)
            </button>
            <button class="btn btn-success" onclick="exportItemsExcel()" id="btn-export-items">
                <i class="fas fa-file-excel"></i> Export Detail Item (.xlsx)
            </button>
        </div>

        <!-- Summary -->
        <div class="dashboard-summary" style="margin-bottom:24px;">
            <div class="summary-card card-revenue">
                <div class="summary-icon"><i class="fas fa-wallet"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="rpt-revenue">Rp 0</div>
                    <div class="summary-label">Total Pendapatan</div>
                </div>
            </div>
            <div class="summary-card card-transactions">
                <div class="summary-icon"><i class="fas fa-receipt"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="rpt-tx">0</div>
                    <div class="summary-label">Total Transaksi</div>
                </div>
            </div>
            <div class="summary-card card-products-count">
                <div class="summary-icon"><i class="fas fa-boxes"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="rpt-items">0</div>
                    <div class="summary-label">Total Item Terjual</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-grid" style="margin-bottom:24px;">
            <div class="chart-card">
                <h3><i class="fas fa-chart-area" style="color:var(--primary);"></i>&nbsp; Tren Pendapatan</h3>
                <div id="rpt-chart-daily" style="height:300px;"></div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-trophy" style="color:var(--success);"></i>&nbsp; Produk Terlaris</h3>
                <div id="rpt-chart-top" style="height:300px;"></div>
            </div>
        </div>

        <!-- Tab Switcher -->
        <div style="display:flex;gap:0;margin-bottom:0;">
            <button class="tab-btn active" id="tab-transactions" onclick="switchTab('transactions')">
                <i class="fas fa-receipt"></i>&nbsp; Riwayat Transaksi
            </button>
            <button class="tab-btn" id="tab-items" onclick="switchTab('items')">
                <i class="fas fa-list-ul"></i>&nbsp; Detail Per Item
            </button>
        </div>

        <!-- Transaction History Grid -->
        <div class="transaction-table" id="panel-transactions" style="border-radius:0 var(--radius-md) var(--radius-md) var(--radius-md);">
            <div id="transaction-grid"></div>
        </div>

        <!-- Item Detail Grid -->
        <div class="transaction-table" id="panel-items" style="display:none;border-radius:0 var(--radius-md) var(--radius-md) var(--radius-md);">
            <div id="item-detail-grid"></div>
        </div>
    </div>

    <!-- Transaction Detail Window -->
    <div id="tx-detail-window" style="display:none;">
        <div class="payment-content">
            <div class="receipt-preview" id="tx-detail-content"></div>
        </div>
    </div>

    <style>
    .tab-btn {
        padding: 12px 24px;
        border: 1px solid var(--border);
        border-bottom: none;
        background: var(--bg-hover);
        color: var(--text-secondary);
        font-size: 0.88rem;
        font-weight: 600;
        font-family: var(--font-main);
        cursor: pointer;
        transition: all var(--transition-fast);
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        position: relative;
        bottom: -1px;
    }
    .tab-btn.active {
        background: var(--bg-card);
        color: var(--primary);
        border-color: var(--border-light);
        border-bottom: 2px solid var(--bg-card);
        z-index: 1;
    }
    .tab-btn:hover:not(.active) {
        background: white;
        color: var(--primary);
    }
    </style>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script src="/kasir/assets/js/jszip.min.js"></script>
    <script src="/kasir/assets/kendo/js/kendo.all.min.js"></script>
    <script>
    var txDetailWindow = null;
    var cachedTransactions = [];
    var cachedItemDetails = [];

    function formatRupiah(num) {
        return 'Rp ' + parseInt(num).toLocaleString('id-ID');
    }

    function getFilters() {
        return {
            date_from: $('#filter-from').val(),
            date_to: $('#filter-to').val()
        };
    }

    function showToast(message, type) {
        var toast = $('<div class="toast-notification ' + (type || 'info') + '">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }

    // ============================================
    // Tab Switcher
    // ============================================
    function loadReport() {
        loadTransactions();
        loadItemDetails();
        loadDailyChart();
        loadTopChart();
    }

    // ============================================
    // Tab Switcher
    // ============================================
    function switchTab(tab) {
        $('.tab-btn').removeClass('active');
        $('#tab-' + tab).addClass('active');
        if (tab === 'transactions') {
            $('#panel-transactions').show();
            $('#panel-items').hide();
        } else {
            $('#panel-transactions').hide();
            $('#panel-items').show();
        }
    }

    // ============================================
    // Transaction History (per-transaksi)
    // ============================================
    function loadTransactions() {
        var f = getFilters();
        $.getJSON('/kasir/api/reports.php?action=transactions&date_from=' + f.date_from + '&date_to=' + f.date_to, function(res) {
            if (!res.success) return;

            // Update summary
            $('#rpt-revenue').text(formatRupiah(res.summary.total_revenue));
            $('#rpt-tx').text(res.summary.total_transactions);

            // Parse numbers (MySQL returns decimals as strings)
            cachedTransactions = res.data.map(function(d) {
                d.id = parseInt(d.id);
                d.total_harga = parseFloat(d.total_harga) || 0;
                d.uang_bayar = parseFloat(d.uang_bayar) || 0;
                d.kembalian = parseFloat(d.kembalian) || 0;
                return d;
            });

            var $grid = $('#transaction-grid');
            if ($grid.data('kendoGrid')) {
                $grid.data('kendoGrid').destroy();
                $grid.empty();
            }

            $grid.kendoGrid({
                dataSource: {
                    data: cachedTransactions,
                    pageSize: 20,
                    schema: {
                        model: {
                            fields: {
                                id: { type: "number" },
                                invoice_no: { type: "string" },
                                kasir_name: { type: "string" },
                                total_harga: { type: "number" },
                                uang_bayar: { type: "number" },
                                kembalian: { type: "number" },
                                created_at: { type: "string" }
                            }
                        }
                    }
                },
                sortable: true,
                filterable: true,
                pageable: { pageSize: 20, buttonCount: 5 },
                columns: [
                    { field: "invoice_no", title: "No. Invoice", width: 200 },
                    { field: "kasir_name", title: "Kasir", width: 150 },
                    { field: "total_harga", title: "Total", width: 150, template: "#= 'Rp ' + parseInt(total_harga).toLocaleString('id-ID') #" },
                    { field: "uang_bayar", title: "Bayar", width: 150, template: "#= 'Rp ' + parseInt(uang_bayar).toLocaleString('id-ID') #" },
                    { field: "kembalian", title: "Kembali", width: 130, template: "#= 'Rp ' + parseInt(kembalian).toLocaleString('id-ID') #" },
                    { field: "created_at", title: "Waktu", width: 180,
                        template: function(d) {
                            return new Date(d.created_at).toLocaleString('id-ID', {day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
                        }
                    },
                    { title: "Detail", width: 100,
                        template: function(d) {
                            return '<button class="grid-action-btn" onclick="viewDetail(' + d.id + ')"><i class="fas fa-eye"></i></button>';
                        }
                    }
                ]
            });
        });
    }

    // ============================================
    // Item Details (per-item)
    // ============================================
    function loadItemDetails() {
        var f = getFilters();
        $.getJSON('/kasir/api/reports.php?action=item_details&date_from=' + f.date_from + '&date_to=' + f.date_to, function(res) {
            if (!res.success) return;

            $('#rpt-items').text(res.summary.total_items);
            // Parse numbers (MySQL returns decimals as strings)
            cachedItemDetails = res.data.map(function(d) {
                d.qty = parseInt(d.qty) || 0;
                d.harga_satuan = parseFloat(d.harga_satuan) || 0;
                d.subtotal = parseFloat(d.subtotal) || 0;
                return d;
            });

            var $grid = $('#item-detail-grid');
            if ($grid.data('kendoGrid')) {
                $grid.data('kendoGrid').destroy();
                $grid.empty();
            }

            $grid.kendoGrid({
                dataSource: {
                    data: cachedItemDetails,
                    pageSize: 25,
                    schema: {
                        model: {
                            fields: {
                                invoice_no: { type: "string" },
                                created_at: { type: "string" },
                                kasir_name: { type: "string" },
                                kategori: { type: "string" },
                                nama_produk: { type: "string" },
                                qty: { type: "number" },
                                harga_satuan: { type: "number" },
                                subtotal: { type: "number" }
                            }
                        }
                    }
                },
                sortable: true,
                filterable: true,
                groupable: true,
                pageable: { pageSize: 25, buttonCount: 5 },
                columns: [
                    { field: "invoice_no", title: "No. Invoice", width: 190, groupHeaderTemplate: "#= value # (#= count # item)" },
                    { field: "created_at", title: "Waktu", width: 170,
                        template: function(d) {
                            return new Date(d.created_at).toLocaleString('id-ID', {day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
                        }
                    },
                    { field: "kasir_name", title: "Kasir", width: 140 },
                    { field: "kategori", title: "Kategori", width: 130 },
                    { field: "nama_produk", title: "Nama Produk" },
                    { field: "qty", title: "Qty", width: 80, attributes: { style: "text-align:center;" } },
                    { field: "harga_satuan", title: "Harga Satuan", width: 150,
                        template: "#= 'Rp ' + parseInt(harga_satuan).toLocaleString('id-ID') #"
                    },
                    { field: "subtotal", title: "Subtotal", width: 150,
                        template: "#= 'Rp ' + parseInt(subtotal).toLocaleString('id-ID') #",
                        footerTemplate: function(d) { return '<b>Grand Total</b>'; }
                    }
                ]
            });
        });
    }

    // ============================================
    // Charts
    // ============================================
    function loadDailyChart() {
        var f = getFilters();
        $.getJSON('/kasir/api/reports.php?action=daily_sales&date_from=' + f.date_from + '&date_to=' + f.date_to, function(res) {
            if (!res.success) return;
            var data = res.data.map(function(d) {
                return {
                    tanggal: new Date(d.tanggal).toLocaleDateString('id-ID', {day:'numeric', month:'short'}),
                    total: parseFloat(d.total),
                    jumlah: parseInt(d.jumlah_transaksi)
                };
            });
            if (data.length === 0) data = [{ tanggal: 'Belum ada data', total: 0, jumlah: 0 }];

            var $chart = $('#rpt-chart-daily');
            if ($chart.data('kendoChart')) $chart.data('kendoChart').destroy();

            $chart.kendoChart({
                dataSource: { data: data },
                legend: { position: "bottom" },
                seriesDefaults: { type: "column" },
                series: [{
                    field: "total",
                    name: "Pendapatan (Rp)",
                    color: "#16A34A",
                    overlay: { gradient: "none" },
                    border: { width: 0 }
                }],
                categoryAxis: {
                    field: "tanggal",
                    labels: { font: "12px Inter" },
                    majorGridLines: { visible: false }
                },
                valueAxis: {
                    labels: { font: "11px Inter", template: function(e) { return formatRupiah(e.value); } },
                    majorGridLines: { color: "#E8F2EB" }
                },
                tooltip: {
                    visible: true,
                    template: "#= category #: Rp #= kendo.toString(value, 'n0') # (#= dataItem.jumlah # transaksi)"
                },
                chartArea: { background: "transparent" }
            });
        });
    }

    function loadTopChart() {
        var f = getFilters();
        $.getJSON('/kasir/api/reports.php?action=top_products&limit=5&date_from=' + f.date_from + '&date_to=' + f.date_to, function(res) {
            if (!res.success) return;
            var data = res.data.map(function(d) {
                return { produk: d.nama_produk, qty: parseInt(d.total_qty) };
            });
            if (data.length === 0) data = [{ produk: '-', qty: 0 }];

            var $chart = $('#rpt-chart-top');
            if ($chart.data('kendoChart')) $chart.data('kendoChart').destroy();

            $chart.kendoChart({
                dataSource: { data: data },
                legend: { visible: false },
                series: [{
                    type: "bar",
                    field: "qty",
                    color: "#2D9F6F",
                    overlay: { gradient: "none" },
                    border: { width: 0 },
                    gap: 0.5
                }],
                categoryAxis: {
                    field: "produk",
                    labels: { font: "11px Inter" },
                    majorGridLines: { visible: false }
                },
                valueAxis: { labels: { font: "11px Inter" }, majorGridLines: { color: "#E8F2EB" } },
                tooltip: { visible: true, template: "#= category #: #= value # terjual" },
                chartArea: { background: "transparent" }
            });
        });
    }

    // ============================================
    // View Transaction Detail (popup)
    // ============================================
    function viewDetail(txId) {
        $.getJSON('/kasir/api/reports.php?action=transaction_detail&id=' + txId, function(res) {
            if (!res.success) return;
            var html = '<div class="receipt-header"><h2>Detail Transaksi</h2></div>';
            html += '<div class="receipt-items">';
            var total = 0;
            res.data.forEach(function(item) {
                html += '<div class="receipt-item"><span>' + item.qty + 'x ' + item.nama_produk + '</span><span>' + formatRupiah(item.subtotal) + '</span></div>';
                total += parseFloat(item.subtotal);
            });
            html += '</div>';
            html += '<div class="receipt-total-row grand"><span>Total</span><span>' + formatRupiah(total) + '</span></div>';
            
            $('#tx-detail-content').html(html);
            
            if (!txDetailWindow) {
                txDetailWindow = $('#tx-detail-window').kendoWindow({
                    title: 'Detail Transaksi',
                    width: '400px',
                    modal: true,
                    visible: false,
                    actions: ['Close'],
                    animation: { open: { effects: "fadeIn" }, close: { effects: "fadeOut" } }
                }).data('kendoWindow');
            }
            txDetailWindow.center().open();
        });
    }

    // ============================================
    // EXCEL EXPORT
    // ============================================
    function exportTransactionsExcel() {
        if (cachedTransactions.length === 0) {
            showToast('Tidak ada data transaksi untuk diekspor', 'error');
            return;
        }

        var f = getFilters();
        var workbook = new kendo.ooxml.Workbook({
            sheets: [{
                title: "Riwayat Transaksi",
                columns: [
                    { autoWidth: true },
                    { autoWidth: true },
                    { autoWidth: true },
                    { autoWidth: true },
                    { autoWidth: true },
                    { autoWidth: true }
                ],
                rows: buildTransactionRows(cachedTransactions, f)
            }]
        });

        workbook.toDataURLAsync().then(function(dataURL) {
            kendo.saveAs({
                dataURI: dataURL,
                fileName: "Laporan_Transaksi_" + f.date_from + "_sd_" + f.date_to + ".xlsx"
            });
            showToast('File Excel transaksi berhasil diunduh!', 'success');
        });
    }

    function exportItemsExcel() {
        var f = getFilters();
        
        // Fetch fresh data for export
        $.getJSON('/kasir/api/reports.php?action=item_details&date_from=' + f.date_from + '&date_to=' + f.date_to, function(res) {
            if (!res.success || res.data.length === 0) {
                showToast('Tidak ada data item untuk diekspor', 'error');
                return;
            }

            var workbook = new kendo.ooxml.Workbook({
                sheets: [{
                    title: "Detail Per Item",
                    columns: [
                        { autoWidth: true },
                        { autoWidth: true },
                        { autoWidth: true },
                        { autoWidth: true },
                        { autoWidth: true },
                        { autoWidth: true },
                        { autoWidth: true },
                        { autoWidth: true }
                    ],
                    rows: buildItemRows(res.data, res.summary, f)
                }]
            });

            workbook.toDataURLAsync().then(function(dataURL) {
                kendo.saveAs({
                    dataURI: dataURL,
                    fileName: "Laporan_Detail_Item_" + f.date_from + "_sd_" + f.date_to + ".xlsx"
                });
                showToast('File Excel detail item berhasil diunduh!', 'success');
            });
        });
    }

    // Build Excel rows for transactions
    function buildTransactionRows(data, f) {
        var headerStyle = { bold: true, background: "#16A34A", color: "#FFFFFF", fontSize: 12 };
        var titleStyle = { bold: true, fontSize: 14, color: "#132A1B" };
        var totalStyle = { bold: true, background: "#DCFCE7", fontSize: 12 };

        var rows = [];

        // Title
        rows.push({ cells: [{ value: "LAPORAN TRANSAKSI - CAFÉ POS", colSpan: 6, bold: true, fontSize: 16, color: "#16A34A" }] });
        rows.push({ cells: [{ value: "Periode: " + f.date_from + " s/d " + f.date_to, colSpan: 6, fontSize: 11, color: "#476252" }] });
        rows.push({ cells: [{ value: "" }] }); // empty row

        // Headers
        rows.push({
            cells: [
                { value: "No. Invoice", ...headerStyle },
                { value: "Kasir", ...headerStyle },
                { value: "Total (Rp)", ...headerStyle },
                { value: "Bayar (Rp)", ...headerStyle },
                { value: "Kembali (Rp)", ...headerStyle },
                { value: "Waktu", ...headerStyle }
            ]
        });

        // Data rows
        var grandTotal = 0;
        data.forEach(function(d) {
            grandTotal += parseFloat(d.total_harga);
            rows.push({
                cells: [
                    { value: d.invoice_no },
                    { value: d.kasir_name },
                    { value: parseFloat(d.total_harga), format: "#,##0" },
                    { value: parseFloat(d.uang_bayar), format: "#,##0" },
                    { value: parseFloat(d.kembalian), format: "#,##0" },
                    { value: new Date(d.created_at).toLocaleString('id-ID') }
                ]
            });
        });

        // Total row
        rows.push({ cells: [{ value: "" }] });
        rows.push({
            cells: [
                { value: "GRAND TOTAL", colSpan: 2, ...totalStyle },
                { value: "", ...totalStyle },
                { value: grandTotal, format: "#,##0", ...totalStyle },
                { value: "", ...totalStyle },
                { value: "", ...totalStyle },
                { value: data.length + " transaksi", ...totalStyle }
            ]
        });

        return rows;
    }

    // Build Excel rows for item details
    function buildItemRows(data, summary, f) {
        var headerStyle = { bold: true, background: "#2D9F6F", color: "#FFFFFF", fontSize: 12 };
        var totalStyle = { bold: true, background: "#E6F7EF", fontSize: 12 };

        var rows = [];

        // Title
        rows.push({ cells: [{ value: "LAPORAN DETAIL PER ITEM - CAFÉ POS", colSpan: 8, bold: true, fontSize: 16, color: "#2D9F6F" }] });
        rows.push({ cells: [{ value: "Periode: " + f.date_from + " s/d " + f.date_to, colSpan: 8, fontSize: 11, color: "#476252" }] });
        rows.push({ cells: [{ value: "" }] });

        // Headers
        rows.push({
            cells: [
                { value: "No. Invoice", ...headerStyle },
                { value: "Waktu", ...headerStyle },
                { value: "Kasir", ...headerStyle },
                { value: "Kategori", ...headerStyle },
                { value: "Nama Produk", ...headerStyle },
                { value: "Qty", ...headerStyle },
                { value: "Harga Satuan (Rp)", ...headerStyle },
                { value: "Subtotal (Rp)", ...headerStyle }
            ]
        });

        // Data rows
        data.forEach(function(d) {
            rows.push({
                cells: [
                    { value: d.invoice_no },
                    { value: new Date(d.created_at).toLocaleString('id-ID') },
                    { value: d.kasir_name },
                    { value: d.kategori },
                    { value: d.nama_produk },
                    { value: parseInt(d.qty) },
                    { value: parseFloat(d.harga_satuan), format: "#,##0" },
                    { value: parseFloat(d.subtotal), format: "#,##0" }
                ]
            });
        });

        // Totals
        rows.push({ cells: [{ value: "" }] });
        rows.push({
            cells: [
                { value: "GRAND TOTAL", colSpan: 5, ...totalStyle },
                { value: "", ...totalStyle },
                { value: "", ...totalStyle },
                { value: "", ...totalStyle },
                { value: "", ...totalStyle },
                { value: parseInt(summary.total_items), ...totalStyle },
                { value: "", ...totalStyle },
                { value: parseFloat(summary.total_revenue), format: "#,##0", ...totalStyle }
            ]
        });
        rows.push({
            cells: [
                { value: summary.total_transactions + " transaksi, " + summary.total_items + " item terjual", colSpan: 8, italic: true, color: "#476252" }
            ]
        });

        return rows;
    }

    // ============================================
    // Init
    // ============================================
    $(document).ready(function() {
        loadReport();
    });
    </script>
</body>
</html>
