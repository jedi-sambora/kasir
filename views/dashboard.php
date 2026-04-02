<?php
/**
 * Dashboard Page - Analytics overview
 */
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard - Ringkasan penjualan dan analitik">
    <title>Dashboard — Café POS</title>
    <link rel="stylesheet" href="/kasir/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.common-material.min.css">
    <link rel="stylesheet" href="/kasir/assets/kendo/styles/kendo.material.min.css">
    <link rel="stylesheet" href="/kasir/assets/css/app.css">
</head>
<body>
    <?php include __DIR__ . '/components/navbar.php'; ?>

    <div class="page-content">
        <div class="page-title-bar">
            <h1><i class="fas fa-chart-pie" style="color:#4A90D9;"></i>&nbsp; Dashboard</h1>
            <div style="font-size:0.9rem;color:var(--text-muted);">
                <i class="fas fa-calendar-alt"></i>&nbsp; <?= date('l, d F Y') ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="dashboard-summary" id="summary-cards">
            <div class="summary-card card-revenue">
                <div class="summary-icon"><i class="fas fa-wallet"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="sum-revenue">Rp 0</div>
                    <div class="summary-label">Pendapatan Hari Ini</div>
                </div>
            </div>
            <div class="summary-card card-transactions">
                <div class="summary-icon"><i class="fas fa-receipt"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="sum-tx">0</div>
                    <div class="summary-label">Transaksi Hari Ini</div>
                </div>
            </div>
            <div class="summary-card card-products-count">
                <div class="summary-icon"><i class="fas fa-mug-hot"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="sum-products">0</div>
                    <div class="summary-label">Produk Aktif</div>
                </div>
            </div>
            <div class="summary-card card-avg">
                <div class="summary-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="summary-info">
                    <div class="summary-value" id="sum-avg">Rp 0</div>
                    <div class="summary-label">Rata-Rata Transaksi</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line" style="color:var(--primary);"></i>&nbsp; Penjualan 7 Hari Terakhir</h3>
                <div id="chart-daily-sales" style="height:300px;"></div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie" style="color:var(--warning);"></i>&nbsp; Penjualan per Kategori</h3>
                <div id="chart-category" style="height:300px;"></div>
            </div>
        </div>

        <div class="chart-card">
            <h3><i class="fas fa-trophy" style="color:var(--success);"></i>&nbsp; Produk Terlaris Bulan Ini</h3>
            <div id="chart-top-products" style="height:320px;"></div>
        </div>
    </div>

    <script src="/kasir/assets/js/jquery.min.js"></script>
    <script src="/kasir/assets/kendo/js/kendo.all.min.js"></script>
    <script>
    function formatRupiah(num) {
        return 'Rp ' + parseInt(num).toLocaleString('id-ID');
    }

    // Load dashboard summary
    function loadSummary() {
        $.getJSON('/kasir/api/reports.php?action=dashboard', function(res) {
            if (res.success) {
                var s = res.summary;
                $('#sum-revenue').text(formatRupiah(s.revenue_today));
                $('#sum-tx').text(s.transactions_today);
                $('#sum-products').text(s.total_products);
                $('#sum-avg').text(formatRupiah(s.avg_transaction));
            }
        });
    }

    // Daily sales chart
    function loadDailySalesChart() {
        var today = new Date().toISOString().split('T')[0];
        var weekAgo = new Date(Date.now() - 7*24*60*60*1000).toISOString().split('T')[0];
        $.getJSON('/kasir/api/reports.php?action=daily_sales&date_from=' + weekAgo + '&date_to=' + today, function(res) {
            if (!res.success) return;
            
            var data = res.data.map(function(d) {
                return {
                    tanggal: new Date(d.tanggal).toLocaleDateString('id-ID', {day:'numeric', month:'short'}),
                    total: parseFloat(d.total),
                    jumlah: parseInt(d.jumlah_transaksi)
                };
            });

            if (data.length === 0) {
                data = [{ tanggal: 'Belum ada data', total: 0, jumlah: 0 }];
            }

            $('#chart-daily-sales').kendoChart({
                dataSource: { data: data },
                legend: { position: "bottom" },
                seriesDefaults: { type: "column" },
                series: [{
                    field: "total",
                    name: "Pendapatan (Rp)",
                    color: "#8B5E3C",
                    overlay: { gradient: "none" },
                    border: { width: 0 }
                }],
                categoryAxis: {
                    field: "tanggal",
                    labels: { font: "12px Inter" },
                    majorGridLines: { visible: false }
                },
                valueAxis: {
                    labels: {
                        font: "11px Inter",
                        template: function(e) { return formatRupiah(e.value); }
                    },
                    majorGridLines: { color: "#f0e8e0" }
                },
                tooltip: {
                    visible: true,
                    template: "#= category #: #= kendo.toString(value, 'n0') # (#= dataItem.jumlah # transaksi)"
                },
                chartArea: { background: "transparent" }
            });
        });
    }

    // Category pie chart
    function loadCategoryChart() {
        $.getJSON('/kasir/api/reports.php?action=category_revenue', function(res) {
            if (!res.success) return;

            var colors = ['#8B5E3C', '#D4A574', '#4A90D9', '#2D9F6F', '#E8A838', '#9B6FB0'];

            var data = res.data.map(function(d, i) {
                return {
                    category: d.nama_kategori,
                    value: parseFloat(d.total_revenue),
                    color: colors[i % colors.length]
                };
            });

            if (data.length === 0) {
                data = [{ category: 'Belum ada data', value: 1, color: '#ddd' }];
            }

            $('#chart-category').kendoChart({
                legend: { position: "bottom", labels: { font: "12px Inter" } },
                seriesDefaults: { type: "donut" },
                series: [{
                    data: data,
                    field: "value",
                    categoryField: "category",
                    colorField: "color",
                    padding: 0,
                    startAngle: 90,
                    labels: {
                        visible: true,
                        position: "outsideEnd",
                        template: "#= category #",
                        font: "11px Inter"
                    }
                }],
                tooltip: {
                    visible: true,
                    template: "#= category #: Rp #= kendo.toString(value, 'n0') #"
                },
                chartArea: { background: "transparent" }
            });
        });
    }

    // Top products chart
    function loadTopProductsChart() {
        $.getJSON('/kasir/api/reports.php?action=top_products&limit=8', function(res) {
            if (!res.success) return;

            var data = res.data.map(function(d) {
                return {
                    produk: d.nama_produk,
                    qty: parseInt(d.total_qty),
                    revenue: parseFloat(d.total_revenue)
                };
            });

            if (data.length === 0) {
                data = [{ produk: 'Belum ada data', qty: 0, revenue: 0 }];
            }

            $('#chart-top-products').kendoChart({
                dataSource: { data: data },
                legend: { position: "bottom" },
                seriesDefaults: { type: "bar" },
                series: [{
                    field: "qty",
                    name: "Jumlah Terjual",
                    color: "#2D9F6F",
                    overlay: { gradient: "none" },
                    border: { width: 0 },
                    gap: 0.5
                }],
                categoryAxis: {
                    field: "produk",
                    labels: { font: "12px Inter" },
                    majorGridLines: { visible: false }
                },
                valueAxis: {
                    labels: { font: "11px Inter" },
                    majorGridLines: { color: "#f0e8e0" }
                },
                tooltip: {
                    visible: true,
                    template: "#= category #: #= value # pcs (Rp #= kendo.toString(dataItem.revenue, 'n0') #)"
                },
                chartArea: { background: "transparent" }
            });
        });
    }

    $(document).ready(function() {
        loadSummary();
        loadDailySalesChart();
        loadCategoryChart();
        loadTopProductsChart();
    });
    </script>
</body>
</html>
