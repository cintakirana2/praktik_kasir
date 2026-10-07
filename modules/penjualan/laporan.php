<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../../login.php");
    exit();
}

// PROSES BATAL TRANSAKSI (GANTI STATUS & KEMBALIKAN STOK)
if (isset($_GET['batal_id'])) {
    $id_batal = intval($_GET['batal_id']);

    // 1. Cek apakah transaksi ini statusnya masih 'Selesai'
    $cek_trx = mysqli_query($conn, "SELECT status FROM penjualan WHERE penjualan_id = $id_batal");
    $dt_trx  = mysqli_fetch_assoc($cek_trx);

    if ($dt_trx && $dt_trx['status'] == 'Selesai') {
        // 2. Ambil data detail barang untuk mengembalikan stok
        $q_detail = mysqli_query($conn, "SELECT produk_id, jumlah_produk FROM detail_penjualan WHERE penjualan_id = $id_batal");

        while ($detail = mysqli_fetch_assoc($q_detail)) {
            $p_id = $detail['produk_id'];
            $qty  = $detail['jumlah_produk'];
            mysqli_query($conn, "UPDATE produk SET stok = stok + $qty WHERE produk_id = $p_id");
        }

        // 3. Ubah status penjualan menjadi 'Batal' (bukan di-DELETE)
        mysqli_query($conn, "UPDATE penjualan SET status = 'Batal' WHERE penjualan_id = $id_batal");

        echo "<script>alert('Transaksi berhasil dibatalkan dan stok telah dikembalikan!'); window.location='index.php';</script>";
        exit();
    }
}

// Default filter: tgl awal & akhir bulan ini
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// Query Laporan (Termasuk mengambil p.status)
$query = "SELECT 
            p.penjualan_id,
            p.tgl_penjualan,
            p.total_harga,
            p.status,
            pel.nama_pelanggan,
            u.nama_kasir,
            GROUP_CONCAT(CONCAT(pr.nama_produk, ' (', dp.jumlah_produk, 'x)') SEPARATOR ', ') AS rincian_barang
          FROM penjualan p
          LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id 
          LEFT JOIN user u ON p.user_id = u.user_id
          LEFT JOIN detail_penjualan dp ON p.penjualan_id = dp.penjualan_id
          LEFT JOIN produk pr ON dp.produk_id = pr.produk_id
          WHERE p.tgl_penjualan BETWEEN '$tgl_mulai' AND '$tgl_selesai'
          GROUP BY p.penjualan_id
          ORDER BY p.penjualan_id DESC
          LIMIT 15";

$result = mysqli_query($conn, $query);

$total_transaksi = mysqli_num_rows($result);
$total_omset = 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Kasir UKK</title>
    <link rel="stylesheet" href="/praktik_serkom/assets/css/bootstrap.min.css">
    <style>
        .fs-7 {
            font-size: 0.8rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                border: none !important;
                shadow: none !important;
            }
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-black shadow-sm mb-4 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../index.php">🛒 KASIR UKK</a>
            <div class="navbar-nav me-auto">
                <a class="nav-link active" href="index.php">Penjualan</a>
            </div>
            <a href="../../index.php" class="btn btn-outline-light btn-sm fs-7">← Dashboard</a>
        </div>
    </nav>

    <div class="container mb-5">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Laporan Penjualan Lengkap</h5>
                <small class="text-muted">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)); ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai)); ?></small>
            </div>
            <button onclick="window.print()" class="btn btn-secondary btn-sm fs-7 fw-semibold no-print">
                🖨️ Cetak Laporan
            </button>
        </div>
        <div class="card border-0 shadow-sm rounded-3 mb-3 no-print">
            <div class="card-body py-2 px-3">
                <form action="" method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label class="form-label fs-7 fw-semibold mb-0">Dari tgl:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="tgl_mulai" class="form-control form-control-sm fs-7" value="<?= $tgl_mulai; ?>" required>
                    </div>
                    <div class="col-auto">
                        <label class="form-label fs-7 fw-semibold mb-0">Sampai:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="tgl_selesai" class="form-control form-control-sm fs-7" value="<?= $tgl_selesai; ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm fs-7 fw-semibold">Filter Data</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-primary fs-7">
                            <tr>
                                <th class="text-center py-2">No</th>
                                <th class="py-2">No. Nota</th>
                                <th class="py-2">Tgl</th>
                                <th class="py-2">Kasir</th>
                                <th class="py-2">Pelanggan</th>
                                <th class="py-2">Barang Dibeli</th>
                                <th class="py-2 text-end">Total Harga</th>
                                <th class="text-center py-2">Status</th>
                                <th class="text-center py-2 no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            <?php if ($total_transaksi > 0): ?>
                                <?php
                                $no = 1;
                                $data_rows = [];
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $data_rows[] = $row;
                                    // Hitung total omset HANYA dari transaksi yang statusnya 'Selesai'
                                    if (($row['status'] ?? 'Selesai') == 'Selesai') {
                                        $total_omset += $row['total_harga'];
                                    }
                                }
                                foreach ($data_rows as $row):
                                    $status = $row['status'] ?? 'Selesai';
                                ?>
                                    <tr class="<?= $status == 'Batal' ? 'table-secondary text-muted' : ''; ?>">
                                        <td class="text-center py-2"><?= $no++; ?></td>
                                        <td class="fw-semibold py-2">#TRX-<?= $row['penjualan_id']; ?></td>
                                        <td class="py-2"><?= date('d/m/Y', strtotime($row['tgl_penjualan'])); ?></td>
                                        <td class="py-2"><?= htmlspecialchars($row['nama_kasir'] ?? 'Kasir'); ?></td>
                                        <td class="py-2"><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Umum'); ?></td>
                                        <td class="py-2 text-wrap"><?= htmlspecialchars($row['rincian_barang'] ?? '-'); ?></td>
                                        <td class="fw-semibold text-end py-2">
                                            <?= $status == 'Batal' ? '<s>Rp ' . number_format($row['total_harga'], 0, ',', '.') . '</s>' : 'Rp ' . number_format($row['total_harga'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="text-center py-2">
                                            <?php if ($status == 'Selesai'): ?>
                                                <span class="badge bg-success fw-normal">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger fw-normal">Batal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center py-2 no-print">
                                            <?php if ($status == 'Selesai'): ?>
                                                <a href="?batal_id=<?= $row['penjualan_id']; ?>" class="btn btn-outline-danger btn-sm py-0 px-2 fs-7" onclick="return confirm('Membatalkan transaksi #TRX-<?= $row['penjualan_id']; ?>? Stok barang akan dikembalikan.')">
                                                    Batalkan
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">Tidak ada data transaksi pada periode tanggal ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fs-7 fw-bold">
                            <tr>
                                <td colspan="6" class="text-end py-2">TOTAL OMSET (Hanya Transaksi Selesai):</td>
                                <td class="text-end text-primary py-2">Rp <?= number_format($total_omset, 0, ',', '.'); ?></td>
                                <td colspan="2" class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="/praktik_serkom/assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>