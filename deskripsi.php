<?php
require_once 'config.php';
require_once 'functions.php';

// Get hotel ID
$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$hotel_id) {
    redirect('index.php');
}

// Get hotel details
$hotel = getHotelById($hotel_id);

if (!$hotel) {
    redirect('index.php');
}

// Check if user must login to book
$must_login = !isLoggedIn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hotel['nama_hotel']) ?> - HotelAurora</title>
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
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Beranda</a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= isAdmin() ? 'admin.php' : 'user.php' ?>">
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="daftar.php">
                                    <i class="fas fa-user-plus"></i> Daftar
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Breadcrumb -->
    <section class="py-3 bg-light">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Hotel</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($hotel['nama_hotel']) ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Hotel Details -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Hotel Images -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="hotel-image-gallery">
                            <img src="<?= $hotel['foto'] ?: 'https://via.placeholder.com/800x400?text=Hotel+Image' ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($hotel['nama_hotel']) ?>" 
                                 style="height: 400px; object-fit: cover;">
                        </div>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h1 class="card-title hotel-name mb-2">
                                        <?= htmlspecialchars($hotel['nama_hotel']) ?>
                                    </h1>
                                    <p class="hotel-location text-muted mb-0">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($hotel['lokasi']) ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <div class="hotel-price-large">
                                        <?= formatRupiah($hotel['harga_per_malam']) ?>
                                    </div>
                                    <small class="text-muted">per malam</small>
                                </div>
                            </div>
                            
                            <!-- Stock Warning -->
                            <?php if ($hotel['stok_kamar'] <= 3 && $hotel['stok_kamar'] > 0): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Kamar hampir habis!</strong> Hanya tersisa <?= $hotel['stok_kamar'] ?> kamar.
                                </div>
                            <?php elseif ($hotel['stok_kamar'] == 0): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle"></i>
                                    <strong>Maaf, kamar tidak tersedia</strong> untuk tanggal yang dipilih.
                                </div>
                            <?php endif; ?>
                            
                            <!-- Hotel Description -->
                            <div class="hotel-description">
                                <h4><i class="fas fa-info-circle"></i> Deskripsi Hotel</h4>
                                <p><?= nl2br(htmlspecialchars($hotel['deskripsi'])) ?></p>
                            </div>
                            
                            <!-- Hotel Facilities -->
                            <div class="hotel-facilities mt-4">
                                <h4><i class="fas fa-star"></i> Fasilitas</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-wifi text-primary"></i> WiFi Gratis</li>
                                            <li><i class="fas fa-swimming-pool text-primary"></i> Kolam Renang</li>
                                            <li><i class="fas fa-dumbbell text-primary"></i> Fitness Center</li>
                                            <li><i class="fas fa-utensils text-primary"></i> Restaurant</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-car text-primary"></i> Parkir Gratis</li>
                                            <li><i class="fas fa-concierge-bell text-primary"></i> Room Service</li>
                                            <li><i class="fas fa-snowflake text-primary"></i> AC</li>
                                            <li><i class="fas fa-tv text-primary"></i> TV Cable</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Google Maps -->
                            <div class="hotel-map mt-4">
                                <h4><i class="fas fa-map"></i> Lokasi</h4>
                                <div class="map-container">
                                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.521260322283!2d106.8195613!3d-6.2087634!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5390917b759%3A0x6b45e67356080477!2sHotel%20Indonesia%20Kempinski!5e0!3m2!1sen!2sid!4v1635123456789!5m2!1sen!2sid" 
                                            width="100%" height="300" style="border:0; border-radius: 15px;" 
                                            allowfullscreen="" loading="lazy"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Panel -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 100px;">
                        <div class="card-header">
                            <h4><i class="fas fa-calendar-check"></i> Pesan Sekarang</h4>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($must_login): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Login diperlukan</strong><br>
                                    Silakan login terlebih dahulu untuk melakukan pemesanan.
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </a>
                                    <a href="daftar.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus"></i> Daftar
                                    </a>
                                </div>
                            <?php elseif ($hotel['stok_kamar'] == 0): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle"></i>
                                    Maaf, kamar tidak tersedia.
                                </div>
                                
                                <div class="d-grid">
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-ban"></i> Tidak Tersedia
                                    </button>
                                </div>
                            <?php else: ?>
                                <form action="pemesanan.php" method="GET" id="quickBookingForm">
                                    <input type="hidden" name="hotel_id" value="<?= $hotel['id_hotel'] ?>">
                                    
                                    <div class="form-group mb-3">
                                        <label class="form-label">Check-in</label>
                                        <input type="date" class="form-control" name="checkin" id="checkinDate" required>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label class="form-label">Check-out</label>
                                        <input type="date" class="form-control" name="checkout" id="checkoutDate" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label">Kamar</label>
                                                <select class="form-select" name="rooms" id="roomCount" required>
                                                    <?php for ($i = 1; $i <= $hotel['stok_kamar']; $i++): ?>
                                                        <option value="<?= $i ?>"><?= $i ?> kamar</option>
                                                    <?php endfor; ?>
                                                </select>
                                                <small class="form-text text-muted">Tersedia <?= $hotel['stok_kamar'] ?> kamar</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label">Tamu</label>
                                                <select class="form-select" name="guests" id="guestCount" required>
                                                    <option value="1">1 tamu</option>
                                                    <option value="2">2 tamu</option>
                                                </select>
                                                <small class="form-text text-muted">Maksimal 2 tamu per kamar</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Price Calculation -->
                                    <div class="price-summary mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Harga per malam:</span>
                                            <span><?= formatRupiah($hotel['harga_per_malam']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Jumlah malam:</span>
                                            <span id="nightCount">-</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span id="totalPrice">-</span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-calendar-check"></i> Lanjut Pemesanan
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('checkinDate').min = today;
        document.getElementById('checkoutDate').min = today;
        
        // Calculate price when dates change
        document.getElementById('checkinDate').addEventListener('change', calculatePrice);
        document.getElementById('checkoutDate').addEventListener('change', calculatePrice);
        document.getElementById('roomCount').addEventListener('change', function() {
            updateGuestOptions();
            calculatePrice();
        });
        
        // Update guest options based on room count
        function updateGuestOptions() {
            const roomCount = parseInt(document.getElementById('roomCount').value);
            const guestSelect = document.getElementById('guestCount');
            const maxGuests = roomCount * 2; // 2 guests per room
            
            // Clear existing options
            guestSelect.innerHTML = '';
            
            // Add new options up to maximum capacity
            for (let i = 1; i <= maxGuests; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i + ' tamu';
                guestSelect.appendChild(option);
            }
        }
        
        // Initialize guest options on page load
        updateGuestOptions();
        
        function calculatePrice() {
            const checkin = document.getElementById('checkinDate').value;
            const checkout = document.getElementById('checkoutDate').value;
            const rooms = parseInt(document.getElementById('roomCount').value);
            const pricePerNight = <?= $hotel['harga_per_malam'] ?>;
            
            if (checkin && checkout) {
                const checkinDate = new Date(checkin);
                const checkoutDate = new Date(checkout);
                const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
                
                if (nights > 0) {
                    const total = nights * rooms * pricePerNight;
                    document.getElementById('nightCount').textContent = nights + ' malam';
                    document.getElementById('totalPrice').textContent = formatRupiah(total);
                    
                    // Update checkout minimum date
                    const nextDay = new Date(checkinDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    document.getElementById('checkoutDate').min = nextDay.toISOString().split('T')[0];
                } else {
                    document.getElementById('nightCount').textContent = '-';
                    document.getElementById('totalPrice').textContent = '-';
                }
            }
        }
        
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
    </script>
</body>
</html>