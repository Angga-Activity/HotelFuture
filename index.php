<?php
require_once 'config.php';
require_once 'functions.php';

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 999999999;


$hotels = getAllHotels($search, $location, $min_price, $max_price);


$stmt = $pdo->query("SELECT DISTINCT lokasi FROM hotel ORDER BY lokasi");
$locations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelFuture - Sistem Pemesanan Hotel Masa Depan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

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

    <section class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary mb-4">
                        Selamat Datang di <span class="text-gradient">HotelFuture</span>
                    </h1>
                    <p class="lead mb-4">
                        Sistem pemesanan hotel masa depan dengan teknologi terdepan. 
                        Temukan hotel impian Anda dengan mudah dan nyaman.
                    </p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="daftar.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-rocket"></i> Mulai Sekarang
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <i class="fas fa-building display-1 text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="search-section">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">
                        <i class="fas fa-search"></i> Cari Hotel Impian Anda
                    </h3>
                    
                    <form method="GET" class="search-form" id="searchForm">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Nama Hotel</label>
                                <input type="text" class="form-control" name="search" id="searchInput" 
                                       placeholder="Cari nama hotel..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Lokasi</label>
                                <select class="form-select" name="location" id="locationFilter">
                                    <option value="">Semua Lokasi</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= htmlspecialchars($loc['lokasi']) ?>" 
                                                <?= $location === $loc['lokasi'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loc['lokasi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Harga Min</label>
                                <input type="number" class="form-control" name="min_price" id="minPrice" 
                                       placeholder="0" value="<?= $min_price > 0 ? $min_price : '' ?>">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Harga Max</label>
                                <input type="number" class="form-control" name="max_price" id="maxPrice" 
                                       placeholder="Tidak terbatas" value="<?= $max_price < 999999999 ? $max_price : '' ?>">
                            </div>
                            <div class="col-lg-2 col-md-12">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
>
    <section class="hotels-section py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title">
                    <i class="fas fa-hotel"></i> 
                    <?= empty($search) && empty($location) ? 'Hotel Tersedia' : 'Hasil Pencarian' ?>
                </h2>
                <div class="text-muted">
                    Ditemukan <?= count($hotels) ?> hotel
                </div>
            </div>

            <div id="loading" class="text-center" style="display: none;">
                <div class="spinner"></div>
                <p>Mencari hotel...</p>
            </div>

            <div class="row" id="hotelContainer">
                <?php if (empty($hotels)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-search"></i>
                            <h4>Tidak ada hotel yang ditemukan</h4>
                            <p>Coba ubah kriteria pencarian Anda atau <a href="index.php">lihat semua hotel</a></p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($hotels as $hotel): ?>
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
                                    <h5 class="card-title hotel-name">
                                        <?= htmlspecialchars($hotel['nama_hotel']) ?>
                                    </h5>
                                    
                                    <p class="hotel-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($hotel['lokasi']) ?>
                                    </p>
                                    
                                    <p class="card-text hotel-description">
                                        <?= htmlspecialchars(substr($hotel['deskripsi'], 0, 100)) ?>
                                        <?= strlen($hotel['deskripsi']) > 100 ? '...' : '' ?>
                                    </p>
                                    
                                    <?php if ($hotel['stok_kamar'] <= 3 && $hotel['stok_kamar'] > 0): ?>
                                        <div class="stock-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Hanya tersisa <?= $hotel['stok_kamar'] ?> kamar!
                                        </div>
                                    <?php elseif ($hotel['stok_kamar'] == 0): ?>
                                        <div class="stock-empty">
                                            <i class="fas fa-times-circle"></i>
                                            Kamar tidak tersedia
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid">
                                        <?php if ($hotel['stok_kamar'] > 0): ?>
                                            <a href="deskripsi.php?id=<?= $hotel['id_hotel'] ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-eye"></i> Lihat Detail
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-ban"></i> Tidak Tersedia
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="features-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Mengapa Memilih HotelFuture?</h2>
            
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        </div>
                        <h5>Pencarian Mudah</h5>
                        <p class="text-muted">Temukan hotel sesuai kebutuhan dengan filter canggih</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                        </div>
                        <h5>Pembayaran Digital</h5>
                        <p class="text-muted">Bayar dengan e-wallet favorit Anda dengan aman</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                        </div>
                        <h5>Booking Real-time</h5>
                        <p class="text-muted">Konfirmasi instan dan kode booking langsung</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                        </div>
                        <h5>Support 24/7</h5>
                        <p class="text-muted">Tim support siap membantu kapan saja</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
>
    <div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-hotel"></i> HotelFuture</h5>
                    <p>Sistem pemesanan hotel masa depan dengan teknologi terdepan untuk pengalaman booking yang tak terlupakan.</p>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <h5>Kontak</h5>
                    <p><i class="fas fa-envelope"></i> ujangangga@hotelfuture.com</p>
                    <p><i class="fas fa-phone"></i> +62 821 2995 2530</p>
                    <p><i class="fas fa-map-marker-alt"></i> Bandung, Indonesia</p>
                </div>
                
              <div class="col-lg-4 mb-4">
    <h5>Ikuti Kami</h5>
    <div class="social-links">
        <a href="https://wa.me/6282129952530 target="_blank" class="text-light me-3">
            <i class="fab fa-whatsapp fa-2x"></i>
        </a>
        <a href="https://instagram.com/angga_Activity" target="_blank" class="text-light me-3">
            <i class="fab fa-instagram fa-2x"></i>
        </a>
        <a href="https://github.com/Angga-Activity" target="_blank" class="text-light me-3">
            <i class="fab fa-github fa-2x"></i>
        </a>
    </div>
</div>

            
            <hr class="my-4">
            
            <div class="text-center">
                <p>&copy; 2025 HotelFuture. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script src="validator.js"></script>
</body>
</html>