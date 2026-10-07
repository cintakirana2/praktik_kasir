<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit();
}

$role_user = $_SESSION['role'] ?? 'kasir';

// 1. PROSES TAMBAH PRODUK DARI MODAL DASHBOARD (Khusus Admin)
if (isset($_POST['simpan_produk_dash']) && $role_user == 'admin') {
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $harga       = floatval($_POST['harga']);
    $stok        = intval($_POST['stok']);

    if (mysqli_query($conn, "INSERT INTO produk (nama_produk, harga, stok) VALUES ('$nama_produk', '$harga', '$stok')")) {
        echo "<script>alert('Produk berhasil ditambahkan!'); window.location='index.php';</script>";
    }
}

// 2. PROSES TAMBAH PELANGGAN DARI MODAL DASHBOARD (Admin & Kasir)
if (isset($_POST['simpan_pelanggan_dash'])) {
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $nomor_telepon  = trim($_POST['nomor_telepon']);

    if (!preg_match('/^[0-9]{9,13}$/', $nomor_telepon)) {
        echo "<script>alert('Nomor telepon harus berupa angka antara 9 sampai 13 digit!'); window.history.back();</script>";
        exit();
    }
    $nomor_telepon = mysqli_real_escape_string($conn, $nomor_telepon);

    if (mysqli_query($conn, "INSERT INTO pelanggan (nama_pelanggan, alamat, nomor_telepon) VALUES ('$nama_pelanggan', '$alamat', '$nomor_telepon')")) {
        echo "<script>alert('Pelanggan berhasil ditambahkan!'); window.location='index.php';</script>";
    }
}

// Single Query Dashboard Stats (Hanya menghitung omset dari transaksi 'Selesai')
$dash = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        (SELECT COUNT(*) FROM produk) AS total_produk,
        (SELECT COUNT(*) FROM pelanggan) AS total_pelanggan,
        (SELECT COUNT(*) FROM penjualan) AS total_transaksi,
        (SELECT IFNULL(SUM(total_harga),0) FROM penjualan WHERE status = 'Selesai') AS total_omset
"));

// Query Ambil Max 10 Transaksi Hari Ini
$tgl_hari_ini = date('Y-m-d');
$query_transaksi_today = "SELECT p.*, pl.nama_pelanggan 
                          FROM penjualan p 
                          LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.pelanggan_id 
                          WHERE p.tgl_penjualan = '$tgl_hari_ini' 
                          ORDER BY p.penjualan_id DESC 
                          LIMIT 10";
$result_today = mysqli_query($conn, $query_transaksi_today);

// Hitung Total Transaksi Hari Ini
$total_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penjualan WHERE tgl_penjualan = '$tgl_hari_ini'"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi Kasir UKK</title>
    <link rel="stylesheet" href="/praktik_serkom/assets/css/bootstrap.min.css">
    <style>
        .fs-7 {
            font-size: 0.8rem;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-black shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🛒 KASIR UKK</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/praktik_kasir/modules/penjualan/transaksi.php">🛒 Transaksi Kasir</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/praktik_kasir/modules/pelanggan/index.php">👥 Data Pelanggan</a>
                    </li>
                    <?php if ($role_user == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/praktik_kasir/modules/produk/index.php">📦 Master Barang/Produk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/praktik_kasir/modules/user/index.php">🔑 Kelola User</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/praktik_kasir/modules/penjualan/laporan.php">📊 Laporan Penjualan</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3 text-white">
                        Halo, <strong><?= htmlspecialchars($_SESSION['nama_kasir'] ?? $_SESSION['username'] ?? 'User'); ?></strong>
                        <span class="badge <?= $role_user == 'admin' ? 'bg-danger' : 'bg-primary'; ?> ms-1">
                            <?= strtoupper($role_user); ?>
                        </span>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm" onclick="return confirm('Apakah Anda yakin ingin logout?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="p-4 mb-4 bg-white rounded-3 shadow-sm border-start border-4 border-primary">
            <h1 class="display-6 fw-bold text-dark">Selamat Datang di Sistem Kasir</h1>
            <p class="text-muted mb-0">
                Anda login sebagai <strong class="text-uppercase"><?= $role_user; ?></strong>
            </p>
        </div>

        <!-- STATISTIK RINGKASAN -->
        <div class="row g-3 mb-4">
            <?php if ($role_user == 'admin'): ?>
                <div class="col-md-3">
                    <div class="card bg-primary text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">TOTAL PRODUK</h6>
                            <h2 class="fw-bold mb-0"><?= $dash['total_produk']; ?></h2>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="<?= $role_user == 'admin' ? 'col-md-3' : 'col-md-6'; ?>">
                <div class="card bg-success text-white h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="card-title text-white-50">DATA PELANGGAN</h6>
                        <h2 class="fw-bold mb-0"><?= $dash['total_pelanggan']; ?></h2>
                    </div>
                </div>
            </div>

            <div class="<?= $role_user == 'admin' ? 'col-md-3' : 'col-md-6'; ?>">
                <div class="card bg-warning text-dark h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="card-title text-black-50">TOTAL TRANSAKSI</h6>
                        <h2 class="fw-bold mb-0"><?= $dash['total_transaksi']; ?></h2>
                    </div>
                </div>
            </div>

            <?php if ($role_user == 'admin'): ?>
                <div class="col-md-3">
                    <div class="card bg-info text-white h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="card-title text-white-50">TOTAL OMSET</h6>
                            <h3 class="fw-bold mb-0">Rp <?= number_format($dash['total_omset'], 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Transaksi Hari Ini</h6>
                        <small class="text-muted">Transaksi terbaru tanggal <?= date('d-m-Y'); ?></small>
                    </div>
                    <?php if ($role_user == 'admin'): ?>
                        <a href="/praktik_kasir/modules/penjualan/laporan.php" class="btn btn-outline-primary btn-sm fw-semibold">
                            Lihat Laporan Lengkap →
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light fs-7 text-secondary">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="20%">ID Transaksi</th>
                                    <th width="35%">Pelanggan</th>
                                    <th width="25%">Total Bayar</th>
                                    <th width="15%" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                <?php if (mysqli_num_rows($result_today) > 0): ?>
                                    <?php $no = 1;
                                    while ($row = mysqli_fetch_assoc($result_today)): ?>
                                        <tr class="<?= ($row['status'] ?? 'Selesai') == 'Batal' ? 'table-secondary text-muted' : ''; ?>">
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td class="fw-bold text-primary">#TRX-<?= sprintf('%04d', $row['penjualan_id']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Umum'); ?></td>
                                            <td class="fw-semibold">
                                                <?= ($row['status'] ?? 'Selesai') == 'Batal' ? '<s>Rp ' . number_format($row['total_harga'], 0, ',', '.') . '</s>' : 'Rp ' . number_format($row['total_harga'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (($row['status'] ?? 'Selesai') == 'Selesai'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Selesai</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Batal</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi untuk hari ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_today > 10): ?>
                    <div class="card-footer bg-light text-center py-2 border-top">
                        <small class="text-muted me-2">Masih ada <strong><?= $total_today - 10; ?></strong> transaksi lainnya hari ini.</small>
                        <?php if ($role_user == 'admin'): ?>
                            <a href="/praktik_kasir/modules/penjualan/laporan.php" class="fw-bold text-decoration-none fs-7">
                                Lihat <?= $total_today - 10; ?> Lainnya →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/praktik_serkom/assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>