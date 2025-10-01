<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user bookings
$bookings = getUserBookings($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - HotelFuture</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-hotel"></i> HotelFuture
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="riwayat.php">
                                <i class="fas fa-history"></i> Riwayat Transaksi
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nama_depan']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="user.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-6 fw-bold mb-3">
                        <i class="fas fa-history"></i> Riwayat Transaksi
                    </h1>
                    <p class="lead mb-0">
                        Kelola dan lihat semua riwayat pemesanan hotel Anda
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Pesan Hotel Baru
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Transaction History -->
    <section class="py-5">
        <div class="container">
            <!-- Filter Buttons -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0">Filter Transaksi</h5>
                        
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="statusFilter" id="all" autocomplete="off" checked>
                            <label class="btn btn-outline-primary" for="all" onclick="filterBookings('')">
                                <i class="fas fa-list"></i> Semua
                            </label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="success" autocomplete="off">
                            <label class="btn btn-outline-success" for="success" onclick="filterBookings('berhasil')">
                                <i class="fas fa-check-circle"></i> Berhasil
                            </label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="pending" autocomplete="off">
                            <label class="btn btn-outline-warning" for="pending" onclick="filterBookings('pending')">
                                <i class="fas fa-clock"></i> Pending
                            </label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="cancelled" autocomplete="off">
                            <label class="btn btn-outline-danger" for="cancelled" onclick="filterBookings('batal')">
                                <i class="fas fa-times-circle"></i> Batal
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Cards -->
            <div id="transactionContainer">
                <?php if (empty($bookings)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-5x text-muted mb-4"></i>
                            <h3>Belum Ada Transaksi</h3>
                            <p class="text-muted mb-4">Anda belum memiliki riwayat pemesanan hotel</p>
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-search"></i> Mulai Cari Hotel
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $nights = (strtotime($booking['tanggal_checkout']) - strtotime($booking['tanggal_checkin'])) / (60 * 60 * 24);
                            $statusClass = $booking['status'] === 'berhasil' ? 'success' : 
                                          ($booking['status'] === 'pending' ? 'warning' : 'danger');
                            $statusIcon = $booking['status'] === 'berhasil' ? 'check-circle' : 
                                         ($booking['status'] === 'pending' ? 'clock' : 'times-circle');
                            ?>
                            
                            <div class="col-lg-6 mb-4" data-status="<?= $booking['status'] ?>">
                                <div class="card transaction-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($booking['nama_hotel']) ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?= htmlspecialchars($booking['lokasi']) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?= $statusClass ?> fs-6">
                                            <i class="fas fa-<?= $statusIcon ?>"></i> 
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="booking-detail">
                                                    <i class="fas fa-calendar-check text-primary"></i>
                                                    <div>
                                                        <small class="text-muted">Check-in</small>
                                                        <div class="fw-bold"><?= formatDate($booking['tanggal_checkin']) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-6">
                                                <div class="booking-detail">
                                                    <i class="fas fa-calendar-times text-primary"></i>
                                                    <div>
                                                        <small class="text-muted">Check-out</small>
                                                        <div class="fw-bold"><?= formatDate($booking['tanggal_checkout']) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-4">
                                                <div class="text-center">
                                                    <i class="fas fa-moon text-primary"></i>
                                                    <div class="fw-bold"><?= $nights ?></div>
                                                    <small class="text-muted">Malam</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-4">
                                                <div class="text-center">
                                                    <i class="fas fa-bed text-primary"></i>
                                                    <div class="fw-bold"><?= $booking['jumlah_kamar'] ?></div>
                                                    <small class="text-muted">Kamar</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-4">
                                                <div class="text-center">
                                                    <i class="fas fa-users text-primary"></i>
                                                    <div class="fw-bold"><?= $booking['jumlah_orang'] ?></div>
                                                    <small class="text-muted">Tamu</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['kode_booking']): ?>
                                            <div class="booking-code mb-3">
                                                <small class="text-muted">Kode Booking:</small>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($booking['kode_booking']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['metode']): ?>
                                            <div class="payment-info mb-3">
                                                <small class="text-muted">Pembayaran:</small>
                                                <div><?= htmlspecialchars($booking['metode']) ?> - <?= htmlspecialchars($booking['nomor_akun']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="total-price mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Total Pembayaran:</span>
                                                <span class="h5 text-success mb-0"><?= formatRupiah($booking['total_harga']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-primary btn-sm flex-fill" 
                                                    onclick="showBookingDetail(<?= htmlspecialchars(json_encode($booking)) ?>)">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                            
                                            <?php if ($booking['status'] === 'berhasil'): ?>
                                                <button class="btn btn-primary btn-sm flex-fill" 
                                                        onclick="printBooking('<?= $booking['kode_booking'] ?>', <?= htmlspecialchars(json_encode($booking)) ?>)">
                                                    <i class="fas fa-print"></i> Cetak
                                                </button>
                                            <?php elseif ($booking['status'] === 'pending'): ?>
                                                <a href="pembayaran.php?booking=<?= $booking['id_pemesanan'] ?>" 
                                                   class="btn btn-warning btn-sm flex-fill">
                                                    <i class="fas fa-credit-card"></i> Bayar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Booking Detail Modal -->
    <div class="modal fade" id="bookingDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pemesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="bookingDetailContent">
                    <!-- Content will be loaded here -->
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="printFromDetailBtn">
                        <i class="fas fa-print"></i> Cetak Detail
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center">
                <p>&copy; 2025 HotelFuture. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    
    <script>
        // Filter bookings by status
        function filterBookings(status) {
            const cards = document.querySelectorAll('[data-status]');
            
            cards.forEach(card => {
                const cardContainer = card.closest('.col-lg-6');
                if (status === '' || card.dataset.status === status) {
                    cardContainer.style.display = '';
                } else {
                    cardContainer.style.display = 'none';
                }
            });
        }
        
        // Show booking detail in modal
        function showBookingDetail(booking) {
            const nights = Math.ceil((new Date(booking.tanggal_checkout) - new Date(booking.tanggal_checkin)) / (1000 * 60 * 60 * 24));
            
            const content = `
                <div class="booking-detail-content">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Hotel</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td>Nama Hotel:</td>
                                    <td><strong>${booking.nama_hotel}</strong></td>
                                </tr>
                                <tr>
                                    <td>Lokasi:</td>
                                    <td>${booking.lokasi}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Informasi Tamu</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td>Nama:</td>
                                    <td><strong><?= htmlspecialchars($_SESSION['nama_depan'] . ' ' . $_SESSION['nama_belakang']) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Email:</td>
                                    <td><?= htmlspecialchars($_SESSION['email']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Detail Pemesanan</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td>Check-in:</td>
                                    <td><strong>${formatDate(booking.tanggal_checkin)}</strong></td>
                                </tr>
                                <tr>
                                    <td>Check-out:</td>
                                    <td><strong>${formatDate(booking.tanggal_checkout)}</strong></td>
                                </tr>
                                <tr>
                                    <td>Lama Menginap:</td>
                                    <td><strong>${nights} malam</strong></td>
                                </tr>
                                <tr>
                                    <td>Jumlah Kamar:</td>
                                    <td><strong>${booking.jumlah_kamar} kamar</strong></td>
                                </tr>
                                <tr>
                                    <td>Jumlah Tamu:</td>
                                    <td><strong>${booking.jumlah_orang} orang</strong></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Informasi Pembayaran</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td>Kode Booking:</td>
                                    <td><strong class="text-primary">${booking.kode_booking || '-'}</strong></td>
                                </tr>
                                <tr>
                                    <td>Metode Pembayaran:</td>
                                    <td>${booking.metode || '-'}</td>
                                </tr>
                                <tr>
                                    <td>Nomor Akun:</td>
                                    <td>${booking.nomor_akun || '-'}</td>
                                </tr>
                                <tr>
                                    <td>Status:</td>
                                    <td><span class="badge bg-${booking.status === 'berhasil' ? 'success' : (booking.status === 'pending' ? 'warning' : 'danger')}">${booking.status.toUpperCase()}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Harga:</strong></td>
                                    <td><strong class="text-success">${formatRupiah(booking.total_harga)}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('bookingDetailContent').innerHTML = content;
            
            // Set up print button
            document.getElementById('printFromDetailBtn').onclick = function() {
                printBookingDetail(booking);
            };
            
            const modal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
            modal.show();
        }
        
        // Print booking from transaction list
        function printBooking(bookingCode, booking) {
            const nights = Math.ceil((new Date(booking.tanggal_checkout) - new Date(booking.tanggal_checkin)) / (1000 * 60 * 60 * 24));
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Bukti Booking - ${bookingCode}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
                        .header { text-align: center; border-bottom: 3px solid #1a365d; padding-bottom: 20px; margin-bottom: 30px; }
                        .booking-code { font-size: 28px; font-weight: bold; color: #1a365d; margin: 20px 0; }
                        .info-section { margin: 20px 0; }
                        .info-row { display: flex; justify-content: space-between; margin: 8px 0; }
                        .label { font-weight: bold; }
                        .total { font-size: 20px; font-weight: bold; color: #38a169; margin-top: 20px; }
                        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1 style="color: #1a365d; margin: 0;">🏨 HotelFuture</h1>
                        <h2 style="margin: 10px 0;">Bukti Pemesanan Hotel</h2>
                        <div class="booking-code">${bookingCode}</div>
                    </div>
                    
                    <div class="info-section">
                        <h3>Informasi Hotel</h3>
                        <div class="info-row">
                            <span class="label">Nama Hotel:</span>
                            <span>${booking.nama_hotel}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Lokasi:</span>
                            <span>${booking.lokasi}</span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h3>Detail Pemesanan</h3>
                        <div class="info-row">
                            <span class="label">Check-in:</span>
                            <span>${formatDate(booking.tanggal_checkin)}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Check-out:</span>
                            <span>${formatDate(booking.tanggal_checkout)}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Lama Menginap:</span>
                            <span>${nights} malam</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Jumlah Kamar:</span>
                            <span>${booking.jumlah_kamar} kamar</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Jumlah Tamu:</span>
                            <span>${booking.jumlah_orang} orang</span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h3>Informasi Tamu</h3>
                        <div class="info-row">
                            <span class="label">Nama:</span>
                            <span><?= htmlspecialchars($_SESSION['nama_depan'] . ' ' . $_SESSION['nama_belakang']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email:</span>
                            <span><?= htmlspecialchars($_SESSION['email']) ?></span>
                        </div>
                    </div>
                    
                    <div class="total">
                        Total Pembayaran: ${formatRupiah(booking.total_harga)}
                    </div>
                    
                    <div class="footer">
                        <p><strong>Status: BERHASIL - Booking Dikonfirmasi</strong></p>
                        <p>Tunjukkan bukti ini saat check-in di hotel.</p>
                        <p>Terima kasih telah menggunakan HotelFuture!</p>
                        <p>Dicetak pada: ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Print booking detail from modal
        function printBookingDetail(booking) {
            const nights = Math.ceil((new Date(booking.tanggal_checkout) - new Date(booking.tanggal_checkin)) / (1000 * 60 * 60 * 24));
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Detail Pemesanan - ${booking.kode_booking}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 30px; line-height: 1.8; }
                        .header { text-align: center; border-bottom: 3px solid #1a365d; padding-bottom: 30px; margin-bottom: 40px; }
                        .booking-code { font-size: 32px; font-weight: bold; color: #1a365d; margin: 25px 0; }
                        .section { margin: 30px 0; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; }
                        .section h3 { color: #1a365d; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
                        .info-row { display: flex; justify-content: space-between; margin: 12px 0; padding: 8px 0; }
                        .label { font-weight: bold; color: #2d3748; }
                        .value { color: #1a202c; }
                        .total { font-size: 24px; font-weight: bold; color: #38a169; margin-top: 30px; text-align: center; padding: 20px; background: #f0fff4; border-radius: 10px; }
                        .footer { margin-top: 50px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #e2e8f0; padding-top: 20px; }
                        .status { font-size: 18px; font-weight: bold; color: #38a169; text-align: center; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1 style="color: #1a365d; margin: 0; font-size: 36px;">🏨 HotelFuture</h1>
                        <h2 style="margin: 15px 0; color: #2d3748;">Detail Pemesanan Hotel</h2>
                        <div class="booking-code">${booking.kode_booking || 'N/A'}</div>
                    </div>
                    
                    <div class="section">
                        <h3>🏨 Informasi Hotel</h3>
                        <div class="info-row">
                            <span class="label">Nama Hotel:</span>
                            <span class="value">${booking.nama_hotel}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Lokasi:</span>
                            <span class="value">${booking.lokasi}</span>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>📅 Detail Pemesanan</h3>
                        <div class="info-row">
                            <span class="label">Tanggal Check-in:</span>
                            <span class="value">${formatDate(booking.tanggal_checkin)}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Tanggal Check-out:</span>
                            <span class="value">${formatDate(booking.tanggal_checkout)}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Lama Menginap:</span>
                            <span class="value">${nights} malam</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Jumlah Kamar:</span>
                            <span class="value">${booking.jumlah_kamar} kamar</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Jumlah Tamu:</span>
                            <span class="value">${booking.jumlah_orang} orang</span>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>👤 Informasi Tamu</h3>
                        <div class="info-row">
                            <span class="label">Nama Lengkap:</span>
                            <span class="value"><?= htmlspecialchars($_SESSION['nama_depan'] . ' ' . $_SESSION['nama_belakang']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email:</span>
                            <span class="value"><?= htmlspecialchars($_SESSION['email']) ?></span>
                        </div>
                    </div>
                    
                    <div class="section">
                        <h3>💳 Informasi Pembayaran</h3>
                        <div class="info-row">
                            <span class="label">Metode Pembayaran:</span>
                            <span class="value">${booking.metode || 'Belum dibayar'}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nomor Akun:</span>
                            <span class="value">${booking.nomor_akun || '-'}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Tanggal Pemesanan:</span>
                            <span class="value">${formatDate(booking.tanggal_pemesanan)}</span>
                        </div>
                    </div>
                    
                    <div class="status">
                        Status Pemesanan: ${booking.status.toUpperCase()}
                    </div>
                    
                    <div class="total">
                        Total Pembayaran: ${formatRupiah(booking.total_harga)}
                    </div>
                    
                    <div class="footer">
                        <p><strong>Terima kasih telah menggunakan HotelFuture!</strong></p>
                        <p>Tunjukkan bukti ini saat check-in di hotel.</p>
                        <p>Untuk bantuan, hubungi customer service kami di info@hotelfuture.com</p>
                        <p>Dicetak pada: ${new Date().toLocaleDateString('id-ID')} pukul ${new Date().toLocaleTimeString('id-ID')}</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Utility functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
    </script>
</body>
</html>