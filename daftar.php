<?php
require_once 'config.php';
require_once 'functions.php';

$error = '';
$success = '';

if ($_POST) {
    $nama_depan = sanitizeInput($_POST['nama_depan']);
    $nama_belakang = sanitizeInput($_POST['nama_belakang']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validation
    if (empty($nama_depan) || empty($nama_belakang) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak sama';
    } else {
        if (registerUser($nama_depan, $nama_belakang, $email, $password)) {
            $success = 'Pendaftaran berhasil! Silakan login.';
            // Clear form data
            $nama_depan = $nama_belakang = $email = '';
        } else {
            $error = 'Email sudah terdaftar atau terjadi kesalahan';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - HotelAurora</title>
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
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Registration Form -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="card">
                        <div class="card-header text-center">
                            <h3><i class="fas fa-user-plus"></i> Daftar Akun Baru</h3>
                            <p class="mb-0">Bergabunglah dengan HotelAurora untuk pengalaman booking terbaik</p>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                                    <br><a href="login.php" class="alert-link">Klik di sini untuk login</a>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" id="registerForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="nama_depan" class="form-label">Nama Depan *</label>
                                            <input type="text" class="form-control" id="nama_depan" name="nama_depan" 
                                                   value="<?= htmlspecialchars($nama_depan ?? '') ?>" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="nama_belakang" class="form-label">Nama Belakang *</label>
                                            <input type="text" class="form-control" id="nama_belakang" name="nama_belakang" 
                                                   value="<?= htmlspecialchars($nama_belakang ?? '') ?>" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email ?? '') ?>" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimal 8 karakter</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <label for="konfirmasi_password" class="form-label">Konfirmasi Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Saya setuju dengan <a href="#" class="text-primary">Syarat dan Ketentuan</a> 
                                        serta <a href="#" class="text-primary">Kebijakan Privasi</a>
                                    </label>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">
                                    Sudah punya akun? 
                                    <a href="login.php" class="text-primary fw-bold">Login di sini</a>
                                </p>
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
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const password = document.getElementById('konfirmasi_password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>