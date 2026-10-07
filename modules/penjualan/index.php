<?php
require_once '../../config/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) { header("Location: ../../login.php"); exit(); }

$query = "SELECT penjualan.*, pelanggan.nama_pelanggan 
          FROM penjualan 
          LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id 
          ORDER BY penjualan.penjualan_id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penjualan - Kasir UKK</title>
    <link rel="stylesheet" href="/praktik_serkom/assets/css/bootstrap.min.css">
    <style>
        .fs-7 { font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-black mb-4 shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold fs-6" href="../../index.php">🛒 KASIR UKK</a>
            <div class="navbar-nav">
                <a class="nav-link text-white small" href="../../index.php">← Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Riwayat Penjualan</h5>
                <small class="text-muted">Daftar transaksi yang telah selesai</small>
            </div>
            <a href="transaksi.php" class="btn btn-success fw-semibold btn-sm fs-7">
                + Transaksi Baru
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-primary fs-7">
                            <tr>
                                <th width="50" class="text-center py-2">No</th>
                                <th class="py-2">tgl</th>
                                <th class="py-2">Pelanggan</th>
                                <th class="py-2">Total Harga</th>
                                <th width="120" class="text-center py-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="text-center py-2"><?= $no++; ?></td>
                                        <td class="py-2"><?= date('d/m/Y', strtotime($row['tgl_penjualan'])); ?></td>
                                        <td class="fw-semibold py-2"><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Umum'); ?></td>
                                        <td class="fw-bold text-primary py-2">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                        <td class="text-center py-2">
                                            <a href="cetak.php?id=<?= $row['penjualan_id']; ?>" target="_blank" class="btn btn-secondary btn-sm py-0 px-2 fs-7">
                                                🖨️ Cetak Nota
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada riwayat transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="/praktik_serkom/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>