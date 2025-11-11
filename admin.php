<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Auto-update booking status
updateBookingStatusByDate();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM pengguna WHERE hak_akses = 'user'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_hotels FROM hotel");
$total_hotels = $stmt->fetch()['total_hotels'];

$stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM pemesanan");
$total_bookings = $stmt->fetch()['total_bookings'];

$stmt = $pdo->query("SELECT SUM(total_harga) as total_revenue FROM pemesanan WHERE status IN ('berhasil', 'selesai')");
$total_revenue = $stmt->fetch()['total_revenue'] ?: 0;

// Get low stock hotels
$stmt = $pdo->query("SELECT * FROM hotel WHERE stok_kamar <= 3 ORDER BY stok_kamar ASC");
$low_stock_hotels = $stmt->fetchAll();

// Get recent bookings
$stmt = $pdo->query("SELECT p.*, h.nama_hotel, u.nama_depan, u.nama_belakang 
                    FROM pemesanan p 
                    JOIN hotel h ON p.id_hotel = h.id_hotel 
                    JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
                    ORDER BY p.tanggal_pemesanan DESC 
                    LIMIT 10");
$recent_bookings = $stmt->fetchAll();

// Get all users
$all_users = getAllUsers();

// Handle AJAX request for filtered revenue
if (isset($_GET['ajax']) && $_GET['ajax'] === 'filtered_revenue') {
    $period = $_GET['period'] ?? 'daily';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    $revenue_data = [];
    
    if ($period === 'daily' && $start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT DATE(p.tanggal_pemesanan) as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                FROM pemesanan p 
                WHERE p.status IN ('berhasil', 'selesai') AND DATE(p.tanggal_pemesanan) BETWEEN ? AND ?
                GROUP BY DATE(p.tanggal_pemesanan) 
                ORDER BY periode DESC");
        $stmt->execute([$start_date, $end_date]);
        $revenue_data = $stmt->fetchAll();
    } elseif ($period === 'monthly' && $start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(p.tanggal_pemesanan, '%Y-%m') as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                FROM pemesanan p 
                WHERE p.status IN ('berhasil', 'selesai') AND DATE_FORMAT(p.tanggal_pemesanan, '%Y-%m') BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(p.tanggal_pemesanan, '%Y-%m') 
                ORDER BY periode DESC");
        $stmt->execute([$start_date, $end_date]);
        $revenue_data = $stmt->fetchAll();
    } elseif ($period === 'yearly' && $start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(p.tanggal_pemesanan, '%Y') as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                FROM pemesanan p 
                WHERE p.status IN ('berhasil', 'selesai') AND DATE_FORMAT(p.tanggal_pemesanan, '%Y') BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(p.tanggal_pemesanan, '%Y') 
                ORDER BY periode DESC");
        $stmt->execute([$start_date, $end_date]);
        $revenue_data = $stmt->fetchAll();
    }
    
    header('Content-Type: application/json');
    echo json_encode($revenue_data);
    exit;
}

// Get revenue reports (default data)
$daily_revenue = getRevenueReport('daily');
$monthly_revenue = getRevenueReport('monthly');

// Get yearly revenue
$stmt = $pdo->query("SELECT DATE_FORMAT(p.tanggal_pemesanan, '%Y') as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                    FROM pemesanan p 
                    WHERE p.status IN ('berhasil', 'selesai') AND p.tanggal_pemesanan >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
                    GROUP BY DATE_FORMAT(p.tanggal_pemesanan, '%Y') 
                    ORDER BY periode DESC");
$yearly_revenue = $stmt->fetchAll();

// Handle AJAX request for daily bookings
if (isset($_GET['ajax']) && $_GET['ajax'] === 'daily_bookings' && isset($_GET['date'])) {
    $date = $_GET['date'];
    
    $stmt = $pdo->prepare("SELECT p.*, h.nama_hotel, u.nama_depan, u.nama_belakang 
                          FROM pemesanan p 
                          JOIN hotel h ON p.id_hotel = h.id_hotel 
                          JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
                          WHERE DATE(p.tanggal_pemesanan) = ? 
                          ORDER BY p.tanggal_pemesanan DESC");
    $stmt->execute([$date]);
    $bookings = $stmt->fetchAll();
    
    // Get revenue for this date
    $stmt = $pdo->prepare("SELECT SUM(total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                          FROM pemesanan 
                          WHERE status IN ('berhasil', 'selesai') 
                          AND DATE(tanggal_pemesanan) = ?");
    $stmt->execute([$date]);
    $revenue = $stmt->fetch();
    
    $response = [
        'bookings' => $bookings,
        'revenue' => $revenue
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle form submissions
$message = '';
if ($_POST) {
    if (isset($_POST['add_hotel'])) {
        $nama_hotel = sanitizeInput($_POST['nama_hotel']);
        $lokasi = sanitizeInput($_POST['lokasi']);
        $harga_per_malam = (int)$_POST['harga_per_malam'];
        $deskripsi = sanitizeInput($_POST['deskripsi']);
        $stok_kamar = (int)$_POST['stok_kamar'];
        
        // Handle photo upload or URL
        $foto = '';
        if (!empty($_FILES['foto_upload']['name'])) {
            $foto = handleHotelPhotoUpload($_FILES['foto_upload']);
            if (!$foto) {
                $message = 'Error uploading photo. Please try again.';
            }
        } elseif (!empty($_POST['foto_url'])) {
            $foto = sanitizeInput($_POST['foto_url']);
        }
        
        if (empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO hotel (nama_hotel, lokasi, harga_per_malam, deskripsi, stok_kamar, foto) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$nama_hotel, $lokasi, $harga_per_malam, $deskripsi, $stok_kamar, $foto])) {
                $message = 'Hotel berhasil ditambahkan';
            }
        }
    } elseif (isset($_POST['edit_hotel'])) {
        $hotel_id = (int)$_POST['hotel_id'];
        $nama_hotel = sanitizeInput($_POST['nama_hotel']);
        $lokasi = sanitizeInput($_POST['lokasi']);
        $harga_per_malam = (int)$_POST['harga_per_malam'];
        $deskripsi = sanitizeInput($_POST['deskripsi']);
        $stok_kamar = (int)$_POST['stok_kamar'];
        
        // Get current hotel data
        $current_hotel = getHotelById($hotel_id);
        $foto = $current_hotel['foto']; // Keep current photo as default
        
        // Handle photo upload or URL
        if (!empty($_FILES['foto_upload']['name'])) {
            $new_foto = handleHotelPhotoUpload($_FILES['foto_upload']);
            if ($new_foto) {
                $foto = $new_foto;
            }
        } elseif (!empty($_POST['foto_url']) && $_POST['foto_url'] !== $current_hotel['foto']) {
            $foto = sanitizeInput($_POST['foto_url']);
        }
        
        $stmt = $pdo->prepare("UPDATE hotel SET nama_hotel = ?, lokasi = ?, harga_per_malam = ?, deskripsi = ?, stok_kamar = ?, foto = ? WHERE id_hotel = ?");
        if ($stmt->execute([$nama_hotel, $lokasi, $harga_per_malam, $deskripsi, $stok_kamar, $foto, $hotel_id])) {
            $message = 'Hotel berhasil diperbarui';
        }
    } elseif (isset($_POST['delete_hotel'])) {
        $hotel_id = (int)$_POST['hotel_id'];
        $stmt = $pdo->prepare("DELETE FROM hotel WHERE id_hotel = ?");
        if ($stmt->execute([$hotel_id])) {
            $message = 'Hotel berhasil dihapus';
        }
    } elseif (isset($_POST['edit_user'])) {
        $user_id = (int)$_POST['user_id'];
        $nama_depan = sanitizeInput($_POST['nama_depan']);
        $nama_belakang = sanitizeInput($_POST['nama_belakang']);
        $email = sanitizeInput($_POST['email']);
        $hak_akses = sanitizeInput($_POST['hak_akses']);
        $password_baru = $_POST['password_baru'];
        
        // Check if email already exists (except current user)
        $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE email = ? AND id_pengguna != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $message = 'Email sudah digunakan oleh user lain';
        } else {
            if (!empty($password_baru)) {
                // Update with new password
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE pengguna SET nama_depan = ?, nama_belakang = ?, email = ?, hak_akses = ?, password = ? WHERE id_pengguna = ?");
                $result = $stmt->execute([$nama_depan, $nama_belakang, $email, $hak_akses, $hashed_password, $user_id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE pengguna SET nama_depan = ?, nama_belakang = ?, email = ?, hak_akses = ? WHERE id_pengguna = ?");
                $result = $stmt->execute([$nama_depan, $nama_belakang, $email, $hak_akses, $user_id]);
            }
            
            if ($result) {
                $message = 'User berhasil diperbarui';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        if ($user_id != $_SESSION['user_id']) { // Don't allow deleting self
            $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
            if ($stmt->execute([$user_id])) {
                $message = 'User berhasil dihapus';
            }
        }
    } elseif (isset($_POST['update_stock'])) {
        $hotel_id = (int)$_POST['hotel_id'];
        $stok_kamar = (int)$_POST['stok_kamar'];
        
        $stmt = $pdo->prepare("UPDATE hotel SET stok_kamar = ? WHERE id_hotel = ?");
        if ($stmt->execute([$stok_kamar, $hotel_id])) {
            $message = 'Stok kamar berhasil diperbarui';
        }
    }
}

// Get all hotels for management
$all_hotels = getAllHotels();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HotelAurora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .filter-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn-filter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-reset:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .date-picker-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .date-picker-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .date-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .date-input-group:hover {
            border-color: #007bff;
        }
        
        .date-input-group i {
            color: #007bff;
            font-size: 1.2rem;
        }
        
        .date-input {
            border: none;
            background: transparent;
            font-size: 1rem;
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .date-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        .revenue-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .revenue-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .revenue-card.info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .revenue-card h4 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .revenue-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        .booking-details {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            animation: slideDown 0.3s ease;
        }
        
        .no-bookings {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            color: #856404;
            text-align: center;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-spinner {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .clear-date-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .clear-date-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .date-clickable {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
        }
        .date-clickable:hover {
            color: #0056b3;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .date-picker-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .revenue-summary {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .revenue-card h4 {
                font-size: 1.5rem;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
        }
    </style>
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
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield"></i> Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#hotels">Kelola Hotel</a></li>
                                <li><a class="dropdown-item" href="#users">Kelola User</a></li>
                                <li><a class="dropdown-item" href="#bookings">Kelola Booking</a></li>
                                <li><a class="dropdown-item" href="#revenue">Laporan Pendapatan</a></li>
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

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3">
                        Dashboard Admin
                    </h1>
                    <p class="lead mb-0">
                        Kelola hotel, pemesanan, dan pengguna dengan mudah
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield display-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-4">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <span class="stat-number" id="totalUsers"><?= $total_users ?></span>
                    <div class="stat-label">Total User</div>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number" id="totalHotels"><?= $total_hotels ?></span>
                    <div class="stat-label">Total Hotel</div>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number" id="totalBookings"><?= $total_bookings ?></span>
                    <div class="stat-label">Total Booking</div>
                </div>
                
                <div class="stat-card">
                    <span class="stat-number" id="totalRevenue"><?= formatRupiah($total_revenue) ?></span>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Low Stock Alert -->
        <?php if (!empty($low_stock_hotels)): ?>
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Peringatan Stok Kamar</h5>
                <p>Hotel berikut memiliki stok kamar yang hampir habis:</p>
                <ul class="mb-0">
                    <?php foreach ($low_stock_hotels as $hotel): ?>
                        <li>
                            <strong><?= htmlspecialchars($hotel['nama_hotel']) ?></strong> 
                            - Sisa <?= $hotel['stok_kamar'] ?> kamar
                            <button class="btn btn-sm btn-warning ms-2" 
                                    onclick="updateStock(<?= $hotel['id_hotel'] ?>, '<?= htmlspecialchars($hotel['nama_hotel']) ?>', <?= $hotel['stok_kamar'] ?>)">
                                Update Stok
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Revenue Report -->
        <div class="card mb-5" id="revenue">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-chart-line"></i> Laporan Pendapatan</h4>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="showRevenueReport('daily')" id="btnDaily">Harian</button>
                    <button class="btn btn-outline-primary" onclick="showRevenueReport('monthly')" id="btnMonthly">Bulanan</button>
                    <button class="btn btn-outline-primary" onclick="showRevenueReport('yearly')" id="btnYearly">Tahunan</button>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Daily Revenue -->
                <div id="dailyRevenue">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Laporan Pendapatan Harian</h5>
                        <button class="btn btn-success" onclick="exportRevenue('daily')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                    
                    <!-- Filter Harian -->
                    <div class="filter-container">
                        <h6 class="mb-3"><i class="fas fa-filter"></i> Filter Periode Harian</h6>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Dari Tanggal</label>
                                <input type="date" id="dailyStartDate" class="form-control">
                            </div>
                            <div class="filter-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" id="dailyEndDate" class="form-control">
                            </div>
                            <div class="filter-actions">
                                <button class="btn-filter" onclick="applyDailyFilter()">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                                <button class="btn-reset" onclick="resetDailyFilter()">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="date-picker-container">
                        <div class="date-picker-header">
                            <div class="date-input-group">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" class="date-input" id="selectedDate" placeholder="Pilih tanggal...">
                            </div>
                            <button class="clear-date-btn" onclick="clearSelectedDate()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <div class="ms-auto">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Pilih tanggal untuk melihat detail pendapatan dan booking
                                </small>
                            </div>
                        </div>
                        
                        <!-- Revenue Summary (hidden by default) -->
                        <div id="revenueSummary" style="display: none;">
                            <div class="revenue-summary">
                                <div class="revenue-card success">
                                    <h4 id="totalPendapatan">Rp 0</h4>
                                    <p>Total Pendapatan</p>
                                </div>
                                <div class="revenue-card info">
                                    <h4 id="jumlahTransaksi">0</h4>
                                    <p>Total Transaksi</p>
                                </div>
                                <div class="revenue-card">
                                    <h4 id="rataRata">Rp 0</h4>
                                    <p>Rata-rata per Transaksi</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking Details Container -->
                        <div id="bookingDetailsContainer">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                                <h5>Pilih Tanggal</h5>
                                <p>Pilih tanggal di atas untuk melihat detail pendapatan dan booking</p>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-muted"><i class="fas fa-info-circle"></i> Klik pada tanggal untuk melihat detail booking pada hari tersebut</p>
                    <div class="table-responsive">
                        <table class="table table-hover" id="dailyRevenueTable">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah Transaksi</th>
                                    <th>Total Pendapatan</th>
                                    <th>Rata-rata per Transaksi</th>
                                </tr>
                            </thead>
                            <tbody id="dailyRevenueBody">
                                <?php foreach ($daily_revenue as $revenue): 
                                    $avg_per_transaction = $revenue['jumlah_transaksi'] > 0 ? $revenue['total_pendapatan'] / $revenue['jumlah_transaksi'] : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <strong class="date-clickable" onclick="showDailyBookings('<?= $revenue['periode'] ?>')"><?= formatDate($revenue['periode']) ?></strong>
                                        </td>
                                        <td><span class="badge bg-info"><?= $revenue['jumlah_transaksi'] ?> transaksi</span></td>
                                        <td><strong class="text-success"><?= formatRupiah($revenue['total_pendapatan']) ?></strong></td>
                                        <td><?= formatRupiah($avg_per_transaction) ?></td>
                                    </tr>
                                    <tr id="bookings-<?= $revenue['periode'] ?>" style="display: none;">
                                        <td colspan="4">
                                            <div id="booking-details-<?= $revenue['periode'] ?>">
                                                <!-- Booking details will be loaded here -->
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Revenue -->
                <div id="monthlyRevenue" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Laporan Pendapatan Bulanan</h5>
                        <button class="btn btn-success" onclick="exportRevenue('monthly')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                    
                    <!-- Filter Bulanan -->
                    <div class="filter-container">
                        <h6 class="mb-3"><i class="fas fa-filter"></i> Filter Periode Bulanan</h6>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Dari Bulan</label>
                                <input type="month" id="monthlyStartDate" class="form-control">
                            </div>
                            <div class="filter-group">
                                <label>Sampai Bulan</label>
                                <input type="month" id="monthlyEndDate" class="form-control">
                            </div>
                            <div class="filter-actions">
                                <button class="btn-filter" onclick="applyMonthlyFilter()">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                                <button class="btn-reset" onclick="resetMonthlyFilter()">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="monthlyRevenueTable">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Jumlah Transaksi</th>
                                    <th>Total Pendapatan</th>
                                    <th>Rata-rata per Transaksi</th>
                                </tr>
                            </thead>
                            <tbody id="monthlyRevenueBody">
                                <?php 
                                $total_revenue_all = 0;
                                $total_transactions_all = 0;
                                foreach ($monthly_revenue as $revenue): 
                                    $total_revenue_all += $revenue['total_pendapatan'];
                                    $total_transactions_all += $revenue['jumlah_transaksi'];
                                    $avg_per_transaction = $revenue['jumlah_transaksi'] > 0 ? $revenue['total_pendapatan'] / $revenue['jumlah_transaksi'] : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= date('F Y', strtotime($revenue['periode'] . '-01')) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $revenue['jumlah_transaksi'] ?> transaksi</span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?= formatRupiah($revenue['total_pendapatan']) ?></strong>
                                        </td>
                                        <td>
                                            <?= formatRupiah($avg_per_transaction) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th><strong>TOTAL KESELURUHAN</strong></th>
                                    <th><strong><?= $total_transactions_all ?> transaksi</strong></th>
                                    <th><strong><?= formatRupiah($total_revenue_all) ?></strong></th>
                                    <th><strong><?= $total_transactions_all > 0 ? formatRupiah($total_revenue_all / $total_transactions_all) : formatRupiah(0) ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Yearly Revenue -->
                <div id="yearlyRevenue" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Laporan Pendapatan Tahunan</h5>
                        <button class="btn btn-success" onclick="exportRevenue('yearly')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                    
                    <!-- Filter Tahunan -->
                    <div class="filter-container">
                        <h6 class="mb-3"><i class="fas fa-filter"></i> Filter Periode Tahunan</h6>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Dari Tahun</label>
                                <select id="yearlyStartDate" class="form-control">
                                    <option value="">Pilih Tahun</option>
                                    <?php for($year = 2020; $year <= 2030; $year++): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Sampai Tahun</label>
                                <select id="yearlyEndDate" class="form-control">
                                    <option value="">Pilih Tahun</option>
                                    <?php for($year = 2020; $year <= 2030; $year++): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button class="btn-filter" onclick="applyYearlyFilter()">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                                <button class="btn-reset" onclick="resetYearlyFilter()">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="yearlyRevenueTable">
                            <thead>
                                <tr>
                                    <th>Tahun</th>
                                    <th>Jumlah Transaksi</th>
                                    <th>Total Pendapatan</th>
                                    <th>Rata-rata per Transaksi</th>
                                </tr>
                            </thead>
                            <tbody id="yearlyRevenueBody">
                                <?php 
                                $total_revenue_yearly = 0;
                                $total_transactions_yearly = 0;
                                foreach ($yearly_revenue as $revenue): 
                                    $total_revenue_yearly += $revenue['total_pendapatan'];
                                    $total_transactions_yearly += $revenue['jumlah_transaksi'];
                                    $avg_per_transaction = $revenue['jumlah_transaksi'] > 0 ? $revenue['total_pendapatan'] / $revenue['jumlah_transaksi'] : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= $revenue['periode'] ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $revenue['jumlah_transaksi'] ?> transaksi</span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?= formatRupiah($revenue['total_pendapatan']) ?></strong>
                                        </td>
                                        <td>
                                            <?= formatRupiah($avg_per_transaction) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th><strong>TOTAL KESELURUHAN</strong></th>
                                    <th><strong><?= $total_transactions_yearly ?> transaksi</strong></th>
                                    <th><strong><?= formatRupiah($total_revenue_yearly) ?></strong></th>
                                    <th><strong><?= $total_transactions_yearly > 0 ? formatRupiah($total_revenue_yearly / $total_transactions_yearly) : formatRupiah(0) ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hotel Management -->
        <div class="card mb-5" id="hotels">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-hotel"></i> Kelola Hotel</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHotelModal">
                    <i class="fas fa-plus"></i> Tambah Hotel
                </button>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Hotel</th>
                                <th>Lokasi</th>
                                <th>Harga/Malam</th>
                                <th>Stok Kamar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_hotels as $hotel): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($hotel['nama_hotel']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($hotel['lokasi']) ?></td>
                                    <td><?= formatRupiah($hotel['harga_per_malam']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $hotel['stok_kamar'] <= 3 ? 'warning' : 'success' ?>">
                                            <?= $hotel['stok_kamar'] ?> kamar
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($hotel['stok_kamar'] > 0): ?>
                                            <span class="badge bg-success">Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="updateStock(<?= $hotel['id_hotel'] ?>, '<?= htmlspecialchars($hotel['nama_hotel']) ?>', <?= $hotel['stok_kamar'] ?>)">
                                                <i class="fas fa-boxes"></i> Stok
                                            </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editHotel(<?= htmlspecialchars(json_encode($hotel)) ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteHotel(<?= $hotel['id_hotel'] ?>, '<?= htmlspecialchars($hotel['nama_hotel']) ?>')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Management -->
        <div class="card mb-5" id="users">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-users"></i> Kelola User</h4>
                <div>
                    <span class="badge bg-info me-2">Total: <?= count($all_users) ?> users</span>
                </div>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Hak Akses</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <tr>
                                    <td><?= $user['id_pengguna'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['nama_depan'] . ' ' . $user['nama_belakang']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $user['hak_akses'] === 'admin' ? 'danger' : 'primary' ?>">
                                            <i class="fas fa-<?= $user['hak_akses'] === 'admin' ? 'user-shield' : 'user' ?>"></i>
                                            <?= ucfirst($user['hak_akses']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($user['tanggal_daftar']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($user['id_pengguna'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteUser(<?= $user['id_pengguna'] ?>, '<?= htmlspecialchars($user['nama_depan'] . ' ' . $user['nama_belakang']) ?>')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fas fa-shield-alt"></i> Anda
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card mb-5" id="bookings">
            <div class="card-header">
                <h4><i class="fas fa-calendar-check"></i> Pemesanan Terbaru</h4>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode Booking</th>
                                <th>Tamu</th>
                                <th>Hotel</th>
                                <th>Check-in</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['kode_booking']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($booking['nama_depan'] . ' ' . $booking['nama_belakang']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($booking['nama_hotel']) ?></td>
                                    <td><?= formatDate($booking['tanggal_checkin']) ?></td>
                                    <td><?= formatRupiah($booking['total_harga']) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = $booking['status'] === 'berhasil' ? 'success' : 
                                                      ($booking['status'] === 'selesai' ? 'info' :
                                                      ($booking['status'] === 'pending' ? 'warning' : 'danger'));
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Hotel Modal -->
    <div class="modal fade" id="addHotelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Hotel Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Hotel *</label>
                                    <input type="text" class="form-control" name="nama_hotel" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Lokasi *</label>
                                    <input type="text" class="form-control" name="lokasi" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Harga per Malam *</label>
                                    <input type="number" class="form-control" name="harga_per_malam" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Stok Kamar *</label>
                                    <input type="number" class="form-control" name="stok_kamar" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Foto Hotel</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small">Upload File</label>
                                    <input type="file" class="form-control" name="foto_upload" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Atau URL Foto</label>
                                    <input type="url" class="form-control" name="foto_url" placeholder="/images/photo1762656082.jpg">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="deskripsi" rows="4" required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_hotel" class="btn btn-primary">Tambah Hotel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Hotel Modal -->
    <div class="modal fade" id="editHotelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Hotel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="hotel_id" id="editHotelId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Hotel *</label>
                                    <input type="text" class="form-control" name="nama_hotel" id="editNamaHotel" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Lokasi *</label>
                                    <input type="text" class="form-control" name="lokasi" id="editLokasi" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Harga per Malam *</label>
                                    <input type="number" class="form-control" name="harga_per_malam" id="editHarga" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Stok Kamar *</label>
                                    <input type="number" class="form-control" name="stok_kamar" id="editStok" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Foto Hotel</label>
                            <div class="mb-2">
                                <small class="text-muted">Foto saat ini: <span id="currentPhotoText"></span></small>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small">Upload File Baru</label>
                                    <input type="file" class="form-control" name="foto_upload" accept="image/*">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah foto</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Atau Ganti dengan URL</label>
                                    <input type="url" class="form-control" name="foto_url" id="editFoto" placeholder="/images/photo1762656082.jpg">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah foto</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Deskripsi *</label>
                            <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="4" required></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_hotel" class="btn btn-primary">Update Hotel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stok Kamar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="hotel_id" id="stockHotelId">
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Nama Hotel</label>
                            <input type="text" class="form-control" id="stockHotelName" readonly>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Stok Kamar Saat Ini</label>
                            <input type="number" class="form-control" id="currentStock" readonly>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Stok Kamar Baru *</label>
                            <input type="number" class="form-control" name="stok_kamar" id="newStock" required min="0">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_stock" class="btn btn-info">Update Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUserId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Depan *</label>
                                    <input type="text" class="form-control" name="nama_depan" id="editNamaDepan" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Belakang *</label>
                                    <input type="text" class="form-control" name="nama_belakang" id="editNamaBelakang" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Hak Akses *</label>
                            <select class="form-select" name="hak_akses" id="editHakAkses" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="password_baru" id="editPassword">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p id="deleteMessage">Apakah Anda yakin ingin menghapus item ini?</p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" id="deleteId" name="">
                        <button type="submit" id="deleteSubmit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
    
    <script>
        // Show revenue report function
        function showRevenueReport(period) {
            // Hide all reports
            document.getElementById('dailyRevenue').style.display = 'none';
            document.getElementById('monthlyRevenue').style.display = 'none';
            document.getElementById('yearlyRevenue').style.display = 'none';
            
            // Remove active class from all buttons
            document.getElementById('btnDaily').classList.remove('btn-primary');
            document.getElementById('btnDaily').classList.add('btn-outline-primary');
            document.getElementById('btnMonthly').classList.remove('btn-primary');
            document.getElementById('btnMonthly').classList.add('btn-outline-primary');
            document.getElementById('btnYearly').classList.remove('btn-primary');
            document.getElementById('btnYearly').classList.add('btn-outline-primary');
            
            // Show selected report and activate button
            if (period === 'daily') {
                document.getElementById('dailyRevenue').style.display = 'block';
                document.getElementById('btnDaily').classList.remove('btn-outline-primary');
                document.getElementById('btnDaily').classList.add('btn-primary');
            } else if (period === 'monthly') {
                document.getElementById('monthlyRevenue').style.display = 'block';
                document.getElementById('btnMonthly').classList.remove('btn-outline-primary');
                document.getElementById('btnMonthly').classList.add('btn-primary');
            } else {
                document.getElementById('yearlyRevenue').style.display = 'block';
                document.getElementById('btnYearly').classList.remove('btn-outline-primary');
                document.getElementById('btnYearly').classList.add('btn-primary');
            }
        }
        
        // Filter functions
        function applyDailyFilter() {
            const startDate = document.getElementById('dailyStartDate').value;
            const endDate = document.getElementById('dailyEndDate').value;
            
            if (!startDate || !endDate) {
                alert('Silakan pilih tanggal mulai dan tanggal akhir');
                return;
            }
            
            if (startDate > endDate) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
                return;
            }
            
            fetchFilteredRevenue('daily', startDate, endDate);
        }
        
        function applyMonthlyFilter() {
            const startDate = document.getElementById('monthlyStartDate').value;
            const endDate = document.getElementById('monthlyEndDate').value;
            
            if (!startDate || !endDate) {
                alert('Silakan pilih bulan mulai dan bulan akhir');
                return;
            }
            
            if (startDate > endDate) {
                alert('Bulan mulai tidak boleh lebih besar dari bulan akhir');
                return;
            }
            
            fetchFilteredRevenue('monthly', startDate, endDate);
        }
        
        function applyYearlyFilter() {
            const startDate = document.getElementById('yearlyStartDate').value;
            const endDate = document.getElementById('yearlyEndDate').value;
            
            if (!startDate || !endDate) {
                alert('Silakan pilih tahun mulai dan tahun akhir');
                return;
            }
            
            if (parseInt(startDate) > parseInt(endDate)) {
                alert('Tahun mulai tidak boleh lebih besar dari tahun akhir');
                return;
            }
            
            fetchFilteredRevenue('yearly', startDate, endDate);
        }
        
        function fetchFilteredRevenue(period, startDate, endDate) {
            const tableBody = document.getElementById(period + 'RevenueBody');
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>';
            
            fetch(`admin.php?ajax=filtered_revenue&period=${period}&start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data untuk periode yang dipilih</td></tr>';
                        return;
                    }
                    
                    let html = '';
                    let totalRevenue = 0;
                    let totalTransactions = 0;
                    
                    data.forEach(revenue => {
                        totalRevenue += parseFloat(revenue.total_pendapatan);
                        totalTransactions += parseInt(revenue.jumlah_transaksi);
                        const avgPerTransaction = revenue.jumlah_transaksi > 0 ? revenue.total_pendapatan / revenue.jumlah_transaksi : 0;
                        
                        let periodeDisplay = revenue.periode;
                        if (period === 'daily') {
                            periodeDisplay = formatDate(revenue.periode);
                        } else if (period === 'monthly') {
                            const date = new Date(revenue.periode + '-01');
                            periodeDisplay = date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
                        }
                        
                        html += `
                            <tr>
                                <td><strong>${periodeDisplay}</strong></td>
                                <td><span class="badge bg-info">${revenue.jumlah_transaksi} transaksi</span></td>
                                <td><strong class="text-success">${formatRupiah(revenue.total_pendapatan)}</strong></td>
                                <td>${formatRupiah(avgPerTransaction)}</td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = html;
                    
                    // Update footer
                    const avgTotal = totalTransactions > 0 ? totalRevenue / totalTransactions : 0;
                    const table = document.getElementById(period + 'RevenueTable');
                    let tfoot = table.querySelector('tfoot');
                    if (tfoot) {
                        tfoot.innerHTML = `
                            <tr class="table-dark">
                                <th><strong>TOTAL KESELURUHAN</strong></th>
                                <th><strong>${totalTransactions} transaksi</strong></th>
                                <th><strong>${formatRupiah(totalRevenue)}</strong></th>
                                <th><strong>${formatRupiah(avgTotal)}</strong></th>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Terjadi kesalahan saat memuat data</td></tr>';
                });
        }
        
        function resetDailyFilter() {
            document.getElementById('dailyStartDate').value = '';
            document.getElementById('dailyEndDate').value = '';
            location.reload();
        }
        
        function resetMonthlyFilter() {
            document.getElementById('monthlyStartDate').value = '';
            document.getElementById('monthlyEndDate').value = '';
            location.reload();
        }
        
        function resetYearlyFilter() {
            document.getElementById('yearlyStartDate').value = '';
            document.getElementById('yearlyEndDate').value = '';
            location.reload();
        }
        
        // Date picker functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('selectedDate');
            const revenueSummary = document.getElementById('revenueSummary');
            const bookingContainer = document.getElementById('bookingDetailsContainer');
            
            dateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (selectedDate) {
                    loadDailyData(selectedDate);
                } else {
                    clearData();
                }
            });
        });
        
        function loadDailyData(date) {
            const container = document.getElementById('bookingDetailsContainer');
            const revenueSummary = document.getElementById('revenueSummary');
            
            // Show loading
            container.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Memuat data untuk tanggal ${formatDateIndonesian(date)}...</p>
                </div>
            `;
            
            // Fetch data
            fetch(`admin.php?ajax=daily_bookings&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    const bookings = data.bookings || [];
                    const revenue = data.revenue || {total_pendapatan: 0, jumlah_transaksi: 0};
                    
                    // Show revenue summary
                    revenueSummary.style.display = 'block';
                    document.getElementById('totalPendapatan').textContent = formatRupiah(revenue.total_pendapatan || 0);
                    document.getElementById('jumlahTransaksi').textContent = revenue.jumlah_transaksi || 0;
                    
                    const avgPerTransaction = revenue.jumlah_transaksi > 0 ? 
                        (revenue.total_pendapatan / revenue.jumlah_transaksi) : 0;
                    document.getElementById('rataRata').textContent = formatRupiah(avgPerTransaction);
                    
                    // Show booking details
                    if (bookings.length === 0) {
                        container.innerHTML = `
                            <div class="no-bookings">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <h5>Tidak ada pesanan pada tanggal ${formatDateIndonesian(date)}</h5>
                                <p class="mb-0">Belum ada pemesanan hotel yang masuk pada hari ini.</p>
                            </div>
                        `;
                    } else {
                        let bookingHtml = `
                            <div class="booking-details">
                                <h5><i class="fas fa-calendar-day"></i> Detail Booking Tanggal ${formatDateIndonesian(date)}</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Kode Booking</th>
                                                <th>Tamu</th>
                                                <th>Hotel</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Kamar</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;
                        
                        let totalRevenue = 0;
                        bookings.forEach(booking => {
                            const statusClass = booking.status === 'berhasil' ? 'success' : 
                                              (booking.status === 'selesai' ? 'info' :
                                              (booking.status === 'pending' ? 'warning' : 'danger'));
                            
                            if (booking.status === 'berhasil' || booking.status === 'selesai') {
                                totalRevenue += parseInt(booking.total_harga);
                            }
                            
                            bookingHtml += `
                                <tr>
                                    <td><strong>${booking.kode_booking}</strong></td>
                                    <td>${booking.nama_depan} ${booking.nama_belakang}</td>
                                    <td>${booking.nama_hotel}</td>
                                    <td>${formatDateIndonesian(booking.tanggal_checkin)}</td>
                                    <td>${formatDateIndonesian(booking.tanggal_checkout)}</td>
                                    <td>${booking.jumlah_kamar} kamar, ${booking.jumlah_orang} orang</td>
                                    <td><strong>${formatRupiah(booking.total_harga)}</strong></td>
                                    <td><span class="badge bg-${statusClass}">${booking.status}</span></td>
                                </tr>
                            `;
                        });
                        
                        bookingHtml += `
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h6 class="text-primary">Total Booking</h6>
                                            <h4>${bookings.length}</h4>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-success">Total Pendapatan</h6>
                                            <h4>${formatRupiah(totalRevenue)}</h4>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-info">Rata-rata</h6>
                                            <h4>${bookings.length > 0 ? formatRupiah(totalRevenue / bookings.length) : 'Rp 0'}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        container.innerHTML = bookingHtml;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Terjadi kesalahan saat memuat data booking.
                        </div>
                    `;
                });
        }
        
        function clearSelectedDate() {
            document.getElementById('selectedDate').value = '';
            clearData();
        }
        
        function clearData() {
            document.getElementById('revenueSummary').style.display = 'none';
            document.getElementById('bookingDetailsContainer').innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                    <h5>Pilih Tanggal</h5>
                    <p>Pilih tanggal di atas untuk melihat detail pendapatan dan booking</p>
                </div>
            `;
        }
        
        // New function to show daily bookings (for table view)
        function showDailyBookings(date) {
            const bookingRow = document.getElementById('bookings-' + date);
            const detailsDiv = document.getElementById('booking-details-' + date);
            
            // Toggle visibility
            if (bookingRow.style.display === 'none') {
                // Show loading
                detailsDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data booking...</div>';
                bookingRow.style.display = 'table-row';
                
                // Fetch booking data
                fetch('admin.php?ajax=daily_bookings&date=' + date)
                    .then(response => response.json())
                    .then(data => {
                        const bookings = data.bookings || [];
                        if (bookings.length === 0) {
                            detailsDiv.innerHTML = `
                                <div class="no-bookings">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Tidak ada yang pesan hotel pada tanggal ${formatDate(date)}</strong>
                                    <p class="mb-0">Belum ada pemesanan hotel yang masuk pada hari ini.</p>
                                </div>
                            `;
                        } else {
                            let bookingHtml = `
                                <div class="booking-details">
                                    <h6><i class="fas fa-calendar-day"></i> Detail Booking Tanggal ${formatDate(date)}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Kode Booking</th>
                                                    <th>Tamu</th>
                                                    <th>Hotel</th>
                                                    <th>Check-in</th>
                                                    <th>Check-out</th>
                                                    <th>Kamar</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                            `;
                            
                            bookings.forEach(booking => {
                                const statusClass = booking.status === 'berhasil' ? 'success' : 
                                                  (booking.status === 'selesai' ? 'info' :
                                                  (booking.status === 'pending' ? 'warning' : 'danger'));
                                
                                bookingHtml += `
                                    <tr>
                                        <td><strong>${booking.kode_booking}</strong></td>
                                        <td>${booking.nama_depan} ${booking.nama_belakang}</td>
                                        <td>${booking.nama_hotel}</td>
                                        <td>${formatDate(booking.tanggal_checkin)}</td>
                                        <td>${formatDate(booking.tanggal_checkout)}</td>
                                        <td>${booking.jumlah_kamar} kamar, ${booking.jumlah_orang} orang</td>
                                        <td>${formatRupiah(booking.total_harga)}</td>
                                        <td><span class="badge bg-${statusClass}">${booking.status}</span></td>
                                    </tr>
                                `;
                            });
                            
                            bookingHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Total ${bookings.length} booking pada tanggal ini
                                        </small>
                                    </div>
                                </div>
                            `;
                            
                            detailsDiv.innerHTML = bookingHtml;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        detailsDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Terjadi kesalahan saat memuat data booking.
                            </div>
                        `;
                    });
            } else {
                // Hide the row
                bookingRow.style.display = 'none';
            }
        }
        
        // Export revenue function
        function exportRevenue(period) {
            window.open('export_revenue.php?period=' + period, '_blank');
        }
        
        // Helper functions
        function formatDateIndonesian(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric'
            });
        }
        
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }
        
        function editHotel(hotel) {
            document.getElementById('editHotelId').value = hotel.id_hotel;
            document.getElementById('editNamaHotel').value = hotel.nama_hotel;
            document.getElementById('editLokasi').value = hotel.lokasi;
            document.getElementById('editHarga').value = hotel.harga_per_malam;
            document.getElementById('editStok').value = hotel.stok_kamar;
            document.getElementById('editDeskripsi').value = hotel.deskripsi;
            
            // Clear the photo URL field and show current photo info
            document.getElementById('editFoto').value = '';
            const currentPhotoText = document.getElementById('currentPhotoText');
            if (hotel.foto) {
                if (hotel.foto.startsWith('http')) {
                    currentPhotoText.textContent = 'URL Link';
                } else {
                    currentPhotoText.textContent = 'File Upload';
                }
            } else {
                currentPhotoText.textContent = 'Tidak ada foto';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editHotelModal'));
            modal.show();
        }
        
        function updateStock(hotelId, hotelName, currentStock) {
            document.getElementById('stockHotelId').value = hotelId;
            document.getElementById('stockHotelName').value = hotelName;
            document.getElementById('currentStock').value = currentStock;
            document.getElementById('newStock').value = currentStock;
            
            const modal = new bootstrap.Modal(document.getElementById('updateStockModal'));
            modal.show();
        }
        
        function editUser(user) {
            document.getElementById('editUserId').value = user.id_pengguna;
            document.getElementById('editNamaDepan').value = user.nama_depan;
            document.getElementById('editNamaBelakang').value = user.nama_belakang;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editHakAkses').value = user.hak_akses;
            document.getElementById('editPassword').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
        
        function deleteHotel(id, name) {
            document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus hotel "${name}"?`;
            document.getElementById('deleteId').name = 'hotel_id';
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteSubmit').name = 'delete_hotel';
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        function deleteUser(id, name) {
            document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus user "${name}"?`;
            document.getElementById('deleteId').name = 'user_id';
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteSubmit').name = 'delete_user';
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>