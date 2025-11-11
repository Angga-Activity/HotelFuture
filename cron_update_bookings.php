<?php

require_once 'config.php';
require_once 'functions.php';

try {
    // Update booking status
    $updated = updateBookingStatusByDate();
    
    // Log the result
    $log_message = date('Y-m-d H:i:s') . " - Booking status update completed. Updated: " . ($updated ? "Yes" : "No") . "\n";
    file_put_contents('logs/booking_updates.log', $log_message, FILE_APPEND | LOCK_EX);
    
    echo "Booking status update completed successfully.\n";
} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - Error updating booking status: " . $e->getMessage() . "\n";
    file_put_contents('logs/booking_errors.log', $error_message, FILE_APPEND | LOCK_EX);
    
    echo "Error updating booking status: " . $e->getMessage() . "\n";
}
?>