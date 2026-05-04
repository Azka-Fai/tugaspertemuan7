<?php
require_once '../../koneksi.php';

// 1. Ambil data lama buat ditampilin di form (Populate Form)
$id_anggota = $_GET['id'] ?? null;
if (!$id_anggota) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = ?");
$stmt->execute([$id_anggota]);
$anggota = $stmt->fetch();

if (!$anggota) {
    echo "Data nggak ketemu, bro!";
    exit();
}

$errors = [];

// 2. Proses jika form di-submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_anggota = trim($_POST['kode_anggota']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $pekerjaan = trim($_POST['pekerjaan']);
    $status = $_POST['status']; 
    
    // Validasi Basic (Email, Telepon, Umur) sama kayak create.php
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email gak valid.";
    if (!preg_match('/^08[0-9]{8,13}$/', $telepon)) $errors[] = "Nomor HP wajib 08... dan 10-15 digit.";
    
    $umur = date_diff(date_create($tanggal_lahir), date_create('today'))->y;
    if ($umur < 10) $errors[] = "Minimal umur 10 tahun.";

    // VALIDASI UNIK (Kode & Email) - Make sure gak nabrak data member lain
    $stmtCek = $pdo->prepare("SELECT id_anggota FROM anggota WHERE (kode_anggota = ? OR email = ?) AND id_anggota != ?");
    $stmtCek->execute([$kode_anggota, $email, $id_anggota]);
    if ($stmtCek->rowCount() > 0) {
        $errors[] = "Kode Anggota atau Email udah dipakai orang lain!";
    }

    // URUSAN FOTO
    $foto_baru = $anggota['foto']; // Default: pakai foto lama
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_upload = time() . '_' . uniqid() . '.' . $ext;
        
        // Upload foto baru
        if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_upload)) {
            // Hapus foto lama kalau ada
            if (!empty($anggota['foto']) && file_exists('uploads/' . $anggota['foto'])) {
                unlink('uploads/' . $anggota['foto']);
            }
            $foto_baru = $foto_upload; // Update nama file ke database
        }
    }

    // Kalau lulus semua validasi, sikat UPDATE-nya
    if (empty($errors)) {
        $sql = "UPDATE anggota SET 
                kode_anggota = ?, nama = ?, email = ?, telepon = ?, alamat = ?, 
                tanggal_lahir = ?, jenis_kelamin = ?, pekerjaan = ?, status = ?, foto = ? 
                WHERE id_anggota = ?";
        
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([
            $kode_anggota, $nama, $email, $telepon, $alamat, 
            $tanggal_lahir, $jenis_kelamin, $pekerjaan, $status, $foto_baru, 
            $id_anggota
        ]);
        
        header("Location: index.php?msg=sukses_update");
        exit();
    }
}
?>

<!-- HTML Form lo taruh di bawah sini ya, value-nya isi pake variabel $anggota['nama_field'] -->
<!-- Contoh: <input type="text" name="nama" value="<?= htmlspecialchars($anggota['nama'] ?? '') ?>"> -->