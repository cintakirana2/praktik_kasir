<?php
require_once '../../config/koneksi.php';
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$pesan = "";

// 1. TAMBAH PRODUK KE KERANJANG
if (isset($_POST['tambah_keranjang'])) {
    $produk_id = intval($_POST['produk_id']);
    $jumlah    = intval($_POST['jumlah']);

    $q_produk = mysqli_query($conn, "SELECT * FROM produk WHERE produk_id = $produk_id");
    $dt_produk = mysqli_fetch_assoc($q_produk);

    if ($dt_produk) {
        if ($jumlah > $dt_produk['stok']) {
            $pesan = "Stok tidak mencukupi! Stok tersisa: " . $dt_produk['stok'];
        } else {
            if (isset($_SESSION['keranjang'][$produk_id])) {
                $_SESSION['keranjang'][$produk_id]['jumlah'] += $jumlah;
                $_SESSION['keranjang'][$produk_id]['subtotal'] = $_SESSION['keranjang'][$produk_id]['jumlah'] * $dt_produk['harga'];
            } else {
                $_SESSION['keranjang'][$produk_id] = [
                    'nama_produk' => $dt_produk['nama_produk'],
                    'harga'       => $dt_produk['harga'],
                    'jumlah'      => $jumlah,
                    'subtotal'    => $dt_produk['harga'] * $jumlah
                ];
            }
        }
    }
}

// 2. HAPUS ITEM
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    unset($_SESSION['keranjang'][$id_hapus]);
    header("Location: transaksi.php");
    exit();
}

// 3. BATALKAN TRANSAKSI
if (isset($_GET['batal'])) {
    unset($_SESSION['keranjang']);
    header("Location: transaksi.php");
    exit();
}

// 4. SIMPAN TRANSAKSI
if (isset($_POST['simpan_transaksi'])) {
    $pelanggan_id  = intval($_POST['pelanggan_id']);
    $total_harga   = floatval($_POST['total_harga']);
    $bayar         = floatval($_POST['bayar']);
    $tgl_penjualan = date('Y-m-d');

    if ($bayar < $total_harga) {
        $pesan = "Uang pembayaran kurang!";
    } else if (empty($_SESSION['keranjang'])) {
        $pesan = "Keranjang belanja kosong!";
    } else {
        // Jika pelanggan_id bernilai 0 atau kosong, set jadi NULL (Pelanggan Umum)
        $pelanggan_id = (!empty($_POST['pelanggan_id']) && $_POST['pelanggan_id'] != 0) ? intval($_POST['pelanggan_id']) : "NULL";

        // Query INSERT (perhatikan $pelanggan_id tidak pakai tanda petik tunggal ' ')
        $q_penjualan = "INSERT INTO penjualan (tgl_penjualan, total_harga, pelanggan_id) VALUES ('$tgl_penjualan', '$total_harga',$pelanggan_id)";
        if (mysqli_query($conn, $q_penjualan)) {
            $penjualan_id = mysqli_insert_id($conn);

            foreach ($_SESSION['keranjang'] as $p_id => $item) {
                $qty = $item['jumlah'];
                $sub = $item['subtotal'];

                mysqli_query($conn, "INSERT INTO detail_penjualan (penjualan_id, produk_id, jumlah_produk, subtotal) VALUES ('$penjualan_id', '$p_id', '$qty', '$sub')");
                mysqli_query($conn, "UPDATE produk SET stok = stok - $qty WHERE produk_id = $p_id");
            }

            unset($_SESSION['keranjang']);
            echo "<script>alert('Transaksi Berhasil!'); window.location='index.php';</script>";
            exit();
        } else {
            $pesan = "Gagal memproses transaksi: " . mysqli_error($conn);
        }
    }
}

$produk_list    = mysqli_query($conn, "SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");
$pelanggan_list = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir - UKK</title>
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

    <div class="container mb-4">
        <?php if ($pesan != ""): ?>
            <div class="alert alert-danger alert-dismissible fade show py-1 px-3 small mb-2"><?= $pesan; ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- INPUT PRODUK -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white fw-bold py-2 small">Pilih Barang</div>
                    <div class="card-body p-3">
                        <form action="" method="POST">
                            <div class="mb-2">
                                <label class="form-label fs-7 fw-semibold mb-1">Produk</label>
                                <!-- Input pencarian produk -->
                                <input type="text" class="form-control form-control-sm fs-7" list="listProduk" id="input_produk" placeholder="Ketik nama barang..." onchange="pilihProduk(this)" required autocomplete="off">

                                <!-- Input hidden untuk mengirim produk_id ke PHP -->
                                <input type="hidden" name="produk_id" id="produk_id_hidden">

                                <!-- Datalist untuk menyajikan opsi pencarian -->
                                <datalist id="listProduk">
                                    <?php while ($p = mysqli_fetch_assoc($produk_list)): ?>
                                        <option data-id="<?= $p['produk_id']; ?>" value="<?= htmlspecialchars($p['nama_produk']); ?> (Rp <?= number_format($p['harga'], 0, ',', '.'); ?>) - Stok: <?= $p['stok']; ?>">
                                        <?php endwhile; ?>
                                </datalist>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fs-7 fw-semibold mb-1">Jumlah (Qty)</label>
                                <input type="number" name="jumlah" class="form-control form-control-sm fs-7" value="1" min="1" required>
                            </div>
                            <button type="submit" name="tambah_keranjang" class="btn btn-primary btn-sm w-100 fw-semibold fs-7">+ Tambah</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABEL KERANJANG -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold small">Keranjang Belanja</span>
                        <?php if (!empty($_SESSION['keranjang'])): ?>
                            <a href="transaksi.php?batal=1" class="btn btn-outline-danger btn-sm py-0 px-2 fs-7" onclick="return confirm('Kosongkan keranjang?')">Reset</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-primary fs-7">
                                    <tr>
                                        <th width="40" class="text-center py-2">No</th>
                                        <th class="py-2">Nama Produk</th>
                                        <th class="py-2">Harga</th>
                                        <th width="60" class="text-center py-2">Qty</th>
                                        <th class="py-2">Subtotal</th>
                                        <th width="50" class="text-center py-2">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-7">
                                    <?php
                                    $grand_total = 0;
                                    if (!empty($_SESSION['keranjang'])):
                                        $no = 1;
                                        foreach ($_SESSION['keranjang'] as $p_id => $item):
                                            $grand_total += $item['subtotal'];
                                    ?>
                                            <tr>
                                                <td class="text-center py-1"><?= $no++; ?></td>
                                                <td class="fw-semibold py-1"><?= htmlspecialchars($item['nama_produk']); ?></td>
                                                <td class="py-1">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></td>
                                                <td class="text-center py-1"><?= $item['jumlah']; ?></td>
                                                <td class="fw-bold py-1">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                                <td class="text-center py-1">
                                                    <a href="transaksi.php?hapus=<?= $p_id; ?>" class="btn btn-danger btn-sm py-0 px-2 fs-7">×</a>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3 fs-7">Keranjang masih kosong.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- FOOTER KASIR COMPACT -->
                    <?php if (!empty($_SESSION['keranjang'])): ?>
                        <div class="card-footer bg-white p-3 border-top">
                            <form action="" method="POST">
                                <!-- Input hidden total_harga aktual yang dikirim ke PHP -->
                                <input type="hidden" name="total_harga" id="input_total_harga" value="<?= $grand_total; ?>">
                                <input type="hidden" name="diskon" id="input_diskon" value="0">

                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-5">
                                        <label class="form-label fs-7 fw-semibold mb-1">Pelanggan / Member</label>

                                        <!-- Input pencarian pelanggan/member -->
                                        <input type="text" class="form-control form-control-sm fs-7" list="listPelanggan" id="input_pelanggan" placeholder="Ketik nama atau no telp..." onchange="pilihPelanggan(this, <?= $grand_total; ?>)" autocomplete="off">

                                        <!-- Input hidden untuk mengirim pelanggan_id ke PHP -->
                                        <input type="hidden" name="pelanggan_id" id="pelanggan_id" value="0">

                                        <!-- Datalist untuk menyajikan opsi pencarian -->
                                        <datalist id="listPelanggan">
                                            <option data-id="0" data-member="0" value="-- Non-Member / Biasa (Diskon 0%) --"></option>
                                            <?php
                                            mysqli_data_seek($pelanggan_list, 0);
                                            while ($pel = mysqli_fetch_assoc($pelanggan_list)):
                                            ?>
                                                <option data-id="<?= $pel['pelanggan_id']; ?>" data-member="1" value="<?= htmlspecialchars($pel['nama_pelanggan']); ?> - <?= $pel['nomor_telepon']; ?> (Member 15%)">
                                                </option>
                                            <?php endwhile; ?>
                                        </datalist>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-7 fw-semibold mb-1">Bayar (Rp)</label>
                                        <input type="number" name="bayar" id="bayar" class="form-control form-control-sm fs-7" placeholder="0" oninput="hitungKembalian(<?= $grand_total; ?>)" required>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <small class="text-muted d-block fs-7">Subtotal: <strong id="text-subtotal">Rp <?= number_format($grand_total, 0, ',', '.'); ?></strong></small>
                                        <small class="text-success d-block fs-7">Diskon (15%): <strong id="text-diskon">- Rp 0</strong></small>
                                        <small class="text-muted d-block fs-7">Total Bayar: <strong class="text-primary fs-6" id="text-total">Rp <?= number_format($grand_total, 0, ',', '.'); ?></strong></small>
                                        <small class="text-muted d-block fs-7">Kembali: <strong class="text-success fs-6" id="text-kembalian">Rp 0</strong></small>
                                    </div>
                                </div>
                        </div>

                        <button type="submit" name="simpan_transaksi" class="btn btn-success btn-sm w-100 fw-bold py-2 mt-2 fs-7">Proses Transaksi</button>
                        </form>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <script src="/praktik_serkom/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variabel global untuk menyimpan status member (0 = Biasa, 1 = Member)
        let globalIsMember = "0";

        // Fungsi utama untuk menghitung Diskon, Total, dan Kembalian
        function hitungKembalian(subtotalAwal) {
            // 1. Hitung Diskon (15% jika member)
            let diskon = 0;
            if (globalIsMember === "1") {
                diskon = subtotalAwal * 0.15;
            }

            let totalSetelahDiskon = subtotalAwal - diskon;

            // Update tampilan Diskon & Total Bayar
            document.getElementById('text-diskon').innerText = '- Rp ' + diskon.toLocaleString('id-ID');
            document.getElementById('text-total').innerText = 'Rp ' + totalSetelahDiskon.toLocaleString('id-ID');

            // Update input hidden untuk dikirim ke PHP
            document.getElementById('input_total_harga').value = totalSetelahDiskon;
            document.getElementById('input_diskon').value = diskon;

            // 2. Hitung Kembalian
            let bayar = parseFloat(document.getElementById('bayar').value) || 0;
            let kembalian = bayar - totalSetelahDiskon;

            let elKembali = document.getElementById('text-kembalian');
            if (kembalian >= 0) {
                elKembali.innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
                elKembali.className = 'text-success fs-6 fw-bold';
            } else {
                elKembali.innerText = 'Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
                elKembali.className = 'text-danger fs-6 fw-bold';
            }
        }

        // Fungsi saat memilih Pelanggan dari datalist
        function pilihPelanggan(input, subtotalAwal) {
            let val = input.value;
            let list = document.getElementById('listPelanggan').options;
            let hiddenInput = document.getElementById('pelanggan_id');
            let found = false;

            for (let i = 0; i < list.length; i++) {
                if (list[i].value === val) {
                    hiddenInput.value = list[i].getAttribute('data-id');
                    globalIsMember = list[i].getAttribute('data-member');
                    found = true;
                    break;
                }
            }

            // Jika tidak ditemukan/dihapus, kembalikan ke Non-Member (ID: 0)
            if (!found) {
                hiddenInput.value = '0';
                globalIsMember = '0';
            }

            // Jalankan perhitungan ulang
            hitungKembalian(subtotalAwal);
        }

        // Fungsi saat memilih Produk dari datalist
        function pilihProduk(input) {
            let val = input.value;
            let list = document.getElementById('listProduk').options;
            let hiddenInput = document.getElementById('produk_id_hidden');
            let found = false;

            for (let i = 0; i < list.length; i++) {
                if (list[i].value === val) {
                    hiddenInput.value = list[i].getAttribute('data-id');
                    found = true;
                    break;
                }
            }

            if (!found) {
                hiddenInput.value = '';
            }
        }
    </script>
</body>

</html>