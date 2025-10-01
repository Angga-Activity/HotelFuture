<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

// Simple API key check (in production, use proper authentication)
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';

try {
    if (isset($_GET['id'])) {
        // Get specific hotel
        $hotel_id = (int)$_GET['id'];
        $hotel = getHotelById($hotel_id);
        
        if ($hotel) {
            $hotel['formatted_price'] = formatRupiah($hotel['harga_per_malam']);
            echo json_encode([
                'success' => true,
                'data' => $hotel
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Hotel tidak ditemukan'
            ]);
        }
    } else {
        // Get all hotels with filters
        $search = $_GET['search'] ?? '';
        $location = $_GET['location'] ?? '';
        $min_price = (int)($_GET['min_price'] ?? 0);
        $max_price = (int)($_GET['max_price'] ?? 999999999);
        
        $hotels = getAllHotels($search, $location, $min_price, $max_price);
        
        // Format prices
        foreach ($hotels as &$hotel) {
            $hotel['formatted_price'] = formatRupiah($hotel['harga_per_malam']);
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($hotels),
            'data' => $hotels
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>