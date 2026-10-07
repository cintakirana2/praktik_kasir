<?php
require_once '../../config/koneksi.php';

$pesan = "";
$status = "";

// 1. PROSES HAPUS PRODUK
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM produk WHERE produk_id = $id")) {
        echo "<script>alert('Produk berhasil dihapus!'); window.location='index.php';</script>";
    } else {
        $pesan = "Gagal menghapus produk: " . mysqli_error($conn);
        $status = "danger";
    }
}

// 2. PROSES SIMPAN (TAMBAH / EDIT)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_produk = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $harga       = floatval($_POST['harga']);
    $stok        = intval($_POST['stok']);

    if (!empty($_POST['produk_id'])) {
        // Mode EDIT
        $id = intval($_POST['produk_id']);
        $query = "UPDATE produk SET nama_produk='$nama_produk', harga='$harga', stok='$stok' WHERE produk_id=$id";
        $msg = "Data produk berhasil diperbarui!";
    } else {
        // Mode TAMBAH
        $query = "INSERT INTO produk (nama_produk, harga, stok) VALUES ('$nama_produk', '$harga', '$stok')";
        $msg = "Produk baru berhasil ditambahkan!";
    }

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('$msg'); window.location='index.php';</script>";
    } else {
        $pesan = "Gagal menyimpan data: " . mysqli_error($conn);
        $status = "danger";
    }
}

$cari = "";
if (isset($_GET['cari']) && $_GET['cari'] != "") {
    $cari = mysqli_real_escape_string($conn, $_GET['cari']);
    $result_produk = mysqli_query($conn, "SELECT * FROM produk WHERE nama_produk LIKE '%$cari%' ORDER BY produk_id DESC LIMIT 15");
} else {
    $result_produk = mysqli_query($conn, "SELECT * FROM produk ORDER BY produk_id DESC LIMIT 15");
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Kasir UKK</title>
    <link rel="stylesheet" href="/praktik_serkom/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- NAVBAR SIMPLE -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../index.php">🛒 KASIR UKK</a>
            <div class="navbar-nav">
                <a class="nav-link text-white" href="../../index.php">← Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <!-- HEADER MODUL -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0">Kelola Data Produk</h4>
                <small class="text-muted">Master data barang toko kasir</small>
            </div>
            <button type="button" class="btn btn-success fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#modalProduk" onclick="resetForm()">
                + Tambah Produk Baru
            </button>
        </div>

        <div class="row mb-3">
            <div class="col-md-5">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama produk..." value="<?= htmlspecialchars($cari); ?>">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                    <?php if ($cari != ""): ?>
                        <a href="index.php" class="btn btn-secondary btn-sm px-3">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- ALERT ERROR -->
        <?php if ($pesan != ""): ?>
            <div class="alert alert-<?= $status; ?> alert-dismissible fade show py-2 small"><?= $pesan; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- TABEL DATA PRODUK -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th>Nama Produk</th>
                                <th>Harga Satuan</th>
                                <th>Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_produk) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result_produk)): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_produk']); ?></td>
                                        <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($row['stok'] <= 5): ?>
                                                <span class="badge bg-danger"><?= $row['stok']; ?> (Menipis)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= $row['stok']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm me-1" 
                                                onclick="editProduk(<?= $row['produk_id']; ?>, '<?= addslashes($row['nama_produk']); ?>', <?= $row['harga']; ?>, <?= $row['stok']; ?>)">
                                                Edit
                                            </button>
                                            <a href="index.php?aksi=hapus&id=<?= $row['produk_id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data produk. Klik tombol "+ Tambah Produk Baru" di atas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FORM TAMBAH / EDIT -->
    <div class="modal fade" id="modalProduk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalProdukLabel">Tambah Produk Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="produk_id" id="produk_id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Produk</label>
                            <input type="text" name="nama_produk" id="nama_produk" class="form-control" placeholder="Contoh: Pensil 2B" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Harga (Rp)</label>
                            <input type="number" step="0.01" name="harga" id="harga" class="form-control" placeholder="Contoh: 3000" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Stok Barang</label>
                            <input type="number" name="stok" id="stok" class="form-control" placeholder="Contoh: 50" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-semibold">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/praktik_serkom/assets/js/bootstrap.bundle.min.js"></script>
    <script>
    function resetForm() {
        document.getElementById('modalProdukLabel').innerText = 'Tambah Produk Baru';
        document.getElementById('produk_id').value = '';
        document.getElementById('nama_produk').value = '';
        document.getElementById('harga').value = '';
        document.getElementById('stok').value = '';
    }

    function editProduk(id, nama, harga, stok) {
        document.getElementById('modalProdukLabel').innerText = 'Edit Data Produk';
        document.getElementById('produk_id').value = id;
        document.getElementById('nama_produk').value = nama;
        document.getElementById('harga').value = harga;
        document.getElementById('stok').value = stok;
        
        var myModal = new bootstrap.Modal(document.getElementById('modalProduk'));
        myModal.show();
    }
    </script>
</body>
</html>