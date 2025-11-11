<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get parameters
$period = $_GET['period'] ?? 'daily';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Auto-update booking status
updateBookingStatusByDate();

// Get filtered revenue data
if (!empty($start_date) && !empty($end_date)) {
    $result = getFilteredRevenueReport($period, $start_date, $end_date);
    $revenue_data = $result['revenue_data'];
    $total_revenue_all = $result['total_revenue'];
    $total_transactions_all = $result['total_transactions'];
    
    // Set title based on period
    if ($period === 'daily') {
        $title = 'Laporan Pendapatan Harian (' . formatDate($start_date) . ' - ' . formatDate($end_date) . ')';
    } elseif ($period === 'monthly') {
        $start_month = date('F Y', strtotime($start_date . '-01'));
        $end_month = date('F Y', strtotime($end_date . '-01'));
        $title = 'Laporan Pendapatan Bulanan (' . $start_month . ' - ' . $end_month . ')';
    } else {
        $title = 'Laporan Pendapatan Tahunan (' . $start_date . ' - ' . $end_date . ')';
    }
} else {
    // Fallback to default report
    if ($period === 'daily') {
        $revenue_data = getRevenueReport('daily');
        $title = 'Laporan Pendapatan Harian (30 Hari Terakhir)';
    } elseif ($period === 'monthly') {
        $revenue_data = getRevenueReport('monthly');
        $title = 'Laporan Pendapatan Bulanan (12 Bulan Terakhir)';
    } else {
        $stmt = $pdo->query("SELECT DATE_FORMAT(p.tanggal_pemesanan, '%Y') as periode, SUM(p.total_harga) as total_pendapatan, COUNT(*) as jumlah_transaksi
                            FROM pemesanan p 
                            WHERE p.status IN ('berhasil', 'selesai') AND p.tanggal_pemesanan >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
                            GROUP BY DATE_FORMAT(p.tanggal_pemesanan, '%Y') 
                            ORDER BY periode DESC");
        $revenue_data = $stmt->fetchAll();
        $title = 'Laporan Pendapatan Tahunan (5 Tahun Terakhir)';
    }
    
    // Calculate totals
    $total_revenue_all = 0;
    $total_transactions_all = 0;
    foreach ($revenue_data as $revenue) {
        $total_revenue_all += $revenue['total_pendapatan'];
        $total_transactions_all += $revenue['jumlah_transaksi'];
    }
}

// Set headers for PDF download
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - HotelAurora</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        .summary h3 {
            margin-top: 0;
            color: #007bff;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .summary-item .label {
            color: #666;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e9ecef;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
        @media print {
            .print-btn {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print PDF
    </button>

    <div class="header">
        <h1>HotelAurora</h1>
        <h2><?= $title ?></h2>
        <p>Digenerate pada: <?= date('d F Y, H:i:s') ?></p>
    </div>

    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value"><?= $total_transactions_all ?></div>
                <div class="label">Total Transaksi</div>
            </div>
            <div class="summary-item">
                <div class="value"><?= formatRupiah($total_revenue_all) ?></div>
                <div class="label">Total Pendapatan</div>
            </div>
            <div class="summary-item">
                <div class="value"><?= $total_transactions_all > 0 ? formatRupiah($total_revenue_all / $total_transactions_all) : 'Rp 0' ?></div>
                <div class="label">Rata-rata per Transaksi</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th><?= $period === 'daily' ? 'Tanggal' : ($period === 'monthly' ? 'Bulan' : 'Tahun') ?></th>
                <th class="text-center">Jumlah Transaksi</th>
                <th class="text-right">Total Pendapatan</th>
                <th class="text-right">Rata-rata per Transaksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($revenue_data as $revenue): 
                $avg_per_transaction = $revenue['jumlah_transaksi'] > 0 ? $revenue['total_pendapatan'] / $revenue['jumlah_transaksi'] : 0;
            ?>
                <tr>
                    <td>
                        <strong>
                            <?php if ($period === 'daily'): ?>
                                <?= formatDate($revenue['periode']) ?>
                            <?php elseif ($period === 'monthly'): ?>
                                <?= date('F Y', strtotime($revenue['periode'] . '-01')) ?>
                            <?php else: ?>
                                <?= $revenue['periode'] ?>
                            <?php endif; ?>
                        </strong>
                    </td>
                    <td class="text-center"><?= $revenue['jumlah_transaksi'] ?> transaksi</td>
                    <td class="text-right"><strong><?= formatRupiah($revenue['total_pendapatan']) ?></strong></td>
                    <td class="text-right"><?= formatRupiah($avg_per_transaction) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #343a40; color: white; font-weight: bold;">
                <td><strong>TOTAL KESELURUHAN</strong></td>
                <td class="text-center"><strong><?= $total_transactions_all ?> transaksi</strong></td>
                <td class="text-right"><strong><?= formatRupiah($total_revenue_all) ?></strong></td>
                <td class="text-right"><strong><?= $total_transactions_all > 0 ? formatRupiah($total_revenue_all / $total_transactions_all) : formatRupiah(0) ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p><strong>HotelAurora</strong> - Sistem Manajemen Hotel</p>
        <p>Laporan ini digenerate secara otomatis oleh sistem pada <?= date('d F Y, H:i:s') ?></p>
        <p>© <?= date('Y') ?> HotelAurora. All rights reserved.</p>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>