<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

if ($_POST) {
    $nama_depan = sanitizeInput($_POST['nama_depan']);
    $nama_belakang = sanitizeInput($_POST['nama_belakang']);
    $email = sanitizeInput($_POST['email']);
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validation
    if (empty($nama_depan) || empty($nama_belakang) || empty($email)) {
        $error = 'Nama dan email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (!empty($password_baru) && strlen($password_baru) < 8) {
        $error = 'Password baru minimal 8 karakter';
    } elseif (!empty($password_baru) && $password_baru !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak sama';
    } else {
        // Check if email already exists (except current user)
        $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE email = ? AND id_pengguna != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = 'Email sudah digunakan oleh user lain';
        } else {
            // Update profile
            if (!empty($password_baru)) {
                // Update with new password
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE pengguna SET nama_depan = ?, nama_belakang = ?, email = ?, password = ? WHERE id_pengguna = ?");
                $result = $stmt->execute([$nama_depan, $nama_belakang, $email, $hashed_password, $_SESSION['user_id']]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE pengguna SET nama_depan = ?, nama_belakang = ?, email = ? WHERE id_pengguna = ?");
                $result = $stmt->execute([$nama_depan, $nama_belakang, $email, $_SESSION['user_id']]);
            }
            
            if ($result) {
                // Update session data
                $_SESSION['nama_depan'] = $nama_depan;
                $_SESSION['nama_belakang'] = $nama_belakang;
                $_SESSION['email'] = $email;
                
                $message = 'Profil berhasil diperbarui';
            } else {
                $error = 'Terjadi kesalahan saat memperbarui profil';
            }
        }
    }
}

// Redirect back to user dashboard with message
if ($message) {
    $_SESSION['profile_message'] = $message;
    redirect('user.php?updated=1');
} elseif ($error) {
    $_SESSION['profile_error'] = $error;
    redirect('user.php?error=1');
} else {
    redirect('user.php');
}
?>