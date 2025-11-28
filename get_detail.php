<?php
include 'config.php';

header('Content-Type: application/json');

if (isset($_GET['kecamatan']) && isset($_GET['type'])) {
    $kecamatan = $_GET['kecamatan'];
    $type = $_GET['type'];
    
    // Query untuk mengambil data detail berdasarkan kecamatan dan tipe
    $query = "SELECT * FROM properti_surabaya WHERE Kecamatan = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $kecamatan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>