<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM pengguna WHERE hak_akses = 'user'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_hotels FROM hotel");
$total_hotels = $stmt->fetch()['total_hotels'];

$stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM pemesanan");
$total_bookings = $stmt->fetch()['total_bookings'];

$stmt = $pdo->query("SELECT SUM(total_harga) as total_revenue FROM pemesanan WHERE status = 'berhasil'");
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

// Get monthly revenue
$monthly_revenue = getRevenueReport('monthly');

// Handle form submissions
$message = '';
if ($_POST) {
    if (isset($_POST['add_hotel'])) {
        $nama_hotel = sanitizeInput($_POST['nama_hotel']);
        $lokasi = sanitizeInput($_POST['lokasi']);
        $harga_per_malam = (int)$_POST['harga_per_malam'];
        $deskripsi = sanitizeInput($_POST['deskripsi']);
        $stok_kamar = (int)$_POST['stok_kamar'];
        $foto = sanitizeInput($_POST['foto']);
        
        $stmt = $pdo->prepare("INSERT INTO hotel (nama_hotel, lokasi, harga_per_malam, deskripsi, stok_kamar, foto) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nama_hotel, $lokasi, $harga_per_malam, $deskripsi, $stok_kamar, $foto])) {
            $message = 'Hotel berhasil ditambahkan';
        }
    } elseif (isset($_POST['edit_hotel'])) {
        $hotel_id = (int)$_POST['hotel_id'];
        $nama_hotel = sanitizeInput($_POST['nama_hotel']);
        $lokasi = sanitizeInput($_POST['lokasi']);
        $harga_per_malam = (int)$_POST['harga_per_malam'];
        $deskripsi = sanitizeInput($_POST['deskripsi']);
        $stok_kamar = (int)$_POST['stok_kamar'];
        $foto = sanitizeInput($_POST['foto']);
        
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
    <title>Admin Dashboard - HotelFuture</title>
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

        <!-- Revenue Report -->
        <div class="card mb-5" id="revenue">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-chart-line"></i> Laporan Pendapatan Bulanan</h4>
                <div class="btn-group">
                    <button class="btn btn-success" onclick="exportRevenue('pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-primary" onclick="exportRevenue('csv')">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    <button class="btn btn-info" onclick="printRevenue()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="revenueTable">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Jumlah Transaksi</th>
                                <th>Total Pendapatan</th>
                                <th>Rata-rata per Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                                      ($booking['status'] === 'pending' ? 'warning' : 'danger');
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
                            <label class="form-label">URL Foto</label>
                            <input type="url" class="form-control" name="foto" placeholder="https://example.com/hotel-image.jpg">
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
                
                <form method="POST">
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
                            <label class="form-label">URL Foto</label>
                            <input type="url" class="form-control" name="foto" id="editFoto" placeholder="https://example.com/hotel-image.jpg">
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
                <p>&copy; 2025 HotelFuture. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    
    <script>
        function editHotel(hotel) {
            document.getElementById('editHotelId').value = hotel.id_hotel;
            document.getElementById('editNamaHotel').value = hotel.nama_hotel;
            document.getElementById('editLokasi').value = hotel.lokasi;
            document.getElementById('editHarga').value = hotel.harga_per_malam;
            document.getElementById('editStok').value = hotel.stok_kamar;
            document.getElementById('editFoto').value = hotel.foto || '';
            document.getElementById('editDeskripsi').value = hotel.deskripsi;
            
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
        
        function exportRevenue(format) {
            window.open(`export_revenue.php?format=${format}&period=monthly`, '_blank');
        }
        
        function printRevenue() {
            const table = document.getElementById('revenueTable').outerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Laporan Pendapatan - HotelFuture</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1a365d; padding-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                        th { background-color: #1a365d; color: white; }
                        .table-dark th { background-color: #343a40; color: white; }
                        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                        .bg-info { background-color: #17a2b8; color: white; }
                        .text-success { color: #28a745; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>🏨 HotelFuture</h1>
                        <h2>Laporan Pendapatan Bulanan</h2>
                        <p>Dicetak pada: ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</p>
                    </div>
                    ${table}
                    <div class="footer">
                        <p>Laporan ini digenerate secara otomatis oleh sistem HotelFuture</p>
                        <p>&copy; 2025 HotelFuture. All rights reserved.</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>