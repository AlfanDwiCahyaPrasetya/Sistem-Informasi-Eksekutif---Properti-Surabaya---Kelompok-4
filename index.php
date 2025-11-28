<?php
include 'config.php';

// Query untuk mengambil data kecamatan unik dengan jumlah properti
$query = "SELECT Kecamatan, COUNT(*) as jumlah_properti 
          FROM properti_surabaya 
          GROUP BY Kecamatan 
          ORDER BY Kecamatan";
$result = mysqli_query($conn, $query);

$kecamatan_list = [];
$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $kecamatan_list[] = [
        'no' => $no++,
        'nama_kecamatan' => $row['Kecamatan'],
        'jumlah_properti' => $row['jumlah_properti']
    ];
}

// Query untuk total properti
$total_query = "SELECT COUNT(*) as total FROM properti_surabaya";
$total_result = mysqli_query($conn, $total_query);
$total_properti = mysqli_fetch_assoc($total_result)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Data Kecamatan - Properti Surabaya</title>
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
        
        .stats-card h5 {
            font-size: 15px;
            opacity: 0.9;
            margin-bottom: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-card h2 {
            font-size: 42px;
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
            
            .stats-card h2 {
                font-size: 36px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
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
                            <a class="nav-link active" href="index.php">
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
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-map-marked-alt"></i> Data Kecamatan Surabaya</h1>
                </div>

                <!-- Statistik Cards -->
                <div class="row fade-in">
                    <div class="col-md-6 fade-in delay-1">
                        <div class="stats-card stats-card-1">
                            <h5><i class="fas fa-building"></i> Total Properti</h5>
                            <h2><?php echo number_format($total_properti); ?></h2>
                            <small>Properti terdaftar di sistem</small>
                        </div>
                    </div>
                    <div class="col-md-6 fade-in delay-2">
                        <div class="stats-card stats-card-2">
                            <h5><i class="fas fa-map-marker-alt"></i> Total Kecamatan</h5>
                            <h2><?php echo count($kecamatan_list); ?></h2>
                            <small>Kecamatan di Surabaya</small>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Kecamatan -->
                <div class="card fade-in delay-3">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Daftar Kecamatan di Surabaya
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="10%">No</th>
                                        <th width="50%">Nama Kecamatan</th>
                                        <th width="20%">Jumlah Properti</th>
                                        <th width="20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($kecamatan_list) > 0): ?>
                                        <?php foreach ($kecamatan_list as $kecamatan): ?>
                                            <tr>
                                                <td><?php echo $kecamatan['no']; ?></td>
                                                <td>
                                                    <strong><?php echo ucwords(strtolower($kecamatan['nama_kecamatan'])); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark">
                                                        <?php echo $kecamatan['jumlah_properti']; ?> properti
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="detail_kecamatan.php?kecamatan=<?php echo urlencode($kecamatan['nama_kecamatan']); ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> Lihat Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-inbox fa-2x mb-3" style="color: #ccc;"></i>
                                                <p class="mb-0">Tidak ada data kecamatan</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Footer -->
                <div class="alert alert-info mt-4" role="alert">
                    <i class="fas fa-info-circle"></i> 
                    <div>
                        <strong>Informasi:</strong> Klik tombol "Lihat Detail" untuk melihat semua properti yang tersedia di kecamatan tersebut.
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