<?php
require_once '../../config/koneksi.php';

$pesan = "";
$status = "";

// 1. PROSES HAPUS PELANGGAN
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM pelanggan WHERE pelanggan_id = $id")) {
        echo "<script>alert('Pelanggan berhasil dihapus!'); window.location='index.php';</script>";
    } else {
        $pesan = "Gagal menghapus pelanggan: " . mysqli_error($conn);
        $status = "danger";
    }
}

// 2. PROSES SIMPAN (TAMBAH / EDIT)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $nomor_telepon  = trim($_POST['nomor_telepon']);

    // Cek apakah hanya angka dan panjangnya antara 9 - 13 digit
    if (!preg_match('/^[0-9]{9,13}$/', $nomor_telepon)) {
        echo "<script>
                alert('Gagal! Nomor telepon harus berupa angka 9 sampai 13 digit.');
                window.history.back();
              </script>";
        exit();
    }

    $nomor_telepon = mysqli_real_escape_string($conn, $nomor_telepon);

    // Cek apakah ini mode EDIT atau TAMBAH
    if (!empty($_POST['pelanggan_id'])) {
        // MODE EDIT (UPDATE)
        $id = intval($_POST['pelanggan_id']);
        $query = "UPDATE pelanggan 
                  SET nama_pelanggan='$nama_pelanggan', alamat='$alamat', nomor_telepon='$nomor_telepon' 
                  WHERE pelanggan_id=$id";
        $pesan_sukses = "Data pelanggan berhasil diperbarui!";
    } else {
        // MODE TAMBAH (INSERT)
        $query = "INSERT INTO pelanggan (nama_pelanggan, alamat, nomor_telepon) 
                  VALUES ('$nama_pelanggan', '$alamat', '$nomor_telepon')";
        $pesan_sukses = "Pelanggan baru berhasil ditambahkan!";
    }
    if (mysqli_query($conn, $query)) {
        echo "<script>
                alert('$pesan_sukses'); 
                window.location='index.php';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menyimpan: " . addslashes(mysqli_error($conn)) . "');
                window.history.back();
              </script>";
    }
}

$cari = "";
if (isset($_GET['cari'])) {
    $cari = mysqli_real_escape_string($conn, $_GET['cari']);
    $result_pelanggan = mysqli_query($conn, "SELECT * FROM pelanggan WHERE nama_pelanggan LIKE '%$cari%' ORDER BY pelanggan_id DESC LIMIT 15");
} else {
    $result_pelanggan = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY pelanggan_id DESC LIMIT 15");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - Kasir UKK</title>
    <link rel="stylesheet" href="/praktik_serkom/assets/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-black mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../../index.php">🛒 KASIR UKK</a>
            <div class="navbar-nav">
                <a class="nav-link text-white" href="../../index.php">← Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0">Kelola Data Pelanggan</h4>
                <small class="text-muted">Master data pelanggan toko kasir</small>
            </div>
            <button type="button" class="btn btn-success fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#modalPelanggan" onclick="resetForm()">
                + Tambah Pelanggan
            </button>
        </div>

        <div class="row mb-3">
            <div class="col-md-5">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama pelanggan..." value="<?= htmlspecialchars($cari); ?>">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                    <?php if ($cari != ""): ?>
                        <a href="index.php" class="btn btn-secondary btn-sm px-3">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($pesan != ""): ?>
            <div class="alert alert-<?= $status; ?> alert-dismissible fade show py-2 small"><?= $pesan; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                <th>Nama Pelanggan</th>
                                <th>Alamat</th>
                                <th>No. Telepon</th>
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_pelanggan) > 0): ?>
                                <?php $no = 1;
                                while ($row = mysqli_fetch_assoc($result_pelanggan)): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggan']); ?></td>
                                        <td><?= htmlspecialchars($row['alamat']); ?></td>
                                        <td><?= htmlspecialchars($row['nomor_telepon']); ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm me-1"
                                                onclick="editPelanggan(<?= $row['pelanggan_id']; ?>, '<?= addslashes($row['nama_pelanggan']); ?>', '<?= addslashes($row['alamat']); ?>', '<?= addslashes($row['nomor_telepon']); ?>')">
                                                Edit
                                            </button>
                                            <a href="index.php?aksi=hapus&id=<?= $row['pelanggan_id']; ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data pelanggan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FORM -->
    <div class="modal fade" id="modalPelanggan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalPelangganLabel">Tambah Pelanggan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pelanggan_id" id="pelanggan_id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Pelanggan</label>
                            <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control" placeholder="Contoh: Ahmad" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea name="alamat" id="alamat" class="form-control" rows="2" placeholder="Masukkan alamat lengkap" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">No. Telepon</label>
                            <input type="text" name="nomor_telepon" id="nomor_telepon" class="form-control" placeholder="Contoh: 08123456789" required>
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
            document.getElementById('modalPelangganLabel').innerText = 'Tambah Pelanggan Baru';
            document.getElementById('pelanggan_id').value = '';
            document.getElementById('nama_pelanggan').value = '';
            document.getElementById('alamat').value = '';
            document.getElementById('nomor_telepon').value = '';
        }

        function editPelanggan(id, nama, alamat, telp) {
            document.getElementById('modalPelangganLabel').innerText = 'Edit Data Pelanggan';
            document.getElementById('pelanggan_id').value = id;
            document.getElementById('nama_pelanggan').value = nama;
            document.getElementById('alamat').value = alamat;
            document.getElementById('nomor_telepon').value = telp;

            var myModal = new bootstrap.Modal(document.getElementById('modalPelanggan'));
            myModal.show();
        }
    </script>
</body>

</html>