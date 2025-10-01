<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$format = isset($_GET['format']) ? $_GET['format'] : 'html';
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';

// Get revenue data
$revenue_data = getRevenueReport($period);

if ($format === 'pdf') {
    // For PDF export (simplified version)
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Pendapatan - HotelFuture</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1a365d; padding-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #1a365d; color: white; }
            .total { font-weight: bold; background-color: #f0f8ff; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        </style>
        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="header">
            <h1>🏨 HotelFuture</h1>
            <h2>Laporan Pendapatan <?= ucfirst($period) ?></h2>
            <p>Digenerate pada: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_revenue = 0;
                $total_transactions = 0;
                foreach ($revenue_data as $data): 
                    $total_revenue += $data['total_pendapatan'];
                    $total_transactions += $data['jumlah_transaksi'];
                ?>
                    <tr>
                        <td><?= $period === 'daily' ? formatDate($data['periode']) : date('F Y', strtotime($data['periode'] . '-01')) ?></td>
                        <td><?= $data['jumlah_transaksi'] ?></td>
                        <td><?= formatRupiah($data['total_pendapatan']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td><strong>TOTAL</strong></td>
                    <td><strong><?= $total_transactions ?></strong></td>
                    <td><strong><?= formatRupiah($total_revenue) ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Laporan ini digenerate secara otomatis oleh sistem HotelFuture</p>
            <p>&copy; 2025 HotelFuture. All rights reserved.</p>
        </div>
    </body>
    </html>
    <?php
} else {
    // CSV Export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_pendapatan_' . $period . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, ['Periode', 'Jumlah Transaksi', 'Total Pendapatan']);
    
    // CSV Data
    foreach ($revenue_data as $data) {
        $periode = $period === 'daily' ? $data['periode'] : date('F Y', strtotime($data['periode'] . '-01'));
        fputcsv($output, [$periode, $data['jumlah_transaksi'], $data['total_pendapatan']]);
    }
    
    fclose($output);
}
?>