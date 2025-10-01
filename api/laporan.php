<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

try {
    $type = $_GET['type'] ?? 'revenue';
    
    if ($type === 'stats') {
        // Get basic statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM pengguna WHERE hak_akses = 'user'");
        $total_users = $stmt->fetch()['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_hotels FROM hotel");
        $total_hotels = $stmt->fetch()['total_hotels'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM pemesanan");
        $total_bookings = $stmt->fetch()['total_bookings'];
        
        $stmt = $pdo->query("SELECT SUM(total_harga) as total_revenue FROM pemesanan WHERE status = 'berhasil'");
        $total_revenue = $stmt->fetch()['total_revenue'] ?: 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => $total_users,
                'total_hotels' => $total_hotels,
                'total_bookings' => $total_bookings,
                'total_revenue' => $total_revenue,
                'formatted_revenue' => formatRupiah($total_revenue)
            ]
        ]);
    } elseif ($type === 'revenue') {
        // Get revenue report
        $period = $_GET['period'] ?? 'monthly';
        $revenue_data = getRevenueReport($period);
        
        // Format data
        foreach ($revenue_data as &$data) {
            $data['formatted_revenue'] = formatRupiah($data['total_pendapatan']);
        }
        
        echo json_encode([
            'success' => true,
            'period' => $period,
            'data' => $revenue_data
        ]);
    } elseif ($type === 'popular_hotels') {
        // Get popular hotels
        $stmt = $pdo->query("SELECT h.nama_hotel, h.lokasi, COUNT(p.id_pemesanan) as total_bookings, SUM(p.total_harga) as total_revenue
                            FROM hotel h 
                            LEFT JOIN pemesanan p ON h.id_hotel = p.id_hotel AND p.status = 'berhasil'
                            GROUP BY h.id_hotel 
                            ORDER BY total_bookings DESC, total_revenue DESC 
                            LIMIT 10");
        $popular_hotels = $stmt->fetchAll();
        
        foreach ($popular_hotels as &$hotel) {
            $hotel['formatted_revenue'] = formatRupiah($hotel['total_revenue'] ?: 0);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $popular_hotels
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid report type'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>