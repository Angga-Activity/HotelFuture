<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get hotel ID and booking details
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$rooms = isset($_GET['rooms']) ? (int)$_GET['rooms'] : 1;
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

if (!$hotel_id) {
    redirect('user.php');
}

// Get hotel details
$hotel = getHotelById($hotel_id);
if (!$hotel) {
    redirect('user.php');
}

$error = '';
$success = '';

// Process booking form
if ($_POST) {
    $nama_depan = sanitizeInput($_POST['nama_depan']);
    $nama_belakang = sanitizeInput($_POST['nama_belakang']);
    $email = sanitizeInput($_POST['email']);
    $tanggal_checkin = $_POST['tanggal_checkin'];
    $tanggal_checkout = $_POST['tanggal_checkout'];
    $jumlah_orang = (int)$_POST['jumlah_orang'];
    $jumlah_kamar = (int)$_POST['jumlah_kamar'];
    
    // Validation
    if (empty($nama_depan) || empty($nama_belakang) || empty($email) || 
        empty($tanggal_checkin) || empty($tanggal_checkout) || 
        $jumlah_orang < 1 || $jumlah_kamar < 1) {
        $error = 'Semua field wajib diisi dengan benar';
    } elseif (!validateDate($tanggal_checkin) || !validateDate($tanggal_checkout)) {
        $error = 'Format tanggal tidak valid';
    } elseif (strtotime($tanggal_checkout) <= strtotime($tanggal_checkin)) {
        $error = 'Tanggal check-out harus setelah check-in';
    } elseif ($jumlah_kamar > $hotel['stok_kamar']) {
        $error = 'Jumlah kamar melebihi stok yang tersedia';
    } elseif ($jumlah_orang > ($jumlah_kamar * 2)) {
        $error = 'Jumlah tamu melebihi kapasitas kamar. Maksimal 2 orang per kamar.';
    } else {
        // Create booking
        $booking_id = createBooking($_SESSION['user_id'], $hotel_id, $tanggal_checkin, $tanggal_checkout, $jumlah_orang, $jumlah_kamar);
        
        if ($booking_id) {
            redirect('pembayaran.php?booking=' . $booking_id);
        } else {
            $error = 'Terjadi kesalahan saat membuat pemesanan';
        }
    }
}

// Calculate price if dates are provided
$nights = 0;
$total_price = 0;
if ($checkin && $checkout && validateDate($checkin) && validateDate($checkout)) {
    $nights = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
    if ($nights > 0) {
        $total_price = $nights * $rooms * $hotel['harga_per_malam'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - <?= htmlspecialchars($hotel['nama_hotel']) ?> - HotelAurora</title>
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

    <!-- Breadcrumb -->
    <section class="py-3 bg-light">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="deskripsi.php?id=<?= $hotel['id_hotel'] ?>"><?= htmlspecialchars($hotel['nama_hotel']) ?></a></li>
                    <li class="breadcrumb-item active">Pemesanan</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Booking Form -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Booking Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-edit"></i> Form Pemesanan</h3>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="bookingForm">
                                <input type="hidden" name="hotel_id" value="<?= $hotel['id_hotel'] ?>">
                                
                                <!-- Guest Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5><i class="fas fa-user"></i> Informasi Tamu</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="nama_depan" class="form-label">Nama Depan *</label>
                                                    <input type="text" class="form-control" id="nama_depan" name="nama_depan" 
                                                           value="<?= htmlspecialchars($_SESSION['nama_depan']) ?>" required>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="nama_belakang" class="form-label">Nama Belakang *</label>
                                                    <input type="text" class="form-control" id="nama_belakang" name="nama_belakang" 
                                                           value="<?= htmlspecialchars($_SESSION['nama_belakang']) ?>" required>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly>
                                            <div class="form-text">Email tidak dapat diubah</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Booking Details -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5><i class="fas fa-calendar"></i> Detail Pemesanan</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="tanggal_checkin" class="form-label">Tanggal Check-in *</label>
                                                    <input type="date" class="form-control" id="tanggal_checkin" name="tanggal_checkin" 
                                                           value="<?= htmlspecialchars($checkin) ?>" required>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="tanggal_checkout" class="form-label">Tanggal Check-out *</label>
                                                    <input type="date" class="form-control" id="tanggal_checkout" name="tanggal_checkout" 
                                                           value="<?= htmlspecialchars($checkout) ?>" required>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="jumlah_kamar" class="form-label">Jumlah Kamar *</label>
                                                    <select class="form-select" id="jumlah_kamar" name="jumlah_kamar" 
                                                            data-max-stock="<?= $hotel['stok_kamar'] ?>" required>
                                                        <?php for ($i = 1; $i <= $hotel['stok_kamar']; $i++): ?>
                                                            <option value="<?= $i ?>" <?= $i == $rooms ? 'selected' : '' ?>>
                                                                <?= $i ?> kamar
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <div class="form-text">Tersedia <?= $hotel['stok_kamar'] ?> kamar</div>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="jumlah_orang" class="form-label">Jumlah Tamu *</label>
                                                    <select class="form-select" id="jumlah_orang" name="jumlah_orang" required>
                                                        <?php 
                                                        $max_guests = min($rooms * 2, $hotel['stok_kamar'] * 2); // Maximum 2 guests per room
                                                        for ($i = 1; $i <= $max_guests; $i++): ?>
                                                            <option value="<?= $i ?>" <?= $i == $guests ? 'selected' : '' ?>>
                                                                <?= $i ?> tamu
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <div class="form-text">Maksimal 2 tamu per kamar</div>
                                                    <div class="invalid-feedback" id="guest-validation-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Price Calculation Display -->
                                        <div class="price-calculation mt-4 p-3 bg-light rounded">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <div class="h5 text-primary mb-0" id="jumlahMalam">
                                                            <?= $nights > 0 ? $nights . ' malam' : '-' ?>
                                                        </div>
                                                        <small class="text-muted">Lama Menginap</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <div class="h5 text-primary mb-0">
                                                            <?= formatRupiah($hotel['harga_per_malam']) ?>
                                                        </div>
                                                        <small class="text-muted">Per Malam</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <div class="h4 text-success mb-0" id="totalHarga">
                                                            <?= $total_price > 0 ? formatRupiah($total_price) : '-' ?>
                                                        </div>
                                                        <small class="text-muted">Total Harga</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" id="hargaPerMalam" value="<?= $hotel['harga_per_malam'] ?>">
                                        <input type="hidden" id="totalHargaHidden" name="total_harga" value="<?= $total_price ?>">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitButton">
                                        <i class="fas fa-arrow-right"></i> Lanjut ke Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Hotel Summary -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 100px;">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Detail Hotel</h5>
                        </div>
                        
                        <div class="card-body">
                            <div class="hotel-summary">
                                <img src="<?= $hotel['foto'] ?: 'https://via.placeholder.com/300x200?text=Hotel+Image' ?>" 
                                     class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($hotel['nama_hotel']) ?>">
                                
                                <h5 class="hotel-name"><?= htmlspecialchars($hotel['nama_hotel']) ?></h5>
                                
                                <p class="hotel-location text-muted mb-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($hotel['lokasi']) ?>
                                </p>
                                
                                <div class="hotel-features">
                                    <div class="feature-item mb-2">
                                        <i class="fas fa-bed text-primary"></i>
                                        <span>Kamar tersedia: <?= $hotel['stok_kamar'] ?></span>
                                    </div>
                                    <div class="feature-item mb-2">
                                        <i class="fas fa-wifi text-primary"></i>
                                        <span>WiFi Gratis</span>
                                    </div>
                                    <div class="feature-item mb-2">
                                        <i class="fas fa-car text-primary"></i>
                                        <span>Parkir Gratis</span>
                                    </div>
                                    <div class="feature-item mb-2">
                                        <i class="fas fa-utensils text-primary"></i>
                                        <span>Restaurant</span>
                                    </div>
                                </div>
                                
                                <?php if ($hotel['stok_kamar'] <= 3): ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <small>Hanya tersisa <?= $hotel['stok_kamar'] ?> kamar!</small>
                                    </div>
                                <?php endif; ?>
                            </div>
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
        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggal_checkin').min = today;
        document.getElementById('tanggal_checkout').min = today;
        
        // Add event listeners
        document.getElementById('jumlah_kamar').addEventListener('change', function() {
            updateGuestOptions();
            validateGuestCapacity();
            calculateTotalPrice();
        });
        
        document.getElementById('jumlah_orang').addEventListener('change', function() {
            validateGuestCapacity();
        });
        
        document.getElementById('tanggal_checkin').addEventListener('change', calculateTotalPrice);
        document.getElementById('tanggal_checkout').addEventListener('change', calculateTotalPrice);
        
        // Update guest options based on room count
        function updateGuestOptions() {
            const roomCount = parseInt(document.getElementById('jumlah_kamar').value);
            const guestSelect = document.getElementById('jumlah_orang');
            const currentGuests = parseInt(guestSelect.value);
            const maxGuests = roomCount * 2; // 2 guests per room
            
            // Clear existing options
            guestSelect.innerHTML = '';
            
            // Add new options up to maximum capacity
            for (let i = 1; i <= maxGuests; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i + ' tamu';
                
                // Keep current selection if still valid
                if (i === currentGuests && currentGuests <= maxGuests) {
                    option.selected = true;
                }
                
                guestSelect.appendChild(option);
            }
            
            // If current guests exceed new capacity, select maximum
            if (currentGuests > maxGuests) {
                guestSelect.value = maxGuests;
            }
        }
        
        // Validate guest capacity
        function validateGuestCapacity() {
            const roomCount = parseInt(document.getElementById('jumlah_kamar').value);
            const guestCount = parseInt(document.getElementById('jumlah_orang').value);
            const maxGuests = roomCount * 2;
            const errorDiv = document.getElementById('guest-validation-error');
            const submitButton = document.getElementById('submitButton');
            
            if (guestCount > maxGuests) {
                errorDiv.textContent = `Jumlah tamu melebihi kapasitas. Maksimal ${maxGuests} tamu untuk ${roomCount} kamar.`;
                errorDiv.style.display = 'block';
                document.getElementById('jumlah_orang').classList.add('is-invalid');
                submitButton.disabled = true;
                return false;
            } else {
                errorDiv.style.display = 'none';
                document.getElementById('jumlah_orang').classList.remove('is-invalid');
                submitButton.disabled = false;
                return true;
            }
        }
        
        // Calculate total price
        function calculateTotalPrice() {
            const checkin = document.getElementById('tanggal_checkin').value;
            const checkout = document.getElementById('tanggal_checkout').value;
            const rooms = parseInt(document.getElementById('jumlah_kamar').value);
            const pricePerNight = parseInt(document.getElementById('hargaPerMalam').value);
            
            if (checkin && checkout) {
                const checkinDate = new Date(checkin);
                const checkoutDate = new Date(checkout);
                const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
                
                if (nights > 0) {
                    const total = nights * rooms * pricePerNight;
                    document.getElementById('jumlahMalam').textContent = nights + ' malam';
                    document.getElementById('totalHarga').textContent = formatRupiah(total);
                    document.getElementById('totalHargaHidden').value = total;
                    
                    // Update checkout minimum date
                    const nextDay = new Date(checkinDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    document.getElementById('tanggal_checkout').min = nextDay.toISOString().split('T')[0];
                } else {
                    document.getElementById('jumlahMalam').textContent = '-';
                    document.getElementById('totalHarga').textContent = '-';
                    document.getElementById('totalHargaHidden').value = 0;
                }
            }
        }
        
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
        
        // Form validation before submit
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!validateGuestCapacity()) {
                e.preventDefault();
                return false;
            }
        });
        
        // Initialize on page load
        updateGuestOptions();
        validateGuestCapacity();
        calculateTotalPrice();
    </script>
</body>
</html>