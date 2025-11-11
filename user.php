<?php
require_once 'config.php';
require_once 'functions.php';


if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin.php');
}

$recent_hotels = getAllHotels('', '', 0, 999999999);
$recent_hotels = array_slice($recent_hotels, 0, 6); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - HotelAurora</title>
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
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="user.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="riwayat.php">
                                <i class="fas fa-history"></i> Riwayat Transaksi
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nama_depan']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                    <i class="fas fa-user-edit"></i> Edit Profil
                                </a></li>
                                <li><a class="dropdown-item" href="riwayat.php">
                                    <i class="fas fa-history"></i> Riwayat Transaksi
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

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold mb-3">
                        Selamat Datang, <?= htmlspecialchars($_SESSION['nama_depan']) ?>!
                    </h1>
                    <p class="lead mb-0">
                        Temukan hotel impian Anda dan nikmati pengalaman menginap terbaik
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle display-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-search fa-3x text-primary mb-3"></i>
                            <h5>Cari Hotel</h5>
                            <p class="text-muted">Temukan hotel sesuai kebutuhan</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Mulai Cari
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-history fa-3x text-success mb-3"></i>
                            <h5>Riwayat Booking</h5>
                            <p class="text-muted">Lihat semua pemesanan Anda</p>
                            <a href="riwayat.php" class="btn btn-success">
                                <i class="fas fa-history"></i> Lihat Riwayat
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-user-edit fa-3x text-warning mb-3"></i>
                            <h5>Edit Profil</h5>
                            <p class="text-muted">Perbarui informasi akun</p>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="fas fa-user-edit"></i> Edit Profil
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-headset fa-3x text-info mb-3"></i>
                            <h5>Bantuan</h5>
                            <p class="text-muted">Hubungi customer service</p>
                            <button class="btn btn-info">
                                <i class="fas fa-headset"></i> Hubungi CS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-star"></i> Rekomendasi Hotel Untuk Anda</h4>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recent_hotels as $hotel): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card hotel-card">
                                <div class="card-img-container position-relative">
                                    <img src="<?= $hotel['foto'] ?: 'https://via.placeholder.com/400x250?text=Hotel+Image' ?>" 
                                         class="card-img-top" alt="<?= htmlspecialchars($hotel['nama_hotel']) ?>">
                                    <div class="hotel-price position-absolute">
                                        <?= formatRupiah($hotel['harga_per_malam']) ?>/malam
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <h6 class="card-title hotel-name">
                                        <?= htmlspecialchars($hotel['nama_hotel']) ?>
                                    </h6>
                                    
                                    <p class="hotel-location mb-2">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($hotel['lokasi']) ?>
                                    </p>
                                    
                                    <p class="card-text small">
                                        <?= htmlspecialchars(substr($hotel['deskripsi'], 0, 80)) ?>...
                                    </p>
                                    
                                    <?php if ($hotel['stok_kamar'] <= 3 && $hotel['stok_kamar'] > 0): ?>
                                        <div class="alert alert-warning py-1 px-2 small">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Sisa <?= $hotel['stok_kamar'] ?> kamar!
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid">
                                        <?php if ($hotel['stok_kamar'] > 0): ?>
                                            <a href="deskripsi.php?id=<?= $hotel['id_hotel'] ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> Lihat Detail & Pesan
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-ban"></i> Tidak Tersedia
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-search"></i> Lihat Semua Hotel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Edit Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" action="update_profile.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Depan</label>
                                    <input type="text" class="form-control" name="nama_depan" 
                                           value="<?= htmlspecialchars($_SESSION['nama_depan']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Nama Belakang</label>
                                    <input type="text" class="form-control" name="nama_belakang" 
                                           value="<?= htmlspecialchars($_SESSION['nama_belakang']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($_SESSION['email']) ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="password_baru">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="konfirmasi_password">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
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
</body>
</html>