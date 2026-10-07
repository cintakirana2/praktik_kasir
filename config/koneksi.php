<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "praktik_kasir";

/** @var mysqli $conn */
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>