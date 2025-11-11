<?php
require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$booking_id = isset($_GET['booking']) ? (int)$_GET['booking'] : 0;

if (!$booking_id) {
    redirect('user.php');
}

$stmt = $pdo->prepare("SELECT p.*, h.nama_hotel, h.lokasi, h.harga_per_malam, h.foto 
                      FROM pemesanan p 
                      JOIN hotel h ON p.id_hotel = h.id_hotel 
                      WHERE p.id_pemesanan = ? AND p.id_pengguna = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('user.php');
}

if ($booking['status'] === 'berhasil') {
    redirect('user.php');
}

$error = '';
$success = '';

if ($_POST) {
    $metode = sanitizeInput($_POST['metode']);
    $nomor_akun = sanitizeInput($_POST['nomor_akun']);

    $nomor_akun_clean = preg_replace('/[^0-9]/', '', $nomor_akun);
    
    if (empty($metode) || empty($nomor_akun_clean)) {
        $error = 'Metode pembayaran dan nomor akun wajib diisi';
    } elseif (strlen($nomor_akun_clean) < 10) {
        $error = 'Nomor akun minimal 10 digit';
    } else {
        // Process payment
        if (processPayment($booking_id, $metode, $nomor_akun_clean)) {
            $success = 'Pembayaran berhasil! Kode booking Anda: ' . $booking['kode_booking'];
            
            // Refresh booking data
            $stmt->execute([$booking_id, $_SESSION['user_id']]);
            $booking = $stmt->fetch();
        } else {
            $error = 'Pembayaran gagal. Silakan coba lagi.';
        }
    }
}

// Calculate nights
$nights = (strtotime($booking['tanggal_checkout']) - strtotime($booking['tanggal_checkin'])) / (60 * 60 * 24);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - HotelAurora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                 <h2>🏨 HotelAurora</h2>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="user.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Payment Section -->
    <section class="py-5">
        <div class="container">
            <?php if ($booking['status'] === 'berhasil'): ?>
                <!-- Payment Success -->
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card success-card">
                            <div class="card-body">
                                <div class="success-content text-center">
                                    <div class="success-animation mb-4">
                                        <div class="checkmark-circle">
                                            <div class="checkmark"></div>
                                        </div>
                                    </div>
                                    
                                    <h2 class="text-success mb-4 fw-bold">🎉 Pembayaran Berhasil!</h2>
                                    
                                    <div class="booking-success-info">
                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <img src="<?= $booking['foto'] ?: 'https://via.placeholder.com/500x300?text=Hotel+Image' ?>" 
                                                     class="img-fluid rounded shadow" alt="<?= htmlspecialchars($booking['nama_hotel']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <div class="booking-details text-start">
                                                    <h4 class="text-primary mb-3"><?= htmlspecialchars($booking['nama_hotel']) ?></h4>
                                                    <p class="text-muted mb-3">
                                                        <i class="fas fa-map-marker-alt"></i> 
                                                        <?= htmlspecialchars($booking['lokasi']) ?>
                                                    </p>
                                                    
                                                    <div class="booking-code-display mb-4">
                                                        <h5 class="mb-2">Kode Booking Anda:</h5>
                                                        <div class="code-box">
                                                            <span class="booking-code"><?= htmlspecialchars($booking['kode_booking']) ?></span>
                                                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyBookingCode()">
                                                                <i class="fas fa-copy"></i> Copy
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="booking-summary">
                                                        <div class="row mb-2">
                                                            <div class="col-6"><strong>Tamu:</strong></div>
                                                            <div class="col-6"><?= htmlspecialchars($_SESSION['nama_depan'] . ' ' . $_SESSION['nama_belakang']) ?></div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-6"><strong>Check-in:</strong></div>
                                                            <div class="col-6"><?= formatDate($booking['tanggal_checkin']) ?></div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-6"><strong>Check-out:</strong></div>
                                                            <div class="col-6"><?= formatDate($booking['tanggal_checkout']) ?></div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-6"><strong>Lama Menginap:</strong></div>
                                                            <div class="col-6"><?= $nights ?> malam</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-6"><strong>Kamar & Tamu:</strong></div>
                                                            <div class="col-6"><?= $booking['jumlah_kamar'] ?> kamar, <?= $booking['jumlah_orang'] ?> orang</div>
                                                        </div>
                                                        <hr>
                                                        <div class="row mb-3">
                                                            <div class="col-6"><strong>Total Pembayaran:</strong></div>
                                                            <div class="col-6 text-success fw-bold h5"><?= formatRupiah($booking['total_harga']) ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="success-message mb-4">
                                        <div class="alert alert-success">
                                            <h5><i class="fas fa-check-circle"></i> Terima kasih atas kepercayaan Anda!</h5>
                                            <p class="mb-0">Pemesanan Anda telah dikonfirmasi. Simpan kode booking untuk check-in di hotel.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <button class="btn btn-primary w-100" onclick="printFullBooking()">
                                                    <i class="fas fa-print"></i><br>
                                                    <small>Cetak Bukti</small>
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-success w-100" onclick="downloadPDF()">
                                                    <i class="fas fa-download"></i><br>
                                                    <small>Download PDF</small>
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-info w-100" onclick="shareBooking()">
                                                    <i class="fas fa-share-alt"></i><br>
                                                    <small>Share</small>
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <a href="user.php" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-tachometer-alt"></i><br>
                                                    <small>Dashboard</small>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Payment Form -->
                <div class="row">
                    <!-- Payment Form -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-credit-card"></i> Pembayaran</h3>
                            </div>
                            
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" id="paymentForm">
                                    <!-- Payment Methods -->
                                    <div class="mb-4">
                                        <h5><i class="fas fa-wallet"></i> Pilih Metode Pembayaran</h5>
                                        <div class="payment-methods">
                                            <div class="payment-method" data-method="Dana">
                                                <input type="radio" class="d-none" name="metode" value="Dana" id="dana">
                                                <label for="dana" class="w-100">
                                                    <div class="payment-logo">
                                                        <i class="fas fa-mobile-alt"></i>
                                                    </div>
                                                    <h6>Dana</h6>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method" data-method="OVO">
                                                <input type="radio" class="d-none" name="metode" value="OVO" id="ovo">
                                                <label for="ovo" class="w-100">
                                                    <div class="payment-logo">
                                                        <i class="fas fa-wallet"></i>
                                                    </div>
                                                    <h6>OVO</h6>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method" data-method="GoPay">
                                                <input type="radio" class="d-none" name="metode" value="GoPay" id="gopay">
                                                <label for="gopay" class="w-100">
                                                    <div class="payment-logo">
                                                        <i class="fas fa-motorcycle"></i>
                                                    </div>
                                                    <h6>GoPay</h6>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method" data-method="ShopeePay">
                                                <input type="radio" class="d-none" name="metode" value="ShopeePay" id="shopeepay">
                                                <label for="shopeepay" class="w-100">
                                                    <div class="payment-logo">
                                                        <i class="fas fa-shopping-bag"></i>
                                                    </div>
                                                    <h6>ShopeePay</h6>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Account Number -->
                                    <div class="form-group mb-4">
                                        <label for="nomor_akun" class="form-label">Nomor Akun E-Wallet *</label>
                                        <input type="text" class="form-control" id="nomor_akun" name="nomor_akun" 
                                               placeholder="Contoh: 0821-2976-2390" required>
                                        <div class="form-text">Masukkan nomor telepon yang terdaftar di e-wallet (minimal 10 digit)</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    
                                    <!-- Payment Summary -->
                                    <div class="payment-summary mb-4">
                                        <h5><i class="fas fa-receipt"></i> Ringkasan Pembayaran</h5>
                                        <div class="summary-details">
                                            <div class="d-flex justify-content-between">
                                                <span>Harga per malam:</span>
                                                <span><?= formatRupiah($booking['harga_per_malam']) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Jumlah malam:</span>
                                                <span><?= $nights ?> malam</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Jumlah kamar:</span>
                                                <span><?= $booking['jumlah_kamar'] ?> kamar</span>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Total Pembayaran:</span>
                                                <span class="text-primary"><?= formatRupiah($booking['total_harga']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-credit-card"></i> Bayar Sekarang
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Booking Summary -->
                    <div class="col-lg-4">
                        <div class="card sticky-top" style="top: 100px;">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> Detail Pemesanan</h5>
                            </div>
                            
                            <div class="card-body">
                                <div class="booking-summary">
                                    <img src="<?= $booking['foto'] ?: 'https://via.placeholder.com/400x250?text=Hotel+Image' ?>" 
                                         class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($booking['nama_hotel']) ?>">
                                    
                                    <h6 class="hotel-name"><?= htmlspecialchars($booking['nama_hotel']) ?></h6>
                                    <p class="hotel-location text-muted mb-3">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($booking['lokasi']) ?>
                                    </p>
                                    
                                    <div class="booking-details">
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-calendar-check text-primary"></i>
                                            <span>Check-in: <?= formatDate($booking['tanggal_checkin']) ?></span>
                                        </div>
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-calendar-times text-primary"></i>
                                            <span>Check-out: <?= formatDate($booking['tanggal_checkout']) ?></span>
                                        </div>
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-bed text-primary"></i>
                                            <span><?= $booking['jumlah_kamar'] ?> kamar</span>
                                        </div>
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-users text-primary"></i>
                                            <span><?= $booking['jumlah_orang'] ?> tamu</span>
                                        </div>
                                        <div class="detail-item mb-2">
                                            <i class="fas fa-moon text-primary"></i>
                                            <span><?= $nights ?> malam</span>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="total-price text-center">
                                        <div class="h4 text-success mb-0">
                                            <?= formatRupiah($booking['total_harga']) ?>
                                        </div>
                                        <small class="text-muted">Total Pembayaran</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="text-center">
                 <p>&copy; 2025 HotelAurora. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="validator.js"></script>
    
    <script>
      
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove active class from all methods
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
                
                // Add active class to clicked method
                this.classList.add('active');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Update placeholder
                const methodName = this.dataset.method;
                const accountInput = document.getElementById('nomor_akun');
                accountInput.placeholder = `Contoh: 0821-2976-2390 (${methodName})`;
            });
        });
        
        // Auto-format phone number with dashes
        document.getElementById('nomor_akun').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            // Format with dashes: 0821-2976-2390
            if (value.length > 4 && value.length <= 8) {
                value = value.slice(0, 4) + '-' + value.slice(4);
            } else if (value.length > 8) {
                value = value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8, 12);
            }
            
            e.target.value = value;
        });
        
        // Success page functions
        function copyBookingCode() {
            const bookingCode = document.querySelector('.booking-code').textContent;
            navigator.clipboard.writeText(bookingCode).then(function() {
                alert('Kode booking berhasil disalin!');
            });
        }
        
        function printFullBooking() {
            const bookingData = {
                kode_booking: '<?= htmlspecialchars($booking['kode_booking'] ?? '') ?>',
                nama_hotel: '<?= htmlspecialchars($booking['nama_hotel'] ?? '') ?>',
                lokasi: '<?= htmlspecialchars($booking['lokasi'] ?? '') ?>',
                nama_tamu: '<?= htmlspecialchars($_SESSION['nama_depan'] . ' ' . $_SESSION['nama_belakang']) ?>',
                email: '<?= htmlspecialchars($_SESSION['email']) ?>',
                checkin: '<?= formatDate($booking['tanggal_checkin'] ?? '') ?>',
                checkout: '<?= formatDate($booking['tanggal_checkout'] ?? '') ?>',
                nights: '<?= $nights ?>',
                rooms: '<?= $booking['jumlah_kamar'] ?? '' ?>',
                guests: '<?= $booking['jumlah_orang'] ?? '' ?>',
                total: '<?= formatRupiah($booking['total_harga'] ?? 0) ?>'
            };
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Bukti Booking - ${bookingData.kode_booking}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
                        .header { text-align: center; border-bottom: 3px solid #1a365d; padding-bottom: 20px; margin-bottom: 30px; }
                        .header h1 { color: #1a365d; margin: 0; font-size: 2.5em; }
                        .header h2 { color: #666; margin: 10px 0 0 0; }
                        .booking-code { font-size: 28px; font-weight: bold; color: #1a365d; margin: 20px 0; text-align: center; background: #f8f9fa; padding: 15px; border-radius: 10px; }
                        .info-section { margin: 20px 0; }
                        .info-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
                        .label { font-weight: bold; color: #333; }
                        .value { color: #666; }
                        .total-section { background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }
                        .total-amount { font-size: 24px; font-weight: bold; color: #198754; }
                        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #1a365d; }
                        .status { background: #198754; color: white; padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 10px 0; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>🏨 HotelAurora</h1>
                        <h2>Bukti Pemesanan Hotel</h2>
                    </div>
                    
                    <div class="booking-code">
                        Kode Booking: ${bookingData.kode_booking}
                    </div>
                    
                    <div class="status">✅ PEMBAYARAN BERHASIL</div>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <span class="label">Hotel:</span>
                            <span class="value">${bookingData.nama_hotel}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Lokasi:</span>
                            <span class="value">${bookingData.lokasi}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nama Tamu:</span>
                            <span class="value">${bookingData.nama_tamu}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Email:</span>
                            <span class="value">${bookingData.email}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Check-in:</span>
                            <span class="value">${bookingData.checkin}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Check-out:</span>
                            <span class="value">${bookingData.checkout}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Lama Menginap:</span>
                            <span class="value">${bookingData.nights} malam</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Jumlah Kamar:</span>
                            <span class="value">${bookingData.rooms} kamar</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Jumlah Tamu:</span>
                            <span class="value">${bookingData.guests} orang</span>
                        </div>
                    </div>
                    
                    <div class="total-section">
                        <div>Total Pembayaran:</div>
                        <div class="total-amount">${bookingData.total}</div>
                    </div>
                    
                    <div class="footer">
                        <p><strong>Tunjukkan bukti ini saat check-in di hotel</strong></p>
                        <p>Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                        <p>Terima kasih telah mempercayai HotelAurora!</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        function downloadPDF() {
            alert('Fitur download PDF akan segera tersedia!');
        }
        
        function shareBooking() {
            if (navigator.share) {
                navigator.share({
                    title: 'Booking HotelAurora Berhasil!',
                    text: 'Saya baru saja booking hotel di HotelAurora. Kode booking: <?= htmlspecialchars($booking['kode_booking'] ?? '') ?>',
                    url: window.location.href
                });
            } else {
                alert('Fitur share tidak didukung di browser ini');
            }
        }
    </script>

    <!-- Success Animation CSS -->
    <style>
        .success-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .checkmark-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #198754;
            margin: 0 auto;
            position: relative;
            animation: scaleIn 0.5s ease-in-out;
        }
        
        .checkmark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 15px;
            border: 3px solid white;
            border-top: none;
            border-right: none;
            transform: translate(-50%, -60%) rotate(-45deg);
            animation: checkmarkDraw 0.5s ease-in-out 0.3s both;
        }
        
        .code-box {
            background: #f8f9fa;
            border: 2px dashed #1a365d;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .booking-code {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1a365d;
            font-family: monospace;
        }
        
        .action-buttons .btn {
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .action-buttons .btn i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes checkmarkDraw {
            from {
                opacity: 0;
                transform: translate(-50%, -60%) rotate(-45deg) scale(0);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -60%) rotate(-45deg) scale(1);
            }
        }
    </style>
</body>
</html> 