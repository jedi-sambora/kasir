// js/bluetooth-printer.js

class BluetoothPrinter {
    constructor() {
        this.device = null;
        this.server = null;
        this.service = null;
        this.characteristic = null;
        
        // Standard ESC/POS Bluetooth UUIDs (Generic Thermal Printer)
        // UUID ini mungkin perlu disesuaikan dengan merek printer Anda
        this.SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
        this.CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
    }

    async connect() {
        if (!navigator.bluetooth) {
            alert('Web Bluetooth API tidak didukung di browser ini. Gunakan Chrome, pastikan berjalan dalam HTTPS atau localhost.');
            return false;
        }

        try {
            console.log('Mencari Bluetooth Printer...');
            // Karena kita mungkin tidak tahu service UUID pasti, acceptAllDevices: true seringkali membantu testing awal
            this.device = await navigator.bluetooth.requestDevice({
                filters: [{ services: [this.SERVICE_UUID] }],
                optionalServices: [this.SERVICE_UUID]
            });
            
            console.log('Menghubungkan ke GATT Server...');
            this.server = await this.device.gatt.connect();

            console.log('Mendapatkan Service...');
            this.service = await this.server.getPrimaryService(this.SERVICE_UUID);

            console.log('Mendapatkan Characteristic...');
            this.characteristic = await this.service.getCharacteristic(this.CHARACTERISTIC_UUID);
            
            console.log('Printer Terkoneksi!');
            return true;
        } catch(error) {
            console.error('Koneksi Gagal:', error);
            // Coba fallback dengan request allowAllDevices jika UUID spesifik gagal
            alert('Koneksi gagal: ' + error.message + '\n\nPastikan GPS menyala, Bluetooth aktif, dan lokasi diizinkan di browser.');
            return false;
        }
    }

    async printReceipt(cart, total) {
        if (!this.characteristic) {
            alert('Tidak bisa mencetak: Printer belum terkoneksi.');
            return false;
        }

        let encoder = new TextEncoder();
        let bytes = [];

        // INIT PRINTER: ESC @
        bytes.push(0x1B, 0x40);

        // ALIGN CENTER: ESC a 1
        bytes.push(0x1B, 0x61, 0x01);
        
        // Header Teks
        bytes.push(...encoder.encode("BERKAH POS - OFFLINE\n"));
        bytes.push(...encoder.encode("Struk Pembayaran\n"));
        bytes.push(...encoder.encode("--------------------------------\n"));
        
        // ALIGN LEFT: ESC a 0
        bytes.push(0x1B, 0x61, 0x00);

        cart.forEach(item => {
            bytes.push(...encoder.encode(`${item.name}\n`));
            let qtyStr = `${item.qty}x`.padEnd(5, ' ');
            let priceStr = `Rp ${item.price.toLocaleString('id-ID')}`.padEnd(12, ' ');
            let subtotalStr = `Rp ${(item.qty*item.price).toLocaleString('id-ID')}`.padStart(15, ' ');
            
            bytes.push(...encoder.encode(`${qtyStr}${priceStr}${subtotalStr}\n`));
        });

        bytes.push(...encoder.encode("--------------------------------\n"));
        
        // ALIGN CENTER: ESC a 1
        bytes.push(0x1B, 0x61, 0x01);
        bytes.push(...encoder.encode(`TOTAL: Rp ${total.toLocaleString('id-ID')}\n`));
        bytes.push(...encoder.encode("Terima Kasih!\n"));
        
        bytes.push(...encoder.encode("\n\n\n")); // Feed paper lines

        try {
            // BLE GATT maximum write chunk size is usually 20 bytes
            let buffer = new Uint8Array(bytes);
            
            let chunkSize = Math.min(20, this.characteristic.writeValueWithResponse ? 512 : 20); // Optimistic chunk size if possible

            for (let i = 0; i < buffer.byteLength; i += chunkSize) {
                let chunk = buffer.slice(i, i + chunkSize);
                await this.characteristic.writeValue(chunk);
            }
            console.log('Selesai mencetak!');
            return true;
        } catch(e) {
            console.error('Print Error:', e);
            alert('Gagal mengirim data cetak ke printer: ' + e.message);
            return false;
        }
    }
}
window.AppPrinter = new BluetoothPrinter();
