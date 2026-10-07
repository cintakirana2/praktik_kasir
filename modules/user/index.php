<?php
require_once '../../config/koneksi.php';
session_start();

$pesan = "";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
    alert('Akses Ditolak! Halaman ini hanya untuk Admin.');
    window.location='../../index.php';
    </script>";
    exit();
}

// 1. PROSES HAPUS USER
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM user WHERE user_id = $id")) {
        echo "<script>alert('User berhasil dihapus!'); window.location='index.php';</script>";
    } else {
        $pesan = "Gagal menghapus user: " . mysqli_error($conn);
        $status = "danger";
    }
}

// 2. PROSES SIMPAN (TAMBAH / EDIT)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kasir = mysqli_real_escape_string($conn, $_POST['nama_kasir']);
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $role       = mysqli_real_escape_string($conn, $_POST['role']);
    $password   = $_POST['password'];

    if (!empty($_POST['user_id'])) {
        $id = intval($_POST['user_id']);
        // Jika password diisi, encrypt dengan md5(). Jika kosong, tidak usah diupdate.
        if (!empty($password)) {
            $pass_md5 = md5($password);
            $query = "UPDATE user SET nama_kasir='$nama_kasir', username='$username', password='$pass_md5', role='$role' WHERE user_id=$id";
        } else {
            $query = "UPDATE user SET nama_kasir='$nama_kasir', username='$username', role='$role' WHERE user_id=$id";
        }
        $msg = "Data user berhasil diperbarui!";
    } else {
        // Enkripsi MD5 saat tambah user baru
        $pass_md5 = md5($password);
        $query = "INSERT INTO user (nama_kasir, username, password, role) VALUES ('$nama_kasir', '$username', '$pass_md5', '$role')";
        $msg = "User baru berhasil ditambahkan!";
    }

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('$msg'); window.location='index.php';</script>";
        exit();
    } else {
        $pesan = "Gagal menyimpan data: " . mysqli_error($conn);
        $status = "danger";
    }
}

// LOGIKA PENCARIAN USER
$cari = "";
if (isset($_GET['cari'])) {
    $cari = mysqli_real_escape_string($conn, $_GET['cari']);
    $result_user = mysqli_query($conn, "SELECT * FROM user WHERE nama_kasir LIKE '%$cari%' OR username LIKE '%$cari%' ORDER BY user_id DESC");
} else {
    $result_user = mysqli_query($conn, "SELECT * FROM user ORDER BY user_id DESC");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Kasir UKK</title>
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
                <h4 class="fw-bold mb-0">Kelola Data User / Kasir</h4>
                <small class="text-muted">Master data akun pengguna sistem</small>
            </div>
            <button type="button" class="btn btn-success fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="resetForm()">
                + Tambah User Baru
            </button>
        </div>

        <!-- FORM PENCARIAN USER -->
        <div class="row mb-3">
            <div class="col-md-5">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama pengguna atau username..." value="<?= htmlspecialchars($cari); ?>">
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
                                <th width="5%" class="text-center">No</th>
                                <th width="35%">Nama Pengguna</th>
                                <th width="25%">Username</th>
                                <th width="15%" class="text-center">Role</th>
                                <th width="20%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_user) > 0): ?>
                                <?php $no = 1;
                                while ($row = mysqli_fetch_assoc($result_user)): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_kasir']); ?></td>
                                        <td><?= htmlspecialchars($row['username']); ?></td>
                                        <td class="text-center">
                                            <?php if (($row['role'] ?? 'kasir') == 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Kasir</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm me-1"
                                                onclick="editUser(<?= $row['user_id']; ?>, '<?= addslashes($row['nama_kasir']); ?>', '<?= addslashes($row['username']); ?>', '<?= $row['role'] ?? 'kasir'; ?>')">
                                                Edit
                                            </button>
                                            <a href="index.php?aksi=hapus&id=<?= $row['user_id']; ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data user.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FORM -->
    <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalUserLabel">Tambah User Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="user_id">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Pengguna / Kasir</label>
                            <input type="text" name="nama_kasir" id="nama_kasir" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Contoh: budi123" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role / Hak Akses</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password">
                            <small class="text-muted" id="passHelp" style="display:none;">*Kosongkan jika tidak ingin mengubah password</small>
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
            document.getElementById('modalUserLabel').innerText = 'Tambah User Baru';
            document.getElementById('user_id').value = '';
            document.getElementById('nama_kasir').value = '';
            document.getElementById('username').value = '';
            document.getElementById('role').value = 'kasir';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passHelp').style.display = 'none';
        }

        function editUser(id, nama, uname, role) {
            document.getElementById('modalUserLabel').innerText = 'Edit Data User';
            document.getElementById('user_id').value = id;
            document.getElementById('nama_kasir').value = nama;
            document.getElementById('username').value = uname;
            document.getElementById('role').value = role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passHelp').style.display = 'block';

            var myModal = new bootstrap.Modal(document.getElementById('modalUser'));
            myModal.show();
        }
    </script>
</body>

</html>