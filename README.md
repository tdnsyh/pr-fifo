# FIFO Inventory (PHP Native)

## Cara Jalankan (Lokalan)
1. Buat database MySQL dan jalankan schema:
   - Import `sql/schema.sql`
   - (Opsional) import `sql/sample_data.sql`
2. Ubah kredensial di `config.php`.
3. Jalankan di server PHP lokal (XAMPP/MAMP/Laragon) dan arahkan Document Root ke folder `public/` atau gunakan:
   ```
   php -S localhost:8000 -t public
   ```
4. Buka `http://localhost:8000/auth/login.php`
   - Buat user admin manual:
     Jalankan perintah PHP untuk menghasilkan hash:
     ```php
     <?php echo password_hash('admin', PASSWORD_BCRYPT); ?>
     ```
     Lalu:
     ```sql
     INSERT INTO users (username, password_hash) VALUES ('admin','<HASH_DIATAS>');
     ```

## Fitur Utama
- Items, Suppliers CRUD
- Barang Masuk -> membuat *batch* stok (menyimpan `qty_remaining`, `cost_per_unit`, `received_at`, `expiry_date`)
- Barang Keluar (FIFO) -> menghabiskan stok dari batch tertua, membuat `issue_lines` + `stock_movements`
- Penyesuaian Stok per batch
- Laporan Stok ringkas + Riwayat pergerakan stok
- Autentikasi sederhana (session)

## Catatan
- Fokus ke logika FIFO yang **transaksional** (BEGIN/COMMIT + SELECT ... FOR UPDATE).
- Sesuaikan *base_url* di `config.php` jika aplikasi berada di subfolder.
