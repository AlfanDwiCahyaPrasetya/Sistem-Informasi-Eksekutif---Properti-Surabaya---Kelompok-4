<?php
include 'config.php';

// Fungsi untuk mengkapitalisasi huruf pertama setiap kata
function capitalizeKecamatan($kecamatan) {
    if (empty($kecamatan)) return $kecamatan;
    $kecamatan = strtolower($kecamatan); // Pastikan semua huruf kecil dulu
    $kecamatan = ucwords($kecamatan); // Kapitalisasi huruf pertama setiap kata
    return $kecamatan;
}

// Query untuk mengambil data agregat per kecamatan
$query_kecamatan = "SELECT DISTINCT Kecamatan FROM properti_surabaya ORDER BY Kecamatan";
$result_kecamatan = mysqli_query($conn, $query_kecamatan);
$kecamatan_list = [];
while ($row = mysqli_fetch_assoc($result_kecamatan)) {
    $kecamatan_list[] = capitalizeKecamatan($row['Kecamatan']);
}

// Data untuk chart - Harga Rata-rata per Kecamatan
$query_price = "SELECT Kecamatan, AVG(Price) as avg_price, COUNT(*) as jumlah 
                FROM properti_surabaya 
                GROUP BY Kecamatan 
                ORDER BY Kecamatan";
$result_price = mysqli_query($conn, $query_price);
$data_price = [];
while ($row = mysqli_fetch_assoc($result_price)) {
    $row['Kecamatan'] = capitalizeKecamatan($row['Kecamatan']);
    $data_price[] = $row;
}

// Data untuk chart - Rata-rata Luas Tanah per Kecamatan
$query_luas_tanah = "SELECT Kecamatan, AVG(`Luas Tanah`) as avg_luas_tanah 
                     FROM properti_surabaya 
                     GROUP BY Kecamatan 
                     ORDER BY Kecamatan";
$result_luas_tanah = mysqli_query($conn, $query_luas_tanah);
$data_luas_tanah = [];
while ($row = mysqli_fetch_assoc($result_luas_tanah)) {
    $row['Kecamatan'] = capitalizeKecamatan($row['Kecamatan']);
    $data_luas_tanah[] = $row;
}

// Data untuk chart - Rata-rata Luas Bangunan per Kecamatan
$query_luas_bangunan = "SELECT Kecamatan, AVG(`Luas Bangunan`) as avg_luas_bangunan 
                        FROM properti_surabaya 
                        GROUP BY Kecamatan 
                        ORDER BY Kecamatan";
$result_luas_bangunan = mysqli_query($conn, $query_luas_bangunan);
$data_luas_bangunan = [];
while ($row = mysqli_fetch_assoc($result_luas_bangunan)) {
    $row['Kecamatan'] = capitalizeKecamatan($row['Kecamatan']);
    $data_luas_bangunan[] = $row;
}

// Data untuk drilldown level 2 (Kamar Tidur) - DIPERBAIKI: tambah luas tanah dan bangunan
$query_kamar_tidur = "SELECT Kecamatan, `Kamar Tidur`, 
                             COUNT(*) as jumlah, 
                             AVG(Price) as avg_price,
                             AVG(`Luas Tanah`) as avg_luas_tanah,
                             AVG(`Luas Bangunan`) as avg_luas_bangunan
                      FROM properti_surabaya 
                      GROUP BY Kecamatan, `Kamar Tidur`
                      ORDER BY Kecamatan, `Kamar Tidur`";
$result_kamar_tidur = mysqli_query($conn, $query_kamar_tidur);
$data_kamar_tidur = [];
while ($row = mysqli_fetch_assoc($result_kamar_tidur)) {
    $row['Kecamatan'] = capitalizeKecamatan($row['Kecamatan']);
    $data_kamar_tidur[$row['Kecamatan']][] = $row;
}

// Data untuk drilldown level 3 (Sertifikat) - DIPERBAIKI: tambah luas tanah dan bangunan
$query_sertifikat = "SELECT Kecamatan, `Kamar Tidur`, Sertifikat, 
                            COUNT(*) as jumlah, 
                            AVG(Price) as avg_price,
                            AVG(`Luas Tanah`) as avg_luas_tanah,
                            AVG(`Luas Bangunan`) as avg_luas_bangunan
                     FROM properti_surabaya 
                     GROUP BY Kecamatan, `Kamar Tidur`, Sertifikat
                     ORDER BY Kecamatan, `Kamar Tidur`, Sertifikat";
$result_sertifikat = mysqli_query($conn, $query_sertifikat);
$data_sertifikat = [];
while ($row = mysqli_fetch_assoc($result_sertifikat)) {
    $row['Kecamatan'] = capitalizeKecamatan($row['Kecamatan']);
    $key = $row['Kecamatan'] . '_' . $row['Kamar Tidur'];
    $data_sertifikat[$key][] = $row;
}

// Data untuk drilldown level 4 (Kondisi Properti) - SUDAH BENAR
$query_kondisi = "SELECT Kecamatan, `Kamar Tidur`, Sertifikat, `Kondisi Properti`, 
                         COUNT(*) as jumlah, 
                         AVG(Price) as avg_price,
                         AVG(`Luas Tanah`) as avg_luas_tanah, 
                         AVG(`Luas Bangunan`) as avg_luas_bangunan
                  FROM properti_surabaya 
                  GROUP BY Kecamatan, `Kamar Tidur`, Sertifikat, `Kondisi Properti`
                  ORDER BY Kecamatan, `Kamar Tidur`, Sertifikat, `Kondisi Properti`";
$result_kondisi = mysqli_query($conn, $query_kondisi);
$data_kondisi = [];
while ($row = mysqli_fetch_assoc($result_kondisi)) {
    $row['Kecamatan'] = capitalizeKecamatan($row['Kecamatan']);
    $key = $row['Kecamatan'] . '_' . $row['Kamar Tidur'] . '_' . $row['Sertifikat'];
    $data_kondisi[$key][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drilldown Dashboard - Properti Surabaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS tetap sama seperti sebelumnya */
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
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 25px;
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
        }
        
        .chart-title i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            padding: 8px;
            border-radius: 8px;
        }
        
        .tab-content {
            padding: 25px 0;
        }
        
        .nav-tabs {
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
            margin-bottom: 25px;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d !important;
            border: none;
            border-bottom: 3px solid transparent;
            margin: 0 5px;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-tabs .nav-link:hover {
            background-color: rgba(67, 97, 238, 0.05);
            border-bottom: 3px solid rgba(67, 97, 238, 0.3);
            color: var(--primary) !important;
        }
        
        .nav-tabs .nav-link.active {
            background-color: rgba(67, 97, 238, 0.1) !important;
            border-bottom: 3px solid var(--primary);
            color: var(--primary) !important;
            font-weight: 600;
        }
        
        .detail-chart-container {
            display: none;
            margin-top: 20px;
            animation: fadeIn 0.5s ease;
        }
        
        .detail-chart-container.show {
            display: block;
        }
        
        .back-button {
            margin-bottom: 20px;
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
            color: white;
        }
        
        .stats-overview {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .stats-overview h5 {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-overview h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }
        
        .chart-grid {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-bottom: 25px;
        }

        .drilldown-path {
            background: rgba(67, 97, 238, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }

        .drilldown-path .path-item {
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
            font-weight: 500;
        }

        .drilldown-path .path-item:not(:last-child):after {
            content: "›";
            margin-left: 10px;
            color: var(--primary);
            font-weight: bold;
        }

        .level-indicator {
            background: linear-gradient(135deg, var(--success), var(--info));
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
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
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .chart-container {
                height: 350px;
                padding: 20px;
            }
            
            .nav-tabs .nav-link {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .chart-container {
                height: 300px;
                padding: 15px;
            }
            
            .nav-tabs .nav-link {
                padding: 8px 12px;
                font-size: 13px;
            }
            
            .chart-title {
                font-size: 16px;
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
                            <a class="nav-link active" href="drilldown.php">
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
                <div class="page-header fade-in">
                    <h1 class="page-title">
                        <i class="fas fa-chart-line"></i> Drilldown Analisis Properti Surabaya
                    </h1>
                </div>

                <!-- Overview Stats -->
                <div class="stats-overview fade-in delay-1">
                    <h5><i class="fas fa-chart-pie"></i> Ringkasan Analisis</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <h3><?php echo count($data_price); ?></h3>
                            <small>Kecamatan Teranalisis</small>
                        </div>
                        <div class="col-md-3">
                            <h3>
                                <?php 
                                $avg_price = array_reduce($data_price, function($carry, $item) {
                                    return $carry + $item['avg_price'];
                                }, 0) / count($data_price);
                                echo 'Rp ' . number_format($avg_price / 1000000, 1) . 'M';
                                ?>
                            </h3>
                            <small>Harga Rata-rata</small>
                        </div>
                        <div class="col-md-3">
                            <h3>
                                <?php 
                                $avg_land = array_reduce($data_luas_tanah, function($carry, $item) {
                                    return $carry + $item['avg_luas_tanah'];
                                }, 0) / count($data_luas_tanah);
                                echo number_format($avg_land, 0) . ' m²';
                                ?>
                            </h3>
                            <small>Luas Tanah Rata-rata</small>
                        </div>
                        <div class="col-md-3">
                            <h3>
                                <?php 
                                $avg_building = array_reduce($data_luas_bangunan, function($carry, $item) {
                                    return $carry + $item['avg_luas_bangunan'];
                                }, 0) / count($data_luas_bangunan);
                                echo number_format($avg_building, 0) . ' m²';
                                ?>
                            </h3>
                            <small>Luas Bangunan Rata-rata</small>
                        </div>
                    </div>
                </div>

                <!-- Drilldown Path -->
                <div id="drilldownPath" class="drilldown-path" style="display: none;">
                    <div class="level-indicator">
                        <i class="fas fa-sitemap"></i> Level <span id="currentLevel">1</span>/4
                    </div>
                    <div id="pathContent"></div>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs fade-in delay-2" id="drilldownTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="surabaya-tab" data-bs-toggle="tab" data-bs-target="#surabaya" type="button" role="tab">
                            <i class="fas fa-city"></i> Surabaya
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="price-tab" data-bs-toggle="tab" data-bs-target="#price" type="button" role="tab">
                            <i class="fas fa-dollar-sign"></i> Harga
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="land-tab" data-bs-toggle="tab" data-bs-target="#land" type="button" role="tab">
                            <i class="fas fa-map"></i> Luas Tanah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="building-tab" data-bs-toggle="tab" data-bs-target="#building" type="button" role="tab">
                            <i class="fas fa-building"></i> Luas Bangunan
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content fade-in delay-3" id="drilldownTabsContent">
                    <!-- Tab Surabaya (Semua Chart) -->
                    <div class="tab-pane fade show active" id="surabaya" role="tabpanel">
                        <div class="chart-grid">
                            <div class="chart-container">
                                <div class="chart-title">
                                    <i class="fas fa-money-bill-wave"></i> Rata-rata Harga Properti per Kecamatan (Level 1)
                                </div>
                                <canvas id="chartPriceSurabaya"></canvas>
                            </div>
                            
                            <div class="chart-container">
                                <div class="chart-title">
                                    <i class="fas fa-ruler-combined"></i> Rata-rata Luas Tanah per Kecamatan (Level 1)
                                </div>
                                <canvas id="chartLandSurabaya"></canvas>
                            </div>
                            
                            <div class="chart-container">
                                <div class="chart-title">
                                    <i class="fas fa-home"></i> Rata-rata Luas Bangunan per Kecamatan (Level 1)
                                </div>
                                <canvas id="chartBuildingSurabaya"></canvas>
                            </div>
                        </div>

                        <!-- Detail Drilldown untuk Surabaya -->
                        <div id="detailSurabaya" class="detail-chart-container">
                            <button class="btn btn-secondary back-button" onclick="hideDetail('Surabaya')">
                                <i class="fas fa-arrow-left"></i> Kembali ke Overview
                            </button>
                            <div class="chart-container">
                                <div class="chart-title" id="detailTitleSurabaya"></div>
                                <canvas id="chartDetailSurabaya"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Harga -->
                    <div class="tab-pane fade" id="price" role="tabpanel">
                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-money-bill-wave"></i> Rata-rata Harga Properti per Kecamatan (Level 1)
                            </div>
                            <canvas id="chartPrice"></canvas>
                        </div>

                        <!-- Detail Drilldown untuk Harga -->
                        <div id="detailPrice" class="detail-chart-container">
                            <button class="btn btn-secondary back-button" onclick="hideDetail('Price')">
                                <i class="fas fa-arrow-left"></i> Kembali ke Chart Harga
                            </button>
                            <div class="chart-container">
                                <div class="chart-title" id="detailTitlePrice"></div>
                                <canvas id="chartDetailPrice"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Luas Tanah -->
                    <div class="tab-pane fade" id="land" role="tabpanel">
                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-ruler-combined"></i> Rata-rata Luas Tanah per Kecamatan (m²) - Level 1
                            </div>
                            <canvas id="chartLand"></canvas>
                        </div>

                        <!-- Detail Drilldown untuk Luas Tanah -->
                        <div id="detailLand" class="detail-chart-container">
                            <button class="btn btn-secondary back-button" onclick="hideDetail('Land')">
                                <i class="fas fa-arrow-left"></i> Kembali ke Chart Luas Tanah
                            </button>
                            <div class="chart-container">
                                <div class="chart-title" id="detailTitleLand"></div>
                                <canvas id="chartDetailLand"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Luas Bangunan -->
                    <div class="tab-pane fade" id="building" role="tabpanel">
                        <div class="chart-container">
                            <div class="chart-title">
                                <i class="fas fa-home"></i> Rata-rata Luas Bangunan per Kecamatan (m²) - Level 1
                            </div>
                                <canvas id="chartBuilding"></canvas>
                        </div>

                        <!-- Detail Drilldown untuk Luas Bangunan -->
                        <div id="detailBuilding" class="detail-chart-container">
                            <button class="btn btn-secondary back-button" onclick="hideDetail('Building')">
                                <i class="fas fa-arrow-left"></i> Kembali ke Chart Luas Bangunan
                            </button>
                            <div class="chart-container">
                                <div class="chart-title" id="detailTitleBuilding"></div>
                                <canvas id="chartDetailBuilding"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data dari PHP
        const dataPrice = <?php echo json_encode($data_price); ?>;
        const dataLuasTanah = <?php echo json_encode($data_luas_tanah); ?>;
        const dataLuasBangunan = <?php echo json_encode($data_luas_bangunan); ?>;
        const dataKamarTidur = <?php echo json_encode($data_kamar_tidur); ?>;
        const dataSertifikat = <?php echo json_encode($data_sertifikat); ?>;
        const dataKondisi = <?php echo json_encode($data_kondisi); ?>;

        // State untuk drilldown
        let currentLevel = 1;
        let drilldownPath = [];
        let currentDataType = '';

        // Konfigurasi warna yang lebih menarik
        const colors = {
            primary: 'rgba(67, 97, 238, 0.8)',
            secondary: 'rgba(247, 37, 133, 0.8)',
            success: 'rgba(76, 201, 240, 0.8)',
            warning: 'rgba(248, 150, 30, 0.8)',
            info: 'rgba(72, 149, 239, 0.8)',
            gradient1: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            gradient2: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            gradient3: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
        };

        // Chart instances
        let chartInstances = {};

        // Fungsi untuk format Rupiah
        function formatRupiah(angka) {
            if (angka >= 1000000000) {
                return 'Rp ' + (angka / 1000000000).toFixed(1) + 'M';
            } else if (angka >= 1000000) {
                return 'Rp ' + (angka / 1000000).toFixed(1) + 'Jt';
            } else {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
            }
        }

        // Fungsi untuk membuat gradient
        function createGradient(ctx, color1, color2) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }

        // Fungsi untuk update drilldown path
        function updateDrilldownPath() {
            const pathContainer = document.getElementById('drilldownPath');
            const pathContent = document.getElementById('pathContent');
            const currentLevelSpan = document.getElementById('currentLevel');
            
            if (drilldownPath.length > 0) {
                pathContainer.style.display = 'block';
                currentLevelSpan.textContent = currentLevel;
                
                let pathHTML = '';
                drilldownPath.forEach((item, index) => {
                    pathHTML += `<span class="path-item">${item.label}: ${item.value}</span>`;
                });
                pathContent.innerHTML = pathHTML;
            } else {
                pathContainer.style.display = 'none';
            }
        }

        // Fungsi untuk membuat chart - DIPERBAIKI: handle data yang kosong
        function createChart(canvasId, data, type, clickable = false) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            // Filter data yang memiliki nilai valid
            const filteredData = data.filter(item => {
                const value = item.avg_price || item.avg_luas_tanah || item.avg_luas_bangunan || item.value || item.jumlah;
                return value !== null && value !== undefined && !isNaN(parseFloat(value)) && parseFloat(value) > 0;
            });

            if (filteredData.length === 0) {
                console.warn(`No valid data for chart: ${canvasId}, type: ${type}`);
                // Tampilkan pesan bahwa tidak ada data
                ctx.parentElement.innerHTML = `
                    <div class="chart-title">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ${document.getElementById(canvasId.replace('chart', 'detailTitle'))?.innerText || 'Chart'} 
                        (Tidak ada data)
                    </div>
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center text-muted">
                            <i class="fas fa-database fa-3x mb-3"></i>
                            <p>Tidak ada data yang tersedia untuk ditampilkan</p>
                        </div>
                    </div>
                `;
                return;
            }

            const labels = filteredData.map(item => item.Kecamatan || item.label || item.name || `Item ${filteredData.indexOf(item) + 1}`);
            let values, label, color;

            if (type === 'price') {
                values = filteredData.map(item => parseFloat(item.avg_price || item.value || 0));
                label = 'Rata-rata Harga';
                color = colors.primary;
            } else if (type === 'land') {
                values = filteredData.map(item => parseFloat(item.avg_luas_tanah || item.value || item.luas_tanah || 0));
                label = 'Rata-rata Luas Tanah (m²)';
                color = colors.success;
            } else if (type === 'building') {
                values = filteredData.map(item => parseFloat(item.avg_luas_bangunan || item.value || item.luas_bangunan || 0));
                label = 'Rata-rata Luas Bangunan (m²)';
                color = colors.warning;
            } else if (type === 'count') {
                values = filteredData.map(item => parseFloat(item.jumlah || item.value || 0));
                label = 'Jumlah Properti';
                color = colors.info;
            }

            // Destroy existing chart if exists
            if (chartInstances[canvasId]) {
                chartInstances[canvasId].destroy();
            }

            const chartCtx = ctx.getContext('2d');
            const gradient = createGradient(chartCtx, 
                color.replace('0.8', '1'), 
                color.replace('0.8', '0.4')
            );

            chartInstances[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: values,
                        backgroundColor: gradient,
                        borderColor: color.replace('0.8', '1'),
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: clickable ? (evt, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const selectedValue = labels[index];
                            handleDrilldown(selectedValue, type, canvasId);
                        }
                    } : null,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#2d3748',
                            bodyColor: '#4a5568',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (type === 'price') {
                                        label += formatRupiah(context.parsed.y);
                                    } else if (type === 'count') {
                                        label += context.parsed.y + ' properti';
                                    } else {
                                        label += context.parsed.y.toFixed(1) + ' m²';
                                    }
                                    return label;
                                },
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                padding: 10,
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    if (type === 'price') {
                                        return formatRupiah(value);
                                    } else if (type === 'count') {
                                        return value + ' properti';
                                    }
                                    return value + ' m²';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10,
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        // Fungsi untuk handle drilldown
        function handleDrilldown(selectedValue, type, sourceChartId) {
            currentDataType = type;
            
            if (currentLevel === 1) {
                // Level 1 -> Level 2: Kecamatan ke Kamar Tidur
                drilldownPath.push({ label: 'Kecamatan', value: selectedValue });
                showLevel2Data(selectedValue, type, sourceChartId);
            } else if (currentLevel === 2) {
                // Level 2 -> Level 3: Kamar Tidur ke Sertifikat
                drilldownPath.push({ label: 'Kamar Tidur', value: selectedValue });
                showLevel3Data(selectedValue, type, sourceChartId);
            } else if (currentLevel === 3) {
                // Level 3 -> Level 4: Sertifikat ke Kondisi Properti
                drilldownPath.push({ label: 'Sertifikat', value: selectedValue });
                showLevel4Data(selectedValue, type, sourceChartId);
            }
            
            currentLevel++;
            updateDrilldownPath();
        }

        // Fungsi untuk menampilkan data Level 2 (Kamar Tidur) - DIPERBAIKI
        function showLevel2Data(kecamatan, type, sourceChartId) {
            const data = dataKamarTidur[kecamatan] || [];
            
            const chartData = data.map(item => {
                let value;
                if (type === 'price') {
                    value = item.avg_price;
                } else if (type === 'land') {
                    value = item.avg_luas_tanah;
                } else if (type === 'building') {
                    value = item.avg_luas_bangunan;
                } else {
                    value = item.jumlah;
                }
                
                return {
                    label: `${item['Kamar Tidur']} Kamar`,
                    value: value,
                    jumlah: item.jumlah,
                    avg_luas_tanah: item.avg_luas_tanah,
                    avg_luas_bangunan: item.avg_luas_bangunan,
                    avg_price: item.avg_price
                };
            });

            showDetailChart(kecamatan, 'Kamar Tidur', chartData, type, sourceChartId);
        }

        // Fungsi untuk menampilkan data Level 3 (Sertifikat) - DIPERBAIKI
        function showLevel3Data(kamarTidur, type, sourceChartId) {
            const kecamatan = drilldownPath[0].value;
            const key = `${kecamatan}_${kamarTidur.split(' ')[0]}`;
            const data = dataSertifikat[key] || [];
            
            const chartData = data.map(item => {
                let value;
                if (type === 'price') {
                    value = item.avg_price;
                } else if (type === 'land') {
                    value = item.avg_luas_tanah;
                } else if (type === 'building') {
                    value = item.avg_luas_bangunan;
                } else {
                    value = item.jumlah;
                }
                
                return {
                    label: item.Sertifikat,
                    value: value,
                    jumlah: item.jumlah,
                    avg_luas_tanah: item.avg_luas_tanah,
                    avg_luas_bangunan: item.avg_luas_bangunan,
                    avg_price: item.avg_price
                };
            });

            showDetailChart(`${kecamatan} - ${kamarTidur}`, 'Sertifikat', chartData, type, sourceChartId);
        }

        // Fungsi untuk menampilkan data Level 4 (Kondisi Properti) - DIPERBAIKI
        function showLevel4Data(sertifikat, type, sourceChartId) {
            const kecamatan = drilldownPath[0].value;
            const kamarTidur = drilldownPath[1].value.split(' ')[0];
            const key = `${kecamatan}_${kamarTidur}_${sertifikat}`;
            const data = dataKondisi[key] || [];
            
            const chartData = data.map(item => {
                let value;
                if (type === 'price') {
                    value = item.avg_price;
                } else if (type === 'land') {
                    value = item.avg_luas_tanah;
                } else if (type === 'building') {
                    value = item.avg_luas_bangunan;
                } else {
                    value = item.jumlah;
                }
                
                return {
                    label: item['Kondisi Properti'],
                    value: value,
                    jumlah: item.jumlah,
                    luas_tanah: item.avg_luas_tanah,
                    luas_bangunan: item.avg_luas_bangunan,
                    avg_price: item.avg_price
                };
            });

            showDetailChart(`${kecamatan} - ${kamarTidur} Kamar - ${sertifikat}`, 'Kondisi Properti', chartData, type, sourceChartId);
        }

        // Fungsi untuk menampilkan detail chart
        function showDetailChart(context, levelType, data, type, sourceChartId) {
            // Tentukan detail container berdasarkan source
            let detailContainer, detailTitle, detailCanvas;
            
            if (sourceChartId.includes('Surabaya')) {
                detailContainer = 'detailSurabaya';
                detailTitle = 'detailTitleSurabaya';
                detailCanvas = 'chartDetailSurabaya';
            } else if (sourceChartId.includes('Price')) {
                detailContainer = 'detailPrice';
                detailTitle = 'detailTitlePrice';
                detailCanvas = 'chartDetailPrice';
            } else if (sourceChartId.includes('Land')) {
                detailContainer = 'detailLand';
                detailTitle = 'detailTitleLand';
                detailCanvas = 'chartDetailLand';
            } else if (sourceChartId.includes('Building')) {
                detailContainer = 'detailBuilding';
                detailTitle = 'detailTitleBuilding';
                detailCanvas = 'chartDetailBuilding';
            }

            // Update title
            let titleText = '';
            let icon = '';
            if (type === 'price') {
                titleText = `Detail Harga berdasarkan ${levelType} - ${context} (Level ${currentLevel + 1})`;
                icon = '<i class="fas fa-money-bill-wave"></i> ';
            } else if (type === 'land') {
                titleText = `Detail Luas Tanah berdasarkan ${levelType} - ${context} (Level ${currentLevel + 1})`;
                icon = '<i class="fas fa-ruler-combined"></i> ';
            } else if (type === 'building') {
                titleText = `Detail Luas Bangunan berdasarkan ${levelType} - ${context} (Level ${currentLevel + 1})`;
                icon = '<i class="fas fa-home"></i> ';
            } else {
                titleText = `Detail Jumlah Properti berdasarkan ${levelType} - ${context} (Level ${currentLevel + 1})`;
                icon = '<i class="fas fa-chart-bar"></i> ';
            }
            document.getElementById(detailTitle).innerHTML = icon + titleText;

            // Sembunyikan chart utama dan tampilkan detail
            document.querySelectorAll('.chart-container').forEach(el => {
                if (!el.parentElement.classList.contains('detail-chart-container')) {
                    el.style.display = 'none';
                }
            });
            document.getElementById(detailContainer).classList.add('show');

            // Buat detail chart
            const chartType = type === 'count' ? 'count' : type;
            createChart(detailCanvas, data, chartType, currentLevel < 4);
        }

        // Fungsi untuk kembali ke chart utama
        function hideDetail(section) {
            // Reset drilldown state
            currentLevel = 1;
            drilldownPath = [];
            currentDataType = '';
            updateDrilldownPath();

            document.getElementById(`detail${section}`).classList.remove('show');
            document.querySelectorAll('.chart-container').forEach(el => {
                if (!el.parentElement.classList.contains('detail-chart-container')) {
                    el.style.display = 'block';
                }
            });
        }

        // Inisialisasi semua chart
        window.addEventListener('load', function() {
            // Tab Surabaya
            createChart('chartPriceSurabaya', dataPrice, 'price', true);
            createChart('chartLandSurabaya', dataLuasTanah, 'land', true);
            createChart('chartBuildingSurabaya', dataLuasBangunan, 'building', true);

            // Tab Individual
            createChart('chartPrice', dataPrice, 'price', true);
            createChart('chartLand', dataLuasTanah, 'land', true);
            createChart('chartBuilding', dataLuasBangunan, 'building', true);
        });

        // Animasi scroll
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