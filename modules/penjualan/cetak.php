<?php
require_once '../../config/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) { header("Location: ../../login.php"); exit(); }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = mysqli_query($conn, "
    SELECT p.*, pel.nama_pelanggan, u.nama_kasir 
    FROM penjualan p
    LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id
    LEFT JOIN user u ON p.user_id = u.user_id
    WHERE p.penjualan_id = '$id'
");
$trx = mysqli_fetch_assoc($query);

// Ambil Data Transaksi & Pelanggan
$q_penjualan = mysqli_query($conn, "SELECT penjualan.*, pelanggan.nama_pelanggan 
                                   FROM penjualan 
                                   LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id 
                                   WHERE penjualan.penjualan_id = $id");
$trx = mysqli_fetch_assoc($q_penjualan);

if (!$trx) {
    echo "Data transaksi tidak ditemukan!";
    exit();
}

// Ambil Detail Barang yang Dibeli
$q_detail = mysqli_query($conn, "SELECT detail_penjualan.*, produk.nama_produk, produk.harga 
                                FROM detail_penjualan 
                                JOIN produk ON detail_penjualan.produk_id = produk.produk_id 
                                WHERE detail_penjualan.penjualan_id = $id");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota #<?= $trx['penjualan_id']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; width: 300px; margin: 0 auto; padding: 10px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
    </style>
</head>
<body onload="window.print()">

    <div class="text-center">
        <h3 style="margin: 0;">TOKOKASIR UKK</h3>
        <p style="margin: 2px 0;">Jl. Pelajar No. 3</p>
        <p style="margin: 0;">No. Nota: #<?= $trx['penjualan_id']; ?></p>
        <p style="margin: 0;">Nama Kasir: <?= htmlspecialchars($trx['nama_kasir'] ?? $_SESSION['nama_kasir'] ?? 'Kasir'); ?></p>
        <p style="margin: 0;">Tgl: <?= date('d/m/Y', strtotime($trx['tgl_penjualan'])); ?></p>
        <p style="margin: 0;">Pelanggan: <?= htmlspecialchars($trx['nama_pelanggan'] ?? 'Umum'); ?></p>
    </div>

    <div class="line"></div>

    <table>
        <?php while ($d = mysqli_fetch_assoc($q_detail)): ?>
            <tr>
                <td colspan="3"><strong><?= htmlspecialchars($d['nama_produk']); ?></strong></td>
            </tr>
            <tr>
                <td><?= $d['jumlah_produk']; ?> x Rp <?= number_format($d['harga'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?= number_format($d['subtotal'], 0, ',', '.'); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td><strong>TOTAL</strong></td>
            <td class="text-right"><strong>Rp <?= number_format($trx['total_harga'], 0, ',', '.'); ?></strong></td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="text-center" style="margin-top: 10px;">
        <p style="margin: 0;">-- Terima Kasih --</p>
        <p style="margin: 0;">Barang yang dibeli tidak dapat ditukar</p>
    </div>

</body>
</html>