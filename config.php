<?php
$host = "localhost";
$username = "root"; // sesuaikan dengan username database Anda
$password = ""; // sesuaikan dengan password database Anda
$database = "db_propertisurabaya";

// Koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>