<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    $status = $_GET['status'] ?? '';
    $bookings = getUserBookings($_SESSION['user_id'], $status);
    
    // Format data for JSON response
    foreach ($bookings as &$booking) {
        $booking['formatted_total'] = formatRupiah($booking['total_harga']);
        $booking['formatted_checkin'] = formatDate($booking['tanggal_checkin']);
        $booking['formatted_checkout'] = formatDate($booking['tanggal_checkout']);
        $booking['nights'] = (strtotime($booking['tanggal_checkout']) - strtotime($booking['tanggal_checkin'])) / (60 * 60 * 24);
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($bookings),
        'data' => $bookings
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>