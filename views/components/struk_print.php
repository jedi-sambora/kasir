<!-- Receipt Print Template (hidden, used for printing) -->
<div id="receipt-print-area" class="receipt-print" style="display:none;">
    <div class="receipt-header">
        <h2>☕ CAFÉ POS</h2>
        <p>Jl. Kopi Nusantara No.1</p>
        <p>Telp: (021) 123-4567</p>
    </div>
    <div class="receipt-info">
        <div>No: <span id="rp-invoice"></span></div>
        <div>Kasir: <span id="rp-kasir"></span></div>
        <div>Tgl: <span id="rp-date"></span></div>
    </div>
    <div class="receipt-items" id="rp-items">
        <!-- items filled by JS -->
    </div>
    <div class="receipt-totals">
        <div class="receipt-total-row">
            <span>Total</span>
            <span id="rp-total"></span>
        </div>
        <div class="receipt-total-row">
            <span>Bayar</span>
            <span id="rp-bayar"></span>
        </div>
        <div class="receipt-total-row grand">
            <span>Kembali</span>
            <span id="rp-kembali"></span>
        </div>
    </div>
    <div class="receipt-footer">
        <p>Terima kasih atas kunjungan Anda!</p>
        <p>Selamat menikmati ☕</p>
    </div>
</div>
