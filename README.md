# 🏨 HotelAurora - Sistem Pemesanan Hotel Modern

Sistem pemesanan hotel berbasis web yang lengkap dengan fitur admin panel, manajemen user, dan sistem pembayaran e-wallet.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Instalasi](#-instalasi)
- [Konfigurasi Database](#-konfigurasi-database)
- [Struktur File](#-struktur-file)
- [API Documentation](#-api-documentation)
- [Akun Default](#-akun-default)
- [Screenshot](#-screenshot)
- [Kontribusi](#-kontribusi)

## ✨ Fitur Utama

### 👥 **Sistem User**
- ✅ Registrasi dan login user
- ✅ Edit profil dengan validasi
- ✅ Dashboard user dengan rekomendasi hotel
- ✅ Riwayat transaksi terpisah dengan filter status
- ✅ Sistem cetak bukti booking (2 format berbeda)

### 🏨 **Manajemen Hotel**
- ✅ Pencarian hotel dengan filter lokasi dan harga
- ✅ Detail hotel dengan galeri foto
- ✅ Sistem booking dengan validasi stok
- ✅ Kalkulasi harga otomatis berdasarkan lama menginap

### 💳 **Sistem Pembayaran**
- ✅ Pembayaran e-wallet (GoPay, OVO, DANA, ShopeePay)
- ✅ Validasi nomor telepon otomatis dengan format
- ✅ Konfirmasi pembayaran dengan kode booking unik
- ✅ Halaman sukses dengan multiple action buttons

### 👑 **Admin Panel**
- ✅ Dashboard admin dengan statistik real-time
- ✅ **CRUD Hotel**: Tambah, Edit, Hapus, Update Stok
- ✅ **CRUD User**: Edit profil, ubah hak akses, reset password
- ✅ **Laporan Pendapatan**: Export PDF, CSV, Print
- ✅ **Manajemen Booking**: Monitor semua transaksi
- ✅ Alert stok kamar rendah dengan quick update

### 🎨 **Design & UX**
- ✅ Design futuristik dengan gradient dan animasi
- ✅ Responsive design untuk mobile dan desktop
- ✅ Loading animations dan micro-interactions
- ✅ Print-friendly layouts untuk semua dokumen

## 🛠 Teknologi

- **Backend**: PHP 7.4+, MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Framework**: Bootstrap 5.3.0
- **Icons**: Font Awesome 6.0.0
- **Database**: MySQL dengan PDO
- **Security**: Password hashing, SQL injection protection, XSS protection

## 🚀 Instalasi

### 1. **Clone Repository**
```bash
<<<<<<< HEAD
git clone https://github.com/username/HotelAurora.git
=======
git clone https://github.com/Angga-Activit/HotelAurora.git
>>>>>>> c9a955e46ae1c5c528eccc22d2ab38b737d72ce3
cd HotelAurora
```

### 2. **Setup Web Server**
- **XAMPP/WAMP**: Copy folder ke `htdocs/HotelAurora`
- **LAMP**: Copy ke `/var/www/html/HotelAurora`
- **Local Server**: `php -S localhost:8000`

### 3. **Setup Database**
1. Buat database baru bernama `HotelAurora`
2. Import file `HotelAurora.sql`
3. Update konfigurasi di `config.php`

### 4. **Konfigurasi**
Edit file `config.php`:
```php
$host = 'localhost';
$dbname = 'HotelAurora';
$username = 'root';
$password = '';
```

### 5. **Akses Aplikasi**
- **User Interface**: `http://localhost/HotelAurora/`
- **Admin Panel**: Login sebagai admin

## 🗄 Konfigurasi Database

### **Buat Database**
```sql
CREATE DATABASE HotelAurora;
USE HotelAurora;
```

### **Import Schema**
```bash
mysql -u root -p HotelAurora < HotelAurora.sql
```

### **Struktur Tabel**
- `pengguna` - Data user dan admin
- `hotel` - Data hotel dan kamar
- `pemesanan` - Data booking dan transaksi

## 📁 Struktur File

```
HotelAurora/
├── 📄 index.php              # Halaman utama & pencarian hotel
├── 📄 login.php              # Halaman login
├── 📄 daftar.php             # Registrasi user baru
├── 📄 user.php               # Dashboard user
├── 📄 riwayat.php            # Riwayat transaksi terpisah
├── 📄 admin.php              # Admin panel lengkap
├── 📄 deskripsi.php          # Detail hotel
├── 📄 pemesanan.php          # Form booking
├── 📄 pembayaran.php         # Proses pembayaran
├── 📄 update_profile.php     # Handler update profil
├── 📄 export_revenue.php     # Export laporan pendapatan
├── 📄 config.php             # Konfigurasi database
├── 📄 functions.php          # Fungsi-fungsi PHP
├── 📄 style.css              # Styling futuristik
├── 📄 script.js              # JavaScript interactions
├── 📄 validator.js           # Validasi form client-side
├── 📄 HotelAurora.sql        # Database schema + sample data
├── 📁 api/
│   ├── 📄 hotel.php          # API data hotel
│   ├── 📄 pemesanan.php      # API booking
│   └── 📄 laporan.php        # API laporan
└── 📄 README.md              # Dokumentasi ini
```

## 🔌 API Documentation

### **1. Hotel API** (`api/hotel.php`)

#### **GET - Ambil Data Hotel**
```javascript
// Semua hotel
fetch('api/hotel.php')

// Hotel berdasarkan ID
fetch('api/hotel.php?id=1')

// Pencarian hotel
fetch('api/hotel.php?search=jakarta')

// Filter berdasarkan lokasi
fetch('api/hotel.php?lokasi=Jakarta')

// Filter berdasarkan harga
fetch('api/hotel.php?min_price=100000&max_price=500000')
```

#### **Response Format**
```json
{
  "status": "success",
  "data": [
    {
      "id_hotel": 1,
      "nama_hotel": "Hotel Mewah Jakarta",
      "lokasi": "Jakarta Pusat",
      "harga_per_malam": 500000,
      "stok_kamar": 10,
      "deskripsi": "Hotel mewah di pusat kota...",
      "foto": "https://example.com/hotel.jpg"
    }
  ]
}
```

### **2. Booking API** (`api/pemesanan.php`)

#### **GET - Ambil Data Booking**
```javascript
// Booking berdasarkan user
fetch('api/pemesanan.php?user_id=1')

// Booking berdasarkan status
fetch('api/pemesanan.php?status=berhasil')

// Detail booking
fetch('api/pemesanan.php?booking_id=1')
```

#### **POST - Buat Booking Baru**
```javascript
fetch('api/pemesanan.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    id_hotel: 1,
    id_pengguna: 1,
    tanggal_checkin: '2025-01-15',
    tanggal_checkout: '2025-01-17',
    jumlah_kamar: 2,
    jumlah_orang: 4
  })
})
```

### **3. Reports API** (`api/laporan.php`)

#### **GET - Laporan Pendapatan**
```javascript
// Laporan bulanan
fetch('api/laporan.php?type=monthly')

// Laporan harian
fetch('api/laporan.php?type=daily')

// Laporan berdasarkan periode
fetch('api/laporan.php?type=period&start=2025-01-01&end=2025-01-31')
```

## 🔐 Akun Default

### **Admin**
- **Email**: `admin@HotelAurora.com`
- **Password**: `admin123`
- **Akses**: Full admin panel

### **User Demo**
- **Email**: `user@example.com`
- **Password**: `user123`
- **Akses**: User dashboard

## 🎯 Cara Penggunaan

### **Untuk User:**
1. **Registrasi** - Daftar akun baru di halaman registrasi
2. **Login** - Masuk dengan email dan password
3. **Cari Hotel** - Gunakan filter lokasi dan harga
4. **Booking** - Pilih tanggal dan jumlah kamar
5. **Bayar** - Pilih e-wallet dan masukkan nomor
6. **Cetak Bukti** - Download atau print bukti booking

### **Untuk Admin:**
1. **Login Admin** - Gunakan akun admin default
2. **Kelola Hotel** - Tambah, edit, hapus hotel
3. **Kelola User** - Edit profil, ubah hak akses user
4. **Monitor Booking** - Lihat semua transaksi
5. **Laporan** - Export pendapatan dalam PDF/CSV
6. **Update Stok** - Kelola ketersediaan kamar

## 🔧 Kustomisasi

### **Mengubah Theme**
Edit variabel CSS di `style.css`:
```css
:root {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --secondary-color: #f093fb;
  --accent-color: #4facfe;
}
```

### **Menambah Payment Gateway**
1. Edit `pembayaran.php`
2. Tambah opsi payment di form
3. Update validasi di `validator.js`

### **Kustomisasi Email**
Edit template email di `functions.php`:
```php
function sendBookingConfirmation($email, $bookingData) {
    // Kustomisasi template email
}
```

## 📊 Fitur Lanjutan

### **Real-time Features**
- Auto-refresh stok kamar
- Live booking notifications
- Real-time price updates

### **Export Features**
- PDF reports dengan header/footer
- CSV data untuk analisis Excel
- Print-friendly layouts

### **Security Features**
- Password hashing dengan bcrypt
- SQL injection protection
- XSS protection
- CSRF token validation

## 🐛 Troubleshooting

### **Database Connection Error**
```
Error: SQLSTATE[HY000] [1045] Access denied
```
**Solusi**: Periksa username/password di `config.php`

### **Permission Denied**
```
Warning: file_get_contents(): failed to open stream
```
**Solusi**: Set permission folder ke 755
```bash
chmod -R 755 HotelAurora/
```

### **Session Issues**
```
Warning: session_start(): Cannot send session cookie
```
**Solusi**: Pastikan tidak ada output sebelum `session_start()`

## 🚀 Deployment

### **Shared Hosting**
1. Upload semua file via FTP
2. Import database via cPanel
3. Update `config.php` dengan kredensial hosting

### **VPS/Dedicated Server**
1. Setup LAMP stack
2. Clone repository ke `/var/www/html/`
3. Setup virtual host
4. Configure SSL certificate

### **Docker**
```dockerfile
FROM php:7.4-apache
COPY . /var/www/html/
RUN docker-php-ext-install pdo pdo_mysql
EXPOSE 80
```

## 📈 Performance Tips

### **Database Optimization**
- Index pada kolom yang sering di-query
- Optimize query dengan LIMIT
- Use prepared statements

### **Frontend Optimization**
- Minify CSS dan JavaScript
- Optimize images
- Enable gzip compression
- Use CDN untuk Bootstrap dan Font Awesome

## 🤝 Kontribusi

1. Fork repository
2. Buat branch fitur (`git checkout -b fitur-baru`)
3. Commit perubahan (`git commit -am 'Tambah fitur baru'`)
4. Push ke branch (`git push origin fitur-baru`)
5. Buat Pull Request

## 📝 Changelog

### **v2.0.0** (2025-01-01)
- ✅ Pemisahan user dashboard dan riwayat transaksi
- ✅ Admin panel dengan full CRUD operations
- ✅ Export PDF/CSV untuk laporan pendapatan
- ✅ Sistem cetak dengan 2 format berbeda
- ✅ Validasi pembayaran yang dipermudah

<<<<<<< HEAD
### **v1.0.0** (2024-12-01)
=======
### **v1.0.0** (2025-12-01)
>>>>>>> c9a955e46ae1c5c528eccc22d2ab38b737d72ce3
- ✅ Sistem booking hotel dasar
- ✅ User registration dan login
- ✅ Admin panel sederhana
- ✅ Payment gateway e-wallet

## 📞 Support

- **Email**: support@HotelAurora.com
<<<<<<< HEAD
- **Documentation**: [Wiki](https://github.com/username/HotelAurora/wiki)
- **Issues**: [GitHub Issues](https://github.com/username/HotelAurora/issues)
=======
- **Documentation**: [Wiki](https://github.com/Angga-Activit/HotelAurora/wiki)
- **Issues**: [GitHub Issues](https://github.com/Angga-Activit/HotelAurora/issues)
>>>>>>> c9a955e46ae1c5c528eccc22d2ab38b737d72ce3

## 📄 License

MIT License - lihat file [LICENSE](LICENSE) untuk detail lengkap.

---

<<<<<<< HEAD
**Dibuat dengan ❤️ oleh Tim HotelAurora**

> **Note**: Sistem ini dibuat untuk keperluan pembelajaran dan demo. Untuk production, pastikan menambahkan security layer tambahan dan testing yang komprehensif.
=======
**Dibuat dengan ❤️ oleh Angga Kaseppp Pisannnn**

> **Note**: Sistem ini dibuat untuk keperluan pembelajaran dan demo. Untuk production, pastikan menambahkan security layer tambahan dan testing yang komprehensif.
>>>>>>> c9a955e46ae1c5c528eccc22d2ab38b737d72ce3
