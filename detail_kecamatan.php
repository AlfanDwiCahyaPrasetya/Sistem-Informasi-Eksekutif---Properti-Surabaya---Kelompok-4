<?php
include 'config.php';

// Ambil parameter kecamatan dari URL
$kecamatan = isset($_GET['kecamatan']) ? mysqli_real_escape_string($conn, $_GET['kecamatan']) : '';

// Set jumlah item per halaman
$items_per_page = 25;

// Ambil nomor halaman dari URL, default ke halaman 1 jika tidak ada
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Query untuk menghitung total data
$count_query = "SELECT COUNT(*) as total FROM properti_surabaya WHERE Kecamatan = '$kecamatan'";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];

// Hitung total halaman
$total_pages = ceil($total_rows / $items_per_page);

// Query untuk mengambil data properti berdasarkan kecamatan dengan pagination
$query = "SELECT * FROM properti_surabaya WHERE Kecamatan = '$kecamatan' LIMIT $items_per_page OFFSET $offset";
$result = mysqli_query($conn, $query);

$properti_list = [];
$no = $offset + 1;
while ($row = mysqli_fetch_assoc($result)) {
    $properti_list[] = $row;
}

// Hitung statistik untuk semua data di kecamatan (bukan hanya halaman saat ini)
$stats_query = "SELECT 
                COUNT(*) as total_properti,
                AVG(Price) as avg_price,
                MIN(Price) as min_price,
                MAX(Price) as max_price,
                AVG(`Luas Tanah`) as avg_luas_tanah,
                AVG(`Luas Bangunan`) as avg_luas_bangunan
                FROM properti_surabaya 
                WHERE Kecamatan = '$kecamatan'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kecamatan <?php echo htmlspecialchars($kecamatan); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --sidebar-bg: #1a1d29;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #4a5568;
            overflow-x: hidden;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 0;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            background: var(--sidebar-bg);
            transition: var(--transition);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: 100vh;
            padding-top: 1.5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem 1.5rem 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        
        .sidebar-header h4 {
            color: white;
            font-weight: 600;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header h4 i {
            color: var(--success);
        }
        
        .nav-link {
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8) !important;
            border-radius: 8px;
            margin: 4px 15px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--light));
            color: white !important;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            transition: var(--transition);
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            padding: 12px;
            border-radius: 10px;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 18px 25px;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-card {
            color: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .stats-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-20px, 20px);
        }
        
        .stats-card-1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stats-card-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stats-card-4 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stats-card h5 {
            font-size: 15px;
            opacity: 0.9;
            margin-bottom: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .stats-card small {
            opacity: 0.85;
            font-size: 13px;
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 16px 15px;
            font-weight: 600;
            vertical-align: middle;
        }
        
        .table tbody td {
            padding: 14px 15px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: scale(1.01);
            transition: var(--transition);
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-info {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--info);
            border-left: 4px solid var(--info);
        }
        
        .alert-warning {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }
        
        .pagination .page-link {
            color: var(--primary);
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin: 0 4px;
            transition: var(--transition);
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: var(--primary);
            color: white;
        }
        
        .pagination .page-link:hover {
            background-color: rgba(67, 97, 238, 0.1);
            border-color: var(--primary);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .sidebar-sticky {
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .stats-card h3 {
                font-size: 28px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .table-responsive {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-sticky pt-3">
                    <div class="sidebar-header">
                        <h4><i class="fas fa-city"></i> Dashboard Properti</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-table"></i> Data Kecamatan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="drilldown.php">
                                <i class="fas fa-chart-bar"></i> Data Drilldown
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="what-if.php">
                                <i class="fas fa-calculator"></i> What If Analysis
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Tombol Kembali -->
                <div class="mb-4 fade-in">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Data Kecamatan
                    </a>
                </div>

                <div class="page-header fade-in delay-1">
                    <h1 class="page-title">
                        <i class="fas fa-map-marker-alt"></i> 
                        Detail Kecamatan: <?php echo ucwords(strtolower($kecamatan)); ?>
                    </h1>
                </div>

                <!-- Statistik Kecamatan -->
                <div class="row">
                    <div class="col-md-3 col-sm-6 fade-in delay-1">
                        <div class="stats-card stats-card-1">
                            <h5><i class="fas fa-building"></i> Total Properti</h5>
                            <h3><?php echo number_format($stats['total_properti']); ?></h3>
                            <small>Properti terdaftar</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 fade-in delay-2">
                        <div class="stats-card stats-card-2">
                            <h5><i class="fas fa-money-bill-wave"></i> Harga Rata-rata</h5>
                            <h3>
                                <?php 
                                if (is_numeric($stats['avg_price']) && $stats['avg_price'] > 0) {
                                    echo 'Rp ' . number_format($stats['avg_price'] / 1000000, 1) . 'M';
                                } else {
                                    echo 'Tidak Tersedia';
                                }
                                ?>
                            </h3>
                            <small>
                                <?php 
                                if (is_numeric($stats['avg_price']) && $stats['avg_price'] > 0) {
                                    echo 'Rp ' . number_format($stats['avg_price'], 0, ',', '.');
                                } else {
                                    echo 'Data harga tidak tersedia';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 fade-in delay-3">
                        <div class="stats-card stats-card-3">
                            <h5><i class="fas fa-ruler-combined"></i> Avg Luas Tanah</h5>
                            <h3>
                                <?php 
                                if (is_numeric($stats['avg_luas_tanah']) && $stats['avg_luas_tanah'] > 0) {
                                    echo number_format($stats['avg_luas_tanah'], 0) . ' m²';
                                } else {
                                    echo 'Tidak Tersedia';
                                }
                                ?>
                            </h3>
                            <small>Rata-rata</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 fade-in delay-4">
                        <div class="stats-card stats-card-4">
                            <h5><i class="fas fa-home"></i> Avg Luas Bangunan</h5>
                            <h3>
                                <?php 
                                if (is_numeric($stats['avg_luas_bangunan']) && $stats['avg_luas_bangunan'] > 0) {
                                    echo number_format($stats['avg_luas_bangunan'], 0) . ' m²';
                                } else {
                                    echo 'Tidak Tersedia';
                                }
                                ?>
                            </h3>
                            <small>Rata-rata</small>
                        </div>
                    </div>
                </div>

                <!-- Info Rentang Harga -->
                <div class="alert alert-info fade-in" role="alert">
                    <i class="fas fa-info-circle"></i> 
                    <div>
                        <strong>Rentang Harga:</strong> 
                        Terendah: 
                        <?php 
                        if (is_numeric($stats['min_price']) && $stats['min_price'] > 0) {
                            echo 'Rp ' . number_format($stats['min_price'], 0, ',', '.');
                        } else {
                            echo 'Tidak Tersedia';
                        }
                        ?> | 
                        Tertinggi: 
                        <?php 
                        if (is_numeric($stats['max_price']) && $stats['max_price'] > 0) {
                            echo 'Rp ' . number_format($stats['max_price'], 0, ',', '.');
                        } else {
                            echo 'Tidak Tersedia';
                        }
                        ?>
                    </div>
                </div>

                <!-- Tabel Detail Properti -->
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-list"></i> 
                        Daftar Properti di Kecamatan <?php echo ucwords(strtolower($kecamatan)); ?>
                        <span class="badge bg-light text-dark float-end">
                            Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (count($properti_list) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="15%">Harga</th>
                                            <th width="8%">K. Tidur</th>
                                            <th width="8%">K. Mandi</th>
                                            <th width="10%">Luas Tanah</th>
                                            <th width="10%">Luas Bangunan</th>
                                            <th width="15%">Sertifikat</th>
                                            <th width="10%">Daya Listrik</th>
                                            <th width="10%">Lantai</th>
                                            <th width="10%">Kondisi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($properti_list as $properti): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td>
                                                    <strong>
                                                        <?php 
                                                        if (is_numeric($properti['Price']) && $properti['Price'] > 0) {
                                                            echo 'Rp ' . number_format($properti['Price'], 0, ',', '.');
                                                        } else {
                                                            echo 'Tidak Tersedia';
                                                        }
                                                        ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark">
                                                        <i class="fas fa-bed"></i> 
                                                        <?php 
                                                        if (is_numeric($properti['Kamar Tidur']) && $properti['Kamar Tidur'] > 0) {
                                                            echo $properti['Kamar Tidur'];
                                                        } else {
                                                            echo '0';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-bath"></i> 
                                                        <?php 
                                                        if (is_numeric($properti['Kamar Mandi']) && $properti['Kamar Mandi'] > 0) {
                                                            echo $properti['Kamar Mandi'];
                                                        } else {
                                                            echo '0';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (is_numeric($properti['Luas Tanah']) && $properti['Luas Tanah'] > 0) {
                                                        echo number_format($properti['Luas Tanah']) . ' m²';
                                                    } else {
                                                        echo 'Tidak Tersedia';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (is_numeric($properti['Luas Bangunan']) && $properti['Luas Bangunan'] > 0) {
                                                        echo number_format($properti['Luas Bangunan']) . ' m²';
                                                    } else {
                                                        echo 'Tidak Tersedia';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($properti['Sertifikat']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-bolt"></i> 
                                                        <?php 
                                                        if (is_numeric($properti['Daya Listrik']) && $properti['Daya Listrik'] > 0) {
                                                            echo number_format($properti['Daya Listrik']) . 'W';
                                                        } else {
                                                            echo 'Tidak Tersedia';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php 
                                                        if (is_numeric($properti['Jumlah Lantai']) && $properti['Jumlah Lantai'] > 0) {
                                                            echo $properti['Jumlah Lantai'] . ' Lantai';
                                                        } else {
                                                            echo '1 Lantai';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $kondisi = $properti['Kondisi Properti'];
                                                    $badge_class = 'bg-success';
                                                    if ($kondisi == 'Baru') {
                                                        $badge_class = 'bg-success';
                                                    } elseif ($kondisi == 'Bekas') {
                                                        $badge_class = 'bg-warning text-dark';
                                                    } else {
                                                        $badge_class = 'bg-secondary';
                                                        $kondisi = $kondisi ?: 'Tidak Diketahui';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($kondisi); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>                                
                                </table>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?kecamatan=<?php echo urlencode($kecamatan); ?>&page=<?php echo ($page - 1); ?>">
                                                    <i class="fas fa-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i> Previous
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Tampilkan maksimal 5 nomor halaman
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $start_page + 4);
                                        
                                        // Tampilkan halaman pertama
                                        if ($start_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?kecamatan=<?php echo urlencode($kecamatan); ?>&page=1">1</a>
                                            </li>
                                            <?php if ($start_page > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?kecamatan=<?php echo urlencode($kecamatan); ?>&page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php 
                                        // Tampilkan halaman terakhir
                                        if ($end_page < $total_pages): ?>
                                            <?php if ($end_page < $total_pages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?kecamatan=<?php echo urlencode($kecamatan); ?>&page=<?php echo $total_pages; ?>">
                                                    <?php echo $total_pages; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?kecamatan=<?php echo urlencode($kecamatan); ?>&page=<?php echo ($page + 1); ?>">
                                                    Next <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    Next <i class="fas fa-chevron-right"></i>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <div class="text-center text-muted mt-2">
                                    <small>
                                        Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $items_per_page, $total_rows); ?> 
                                        dari <?php echo number_format($total_rows); ?> properti
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Tidak ada data properti</strong> untuk kecamatan ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Menambahkan efek scroll animasi
        document.addEventListener('DOMContentLoaded', function() {
            // Animasi untuk elemen yang muncul saat scroll
            const fadeElements = document.querySelectorAll('.fade-in');
            
            const fadeInOnScroll = function() {
                fadeElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.style.opacity = "1";
                        element.style.transform = "translateY(0)";
                    }
                });
            };
            
            // Set initial state
            fadeElements.forEach(element => {
                element.style.opacity = "0";
                element.style.transform = "translateY(20px)";
                element.style.transition = "opacity 0.6s ease, transform 0.6s ease";
            });
            
            window.addEventListener('scroll', fadeInOnScroll);
            fadeInOnScroll(); // Trigger once on load
        });
    </script>
</body>
</html>