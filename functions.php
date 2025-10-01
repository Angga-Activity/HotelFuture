<?php
require_once 'config.php';

// User Functions
function registerUser($nama_depan, $nama_belakang, $email, $password) {
    global $pdo;
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id_pengguna FROM pengguna WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false; // Email already exists
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO pengguna (nama_depan, nama_belakang, email, password, hak_akses, tanggal_daftar) VALUES (?, ?, ?, ?, 'user', NOW())");
    return $stmt->execute([$nama_depan, $nama_belakang, $email, $hashed_password]);
}

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_pengguna'];
        $_SESSION['nama_depan'] = $user['nama_depan'];
        $_SESSION['nama_belakang'] = $user['nama_belakang'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['hak_akses'] = $user['hak_akses'];
        return true;
    }
    return false;
}

// Hotel Functions
function getAllHotels($search = '', $location = '', $min_price = 0, $max_price = 999999999) {
    global $pdo;
    
    $sql = "SELECT * FROM hotel WHERE nama_hotel LIKE ? AND lokasi LIKE ? AND harga_per_malam BETWEEN ? AND ? AND stok_kamar > 0 ORDER BY nama_hotel";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$search%", "%$location%", $min_price, $max_price]);
    return $stmt->fetchAll();
}

function getHotelById($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM hotel WHERE id_hotel = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateHotelStock($hotel_id, $quantity, $operation = 'decrease') {
    global $pdo;
    
    if ($operation === 'decrease') {
        $stmt = $pdo->prepare("UPDATE hotel SET stok_kamar = stok_kamar - ? WHERE id_hotel = ? AND stok_kamar >= ?");
        return $stmt->execute([$quantity, $hotel_id, $quantity]);
    } else {
        $stmt = $pdo->prepare("UPDATE hotel SET stok_kamar = stok_kamar + ? WHERE id_hotel = ?");
        return $stmt->execute([$quantity, $hotel_id]);
    }
}

// Booking Functions
function createBooking($user_id, $hotel_id, $checkin, $checkout, $jumlah_orang, $jumlah_kamar) {
    global $pdo;
    
    // Calculate total price
    $hotel = getHotelById($hotel_id);
    $days = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
    $total_harga = $hotel['harga_per_malam'] * $days * $jumlah_kamar;
    
    // Generate booking code
    $kode_booking = generateBookingCode();
    
    // Insert booking
    $stmt = $pdo->prepare("INSERT INTO pemesanan (id_pengguna, id_hotel, tanggal_checkin, tanggal_checkout, jumlah_orang, jumlah_kamar, total_harga, status, kode_booking, tanggal_pemesanan) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
    
    if ($stmt->execute([$user_id, $hotel_id, $checkin, $checkout, $jumlah_orang, $jumlah_kamar, $total_harga, $kode_booking])) {
        return $pdo->lastInsertId();
    }
    return false;
}

function processPayment($booking_id, $metode, $nomor_akun) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert payment record
        $stmt = $pdo->prepare("INSERT INTO pembayaran (id_pemesanan, metode, nomor_akun, tanggal_pembayaran, status_pembayaran) VALUES (?, ?, ?, NOW(), 'berhasil')");
        $stmt->execute([$booking_id, $metode, $nomor_akun]);
        
        // Update booking status
        $stmt = $pdo->prepare("UPDATE pemesanan SET status = 'berhasil' WHERE id_pemesanan = ?");
        $stmt->execute([$booking_id]);
        
        // Get booking details to update hotel stock
        $stmt = $pdo->prepare("SELECT id_hotel, jumlah_kamar FROM pemesanan WHERE id_pemesanan = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        // Update hotel stock
        updateHotelStock($booking['id_hotel'], $booking['jumlah_kamar']);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// Get user bookings
function getUserBookings($user_id, $status = '') {
    global $pdo;
    
    $sql = "SELECT p.*, h.nama_hotel, h.lokasi, pay.metode, pay.nomor_akun 
            FROM pemesanan p 
            JOIN hotel h ON p.id_hotel = h.id_hotel 
            LEFT JOIN pembayaran pay ON p.id_pemesanan = pay.id_pemesanan 
            WHERE p.id_pengguna = ?";
    
    $params = [$user_id];
    
    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY p.tanggal_pemesanan DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Admin Functions
function getAllUsers() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM pengguna ORDER BY tanggal_daftar DESC");
    return $stmt->fetchAll();
}

function getAllBookings() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT p.*, h.nama_hotel, u.nama_depan, u.nama_belakang, u.email 
                        FROM pemesanan p 
                        JOIN hotel h ON p.id_hotel = h.id_hotel 
                        JOIN pengguna u ON p.id_pengguna = u.id_pengguna 
                        ORDER BY p.tanggal_pemesanan DESC");
    return $stmt->fetchAll();
}

function getRevenueReport($period = 'monthly') {
    global $pdo;
    
    if ($period === 'daily') {
        $sql = "SELECT DATE(p.tanggal_pemesanan) as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                FROM pemesanan p 
                WHERE p.status = 'berhasil' AND DATE(p.tanggal_pemesanan) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(p.tanggal_pemesanan) 
                ORDER BY periode DESC";
    } else {
        $sql = "SELECT DATE_FORMAT(p.tanggal_pemesanan, '%Y-%m') as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                FROM pemesanan p 
                WHERE p.status = 'berhasil' AND p.tanggal_pemesanan >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(p.tanggal_pemesanan, '%Y-%m') 
                ORDER BY periode DESC";
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Utility Functions
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>