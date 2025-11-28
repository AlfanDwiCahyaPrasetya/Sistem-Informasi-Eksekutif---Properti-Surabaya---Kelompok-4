<?php
include 'config.php';

// Query untuk mengambil data kecamatan unik
$query = "SELECT DISTINCT Kecamatan FROM properti_surabaya ORDER BY Kecamatan";
$result = mysqli_query($conn, $query);

$kecamatan_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $kecamatan_list[] = $row['Kecamatan'];
}

// Query untuk statistik dasar
$stats_query = "SELECT 
                AVG(CASE WHEN Price REGEXP '^[0-9]+$' THEN Price ELSE NULL END) as avg_price,
                AVG(CASE WHEN Luas Tanah REGEXP '^[0-9]+$' THEN Luas Tanah ELSE NULL END) as avg_luas_tanah,
                AVG(CASE WHEN Luas Bangunan REGEXP '^[0-9]+$' THEN Luas Bangunan ELSE NULL END) as avg_luas_bangunan
                FROM properti_surabaya";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Handle AJAX request untuk mendapatkan data properti berdasarkan kecamatan
if (isset($_POST['action']) && $_POST['action'] == 'get_property_data') {
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    
    $prop_query = "SELECT 
                    Price,
                    Luas Tanah,
                    Luas Bangunan,
                    Kamar Tidur,
                    Kamar Mandi
                   FROM properti_surabaya 
                   WHERE Kecamatan = '$kecamatan' 
                   AND Price REGEXP '^[0-9]+$'
                   AND Luas Tanah REGEXP '^[0-9]+$'
                   AND Luas Bangunan REGEXP '^[0-9]+$'
                   LIMIT 1";
    
    $prop_result = mysqli_query($conn, $prop_query);
    $property_data = mysqli_fetch_assoc($prop_result);
    
    echo json_encode($property_data);
    exit;
}

// Handle AJAX request untuk What If Analysis
if (isset($_POST['action']) && $_POST['action'] == 'calculate_whatif') {
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $variable = $_POST['variable'];
    $new_value = floatval($_POST['new_value']);
    $current_price = floatval($_POST['current_price']);
    $current_luas_tanah = floatval($_POST['current_luas_tanah']);
    $current_luas_bangunan = floatval($_POST['current_luas_bangunan']);
    
    $result = [];
    
    switch ($variable) {
        case 'luas_tanah':
            // Hitung harga berdasarkan perubahan luas tanah
            if ($current_luas_tanah > 0) {
                $harga_per_m2_tanah = $current_price / $current_luas_tanah;
                $result['price'] = $new_value * $harga_per_m2_tanah;
                
                // PROYEKSI luas bangunan berdasarkan rasio saat ini
                if ($current_luas_tanah > 0 && $current_luas_bangunan > 0) {
                    $rasio_bangunan = $current_luas_bangunan / $current_luas_tanah;
                    $result['luas_bangunan'] = $new_value * $rasio_bangunan;
                } else {
                    $result['luas_bangunan'] = $current_luas_bangunan;
                }
            } else {
                $result['price'] = $current_price;
                $result['luas_bangunan'] = $current_luas_bangunan;
            }
            $result['luas_tanah'] = $new_value;
            break;
            
        case 'luas_bangunan':
            // Hitung harga berdasarkan perubahan luas bangunan
            if ($current_luas_bangunan > 0) {
                $harga_per_m2_bangunan = $current_price / $current_luas_bangunan;
                $result['price'] = $new_value * $harga_per_m2_bangunan;
                
                // PROYEKSI luas tanah berdasarkan rasio saat ini
                if ($current_luas_bangunan > 0 && $current_luas_tanah > 0) {
                    $rasio_tanah = $current_luas_tanah / $current_luas_bangunan;
                    $result['luas_tanah'] = $new_value * $rasio_tanah;
                } else {
                    $result['luas_tanah'] = $current_luas_tanah;
                }
            } else {
                $result['price'] = $current_price;
                $result['luas_tanah'] = $current_luas_tanah;
            }
            $result['luas_bangunan'] = $new_value;
            break;
            
        case 'price':
            // Hitung luas tanah dan bangunan berdasarkan harga baru
            $result['price'] = $new_value;
            
            if ($current_price > 0) {
                // Hitung faktor skala berdasarkan perubahan harga
                $scale_factor = $new_value / $current_price;
                
                // Terapkan faktor skala ke luas tanah dan bangunan
                $result['luas_tanah'] = $current_luas_tanah * $scale_factor;
                $result['luas_bangunan'] = $current_luas_bangunan * $scale_factor;
            } else {
                $result['luas_tanah'] = $current_luas_tanah;
                $result['luas_bangunan'] = $current_luas_bangunan;
            }
            break;
    }
    
    // Pastikan nilai tidak negatif
    $result['price'] = max(0, $result['price']);
    $result['luas_tanah'] = max(0, $result['luas_tanah']);
    $result['luas_bangunan'] = max(0, $result['luas_bangunan']);
    
    // Hitung perubahan persentase
    $result['price_change_pct'] = $current_price > 0 ? (($result['price'] - $current_price) / $current_price) * 100 : 0;
    $result['luas_tanah_change_pct'] = $current_luas_tanah > 0 ? (($result['luas_tanah'] - $current_luas_tanah) / $current_luas_tanah) * 100 : 0;
    $result['luas_bangunan_change_pct'] = $current_luas_bangunan > 0 ? (($result['luas_bangunan'] - $current_luas_bangunan) / $current_luas_bangunan) * 100 : 0;
    
    // Format numbers
    $result['price'] = round($result['price']);
    $result['luas_tanah'] = round($result['luas_tanah'], 2);
    $result['luas_bangunan'] = round($result['luas_bangunan'], 2);
    
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>What If Analysis - Properti Surabaya</title>
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
        
        /* Sidebar Responsive */
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
            width: 280px;
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
            font-size: 1.1rem;
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
            font-size: 0.9rem;
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
        
        /* Main Content Responsive */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            transition: var(--transition);
            min-height: 100vh;
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
            font-size: 1.5rem;
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
            font-size: 1.1rem;
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            border: none;
        }
        
        .info-box h5 {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        
        .info-box p {
            margin-bottom: 0;
            opacity: 0.9;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .btn-calculate {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 16px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
        }
        
        .btn-calculate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        .btn-calculate:disabled {
            opacity: 0.7;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .result-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
        }
        
        .data-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }
        
        .metric-card {
            text-align: center;
            padding: 25px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            transition: var(--transition);
            border: none;
            box-shadow: var(--card-shadow);
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 15px 0;
            line-height: 1.2;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .change-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            margin-top: 8px;
            display: inline-block;
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--info);
        }
        
        .alert-info {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--info);
        }
        
        .comparison-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: var(--card-shadow);
        }
        
        .comparison-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        
        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .comparison-item {
            text-align: center;
            padding: 20px 15px;
            border-radius: 12px;
            background: #f8f9fa;
        }
        
        .comparison-value {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 10px 0;
            line-height: 1.2;
        }
        
        .comparison-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
        
        /* ============ RESPONSIVE STYLES ============ */
        
        /* Large devices (desktops, less than 1200px) */
        @media (max-width: 1199.98px) {
            .sidebar {
                width: 250px;
            }
            
            .main-content {
                margin-left: 250px;
            }
            
            .metric-value {
                font-size: 1.6rem;
            }
        }
        
        /* Medium devices (tablets, less than 992px) */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .page-title {
                font-size: 1.3rem;
                margin-top: 10px;
            }
            
            .card-header {
                padding: 15px 20px;
                font-size: 1rem;
            }
            
            .info-box {
                padding: 20px;
            }
            
            .info-box h5 {
                font-size: 1.1rem;
            }
            
            .btn-calculate {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Small devices (landscape phones, less than 768px) */
        @media (max-width: 767.98px) {
            .main-content {
                padding: 15px 10px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 20px;
            }
            
            .page-title {
                font-size: 1.2rem;
            }
            
            .card {
                border-radius: 12px;
                margin-bottom: 20px;
            }
            
            .card-header {
                padding: 12px 15px;
                border-radius: 12px 12px 0 0 !important;
                font-size: 0.95rem;
            }
            
            .metric-card {
                padding: 20px 15px;
                margin-bottom: 15px;
            }
            
            .metric-value {
                font-size: 1.4rem;
                margin: 10px 0;
            }
            
            .comparison-section {
                padding: 20px 15px;
                margin: 20px 0;
            }
            
            .comparison-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .comparison-item {
                padding: 15px 10px;
            }
            
            .comparison-value {
                font-size: 1.2rem;
            }
            
            .info-box {
                padding: 15px;
                border-radius: 12px;
            }
            
            .form-control {
                padding: 10px 12px;
            }
            
            .btn-calculate {
                padding: 12px 20px;
                font-size: 15px;
            }
        }
        
        /* Extra small devices (portrait phones, less than 576px) */
        @media (max-width: 575.98px) {
            .main-content {
                padding: 10px 5px;
            }
            
            .page-title {
                font-size: 1.1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .page-title i {
                padding: 8px;
            }
            
            .card-header {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .metric-card {
                padding: 15px 10px;
            }
            
            .metric-value {
                font-size: 1.2rem;
            }
            
            .metric-label {
                font-size: 0.8rem;
            }
            
            .comparison-section {
                padding: 15px 10px;
            }
            
            .comparison-title {
                font-size: 1rem;
            }
            
            .info-box h5 {
                font-size: 1rem;
            }
            
            .info-box p {
                font-size: 0.85rem;
            }
            
            .alert {
                padding: 15px;
            }
            
            .change-badge {
                font-size: 10px;
                padding: 4px 8px;
            }
            
            .mobile-menu-toggle {
                top: 15px;
                left: 15px;
                padding: 8px 12px;
                font-size: 1rem;
            }
        }
        
        /* Very small devices (less than 400px) */
        @media (max-width: 399.98px) {
            .sidebar {
                width: 100%;
            }
            
            .main-content {
                padding: 5px;
            }
            
            .page-title {
                font-size: 1rem;
            }
            
            .card-header {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }
            
            .metric-value {
                font-size: 1.1rem;
            }
            
            .btn-calculate {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .form-control {
                font-size: 13px;
            }
        }
        
        /* Print styles */
        @media print {
            .sidebar, .mobile-menu-toggle {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar" id="sidebar">
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
                            <a class="nav-link active" href="what-if.php">
                                <i class="fas fa-calculator"></i> What If Analysis
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content" id="mainContent">
                <!-- Your existing main content remains the same -->
                <div class="page-header fade-in">
                    <h1 class="page-title">
                        <i class="fas fa-calculator"></i> Analisis What If Properti Surabaya
                    </h1>
                </div>

                <div class="info-box fade-in delay-1">
                    <h5><i class="fas fa-info-circle"></i> Tentang What If Analysis</h5>
                    <p>Analisis What If memungkinkan Anda untuk mensimulasikan perubahan pada variabel properti (Harga, Luas Tanah, atau Luas Bangunan) dan melihat dampaknya terhadap variabel lainnya. Pilih kecamatan, tentukan variabel yang ingin diubah, dan lihat hasil proyeksinya.</p>
                </div>

                <!-- Section 1: Data Properti -->
                <div class="card fade-in delay-1">
                    <div class="card-header">
                        <i class="fas fa-home"></i> Data Properti
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="kecamatan-select" class="form-label">Pilih Kecamatan:</label>
                                <select class="form-select" id="kecamatan-select">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    <?php foreach ($kecamatan_list as $kec): ?>
                                        <option value="<?php echo htmlspecialchars($kec); ?>">
                                            <?php echo htmlspecialchars(ucwords($kec)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="property-data-display" class="mt-4 d-none fade-in">
                            <h6 class="mb-3" style="color: var(--dark); font-weight: 600;">Data Properti Saat Ini:</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="metric-card data-card">
                                        <div class="metric-label">Harga Properti</div>
                                        <div class="metric-value" id="display-price">-</div>
                                        <small id="display-price-text">-</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="metric-card data-card">
                                        <div class="metric-label">Luas Tanah</div>
                                        <div class="metric-value" id="display-luas-tanah">-</div>
                                        <small>m²</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="metric-card data-card">
                                        <div class="metric-label">Luas Bangunan</div>
                                        <div class="metric-value" id="display-luas-bangunan">-</div>
                                        <small>m²</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Input What If Analysis -->
                <div class="card fade-in delay-2">
                    <div class="card-header">
                        <i class="fas fa-edit"></i> Masukkan Data Analisis What If
                    </div>
                    <div class="card-body">
                        <form id="what-if-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="variable-select" class="form-label">Variabel yang Ingin Diubah:</label>
                                    <select class="form-select" id="variable-select" required>
                                        <option value="">-- Pilih Variabel --</option>
                                        <option value="price">Harga Properti</option>
                                        <option value="luas_tanah">Luas Tanah</option>
                                        <option value="luas_bangunan">Luas Bangunan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="new-value" class="form-label">Nilai Baru:</label>
                                    <input type="number" class="form-control" id="new-value" placeholder="Masukkan nilai baru" required>
                                    <small class="text-muted" id="value-hint">Pilih variabel terlebih dahulu</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-calculate" id="btn-analyze">
                                <i class="fas fa-chart-line"></i> Hitung Analisis What If
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Section 3: Hasil Analisis -->
                <div class="card d-none fade-in" id="hasil-analisis">
                    <div class="card-header result-card">
                        <i class="fas fa-chart-bar"></i> Hasil Analisis What If
                    </div>
                    <div class="card-body">
                        <!-- Comparison Section -->
                        <div class="comparison-section">
                            <h5 class="comparison-title">
                                <i class="fas fa-balance-scale"></i> Perbandingan Nilai
                            </h5>
                            <div class="comparison-grid">
                                <div class="comparison-item">
                                    <div class="comparison-label">Harga Sebelumnya</div>
                                    <div class="comparison-value text-muted" id="comparison-price-before">-</div>
                                </div>
                                <div class="comparison-item">
                                    <div class="comparison-label">Harga Proyeksi</div>
                                    <div class="comparison-value text-primary" id="comparison-price-after">-</div>
                                </div>
                                <div class="comparison-item">
                                    <div class="comparison-label">Perubahan Harga</div>
                                    <div class="comparison-value" id="comparison-price-change">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Results Cards -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="metric-card" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                                    <div class="metric-label">Harga Proyeksi</div>
                                    <div class="metric-value text-primary" id="result-price">-</div>
                                    <small id="result-price-text" class="text-muted">-</small>
                                    <div class="mt-2">
                                        <span id="price-change" class="change-badge">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="metric-card" style="background: linear-gradient(135deg, #f3e5f5, #e1bee7);">
                                    <div class="metric-label">Luas Tanah Proyeksi</div>
                                    <div class="metric-value" style="color: #7b1fa2;" id="result-luas-tanah">-</div>
                                    <small class="text-muted">m²</small>
                                    <div class="mt-2">
                                        <span id="tanah-change" class="change-badge">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="metric-card" style="background: linear-gradient(135deg, #fff3e0, #ffcc80);">
                                    <div class="metric-label">Luas Bangunan Proyeksi</div>
                                    <div class="metric-value text-warning" id="result-luas-bangunan">-</div>
                                    <small class="text-muted">m²</small>
                                    <div class="mt-2">
                                        <span id="bangunan-change" class="change-badge">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h6><i class="fas fa-lightbulb"></i> Interpretasi Hasil:</h6>
                            <p class="mb-0" id="interpretation-text">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle functionality
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.getElementById('mobileMenuToggle');
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = mobileToggle.contains(event.target);
            
            if (window.innerWidth <= 991.98 && !isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Your existing JavaScript code remains the same
        let currentPropertyData = {};

        // Format number to Rupiah
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        // Format number with thousand separator
        function formatNumber(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        // Load property data when kecamatan is selected
        document.getElementById('kecamatan-select').addEventListener('change', function() {
            const kecamatan = this.value;
            if (!kecamatan) {
                document.getElementById('property-data-display').classList.add('d-none');
                return;
            }

            // Show loading state
            const propertyDisplay = document.getElementById('property-data-display');
            propertyDisplay.classList.remove('d-none');
            propertyDisplay.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat data properti...</p>
                </div>
            `;

            // AJAX request to get property data
            fetch('what-if.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_property_data&kecamatan=' + encodeURIComponent(kecamatan)
            })
            .then(response => response.json())
            .then(data => {
                if (data) {
                    currentPropertyData = {
                        price: parseFloat(data.Price),
                        luas_tanah: parseFloat(data['Luas Tanah']),
                        luas_bangunan: parseFloat(data['Luas Bangunan'])
                    };

                    // Display data
                    propertyDisplay.innerHTML = `
                        <h6 class="mb-3" style="color: var(--dark); font-weight: 600;">Data Properti Saat Ini:</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="metric-card data-card">
                                    <div class="metric-label">Harga Properti</div>
                                    <div class="metric-value" id="display-price">${formatRupiah(currentPropertyData.price)}</div>
                                    <small>${formatRupiah(currentPropertyData.price)}</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="metric-card data-card">
                                    <div class="metric-label">Luas Tanah</div>
                                    <div class="metric-value" id="display-luas-tanah">${formatNumber(currentPropertyData.luas_tanah)}</div>
                                    <small>m²</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="metric-card data-card">
                                    <div class="metric-label">Luas Bangunan</div>
                                    <div class="metric-value" id="display-luas-bangunan">${formatNumber(currentPropertyData.luas_bangunan)}</div>
                                    <small>m²</small>
                                </div>
                            </div>
                        </div>
                    `;

                    document.getElementById('hasil-analisis').classList.add('d-none');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                propertyDisplay.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle"></i> Terjadi kesalahan saat mengambil data properti
                    </div>
                `;
            });
        });

        // Update hint text based on selected variable
        document.getElementById('variable-select').addEventListener('change', function() {
            const variable = this.value;
            const hintText = document.getElementById('value-hint');
            
            switch(variable) {
                case 'price':
                    hintText.textContent = 'Contoh: 2500000000 (dalam Rupiah)';
                    break;
                case 'luas_tanah':
                    hintText.textContent = 'Contoh: 150 (dalam m²)';
                    break;
                case 'luas_bangunan':
                    hintText.textContent = 'Contoh: 120 (dalam m²)';
                    break;
                default:
                    hintText.textContent = 'Pilih variabel terlebih dahulu';
            }
        });

        // Handle form submission
        document.getElementById('what-if-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const kecamatan = document.getElementById('kecamatan-select').value;
            if (!kecamatan) {
                alert('Silakan pilih kecamatan terlebih dahulu');
                return;
            }

            if (!currentPropertyData || Object.keys(currentPropertyData).length === 0) {
                alert('Silakan pilih kecamatan dan tunggu data properti dimuat terlebih dahulu');
                return;
            }

            const variable = document.getElementById('variable-select').value;
            const newValue = parseFloat(document.getElementById('new-value').value);

            if (!variable || isNaN(newValue)) {
                alert('Silakan lengkapi semua field dengan nilai yang valid');
                return;
            }

            if (newValue <= 0) {
                alert('Nilai baru harus lebih besar dari 0');
                return;
            }

            // Show loading state
            const btnAnalyze = document.getElementById('btn-analyze');
            const originalText = btnAnalyze.innerHTML;
            btnAnalyze.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghitung...';
            btnAnalyze.disabled = true;

            // AJAX request to calculate what-if analysis
            const formData = new FormData();
            formData.append('action', 'calculate_whatif');
            formData.append('kecamatan', kecamatan);
            formData.append('variable', variable);
            formData.append('new_value', newValue);
            formData.append('current_price', currentPropertyData.price);
            formData.append('current_luas_tanah', currentPropertyData.luas_tanah);
            formData.append('current_luas_bangunan', currentPropertyData.luas_bangunan);

            fetch('what-if.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                console.log('Result from server:', result);
                
                // Update comparison section
                document.getElementById('comparison-price-before').textContent = formatRupiah(currentPropertyData.price);
                document.getElementById('comparison-price-after').textContent = formatRupiah(result.price);
                document.getElementById('comparison-price-change').textContent = formatRupiah(result.price - currentPropertyData.price);
                document.getElementById('comparison-price-change').className = 
                    comparison-value ${result.price >= currentPropertyData.price ? 'text-success' : 'text-danger'};

                // Display results
                document.getElementById('result-price').textContent = formatRupiah(result.price);
                document.getElementById('result-price-text').textContent = formatRupiah(result.price);
                document.getElementById('result-luas-tanah').textContent = formatNumber(result.luas_tanah);
                document.getElementById('result-luas-bangunan').textContent = formatNumber(result.luas_bangunan);

                // Display changes
                displayChange('price-change', result.price_change_pct);
                displayChange('tanah-change', result.luas_tanah_change_pct);
                displayChange('bangunan-change', result.luas_bangunan_change_pct);

                // Generate interpretation
                generateInterpretation(variable, newValue, result);

                // Show results with animation
                const hasilElement = document.getElementById('hasil-analisis');
                hasilElement.classList.remove('d-none');
                hasilElement.style.animation = 'fadeIn 0.5s ease forwards';
                
                // Scroll to results
                setTimeout(() => {
                    hasilElement.scrollIntoView({ behavior: 'smooth' });
                }, 300);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghitung analisis: ' + error.message);
            })
            .finally(() => {
                // Restore button state
                btnAnalyze.innerHTML = originalText;
                btnAnalyze.disabled = false;
            });
        });

        function displayChange(elementId, changePercent) {
            const element = document.getElementById(elementId);
            const absChange = Math.abs(changePercent).toFixed(2);
            
            if (changePercent > 0) {
                element.className = 'change-badge bg-success text-white';
                element.innerHTML = '<i class="fas fa-arrow-up"></i> +' + absChange + '%';
            } else if (changePercent < 0) {
                element.className = 'change-badge bg-danger text-white';
                element.innerHTML = '<i class="fas fa-arrow-down"></i> ' + absChange + '%';
            } else {
                element.className = 'change-badge bg-secondary text-white';
                element.innerHTML = '<i class="fas fa-minus"></i> 0%';
            }
        }

        function generateInterpretation(variable, newValue, result) {
            const interpretationElement = document.getElementById('interpretation-text');
            let text = '';

            const priceDiff = result.price - currentPropertyData.price;
            const tanahDiff = result.luas_tanah - currentPropertyData.luas_tanah;
            const bangunanDiff = result.luas_bangunan - currentPropertyData.luas_bangunan;

            switch(variable) {
                case 'price':
                    text = Dengan ${priceDiff > 0 ? 'meningkatkan' : 'menurunkan'} harga menjadi <strong>${formatRupiah(newValue)}</strong>:;
                    text += <br>• Luas Tanah diproyeksikan: <strong>${formatNumber(result.luas_tanah)} m²</strong> (${tanahDiff > 0 ? '+' : ''}${formatNumber(tanahDiff)} m²);
                    text += <br>• Luas Bangunan diproyeksikan: <strong>${formatNumber(result.luas_bangunan)} m²</strong> (${bangunanDiff > 0 ? '+' : ''}${formatNumber(bangunanDiff)} m²);
                    text += <br><br>Perhitungan didasarkan pada skala proporsional terhadap perubahan harga.;
                    break;
                    
                case 'luas_tanah':
                    text = Dengan mengubah luas tanah menjadi <strong>${formatNumber(newValue)} m²</strong>:;
                    text += <br>• Harga diproyeksikan: <strong>${formatRupiah(result.price)}</strong> (${priceDiff > 0 ? '+' : ''}${formatRupiah(priceDiff)});
                    text += <br>• Luas Bangunan diproyeksikan: <strong>${formatNumber(result.luas_bangunan)} m²</strong> (${bangunanDiff > 0 ? '+' : ''}${formatNumber(bangunanDiff)} m²);
                    text += <br><br>Perhitungan didasarkan pada harga per m² tanah dan rasio luas tanah:bangunan properti saat ini.;
                    break;
                    
                case 'luas_bangunan':
                    text = Dengan mengubah luas bangunan menjadi <strong>${formatNumber(newValue)} m²</strong>:;
                    text += <br>• Harga diproyeksikan: <strong>${formatRupiah(result.price)}</strong> (${priceDiff > 0 ? '+' : ''}${formatRupiah(priceDiff)});
                    text += <br>• Luas Tanah diproyeksikan: <strong>${formatNumber(result.luas_tanah)} m²</strong> (${tanahDiff > 0 ? '+' : ''}${formatNumber(tanahDiff)} m²);
                    text += <br><br>Perhitungan didasarkan pada harga per m² bangunan dan rasio luas tanah:bangunan properti saat ini.;
                    break;
            }

            interpretationElement.innerHTML = text;
        }

        // Add scroll animation
        document.addEventListener('DOMContentLoaded', function() {
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
            
            fadeElements.forEach(element => {
                element.style.opacity = "0";
                element.style.transform = "translateY(20px)";
                element.style.transition = "opacity 0.6s ease, transform 0.6s ease";
            });
            
            window.addEventListener('scroll', fadeInOnScroll);
            fadeInOnScroll();
        });
    </script>
</body>
</html>