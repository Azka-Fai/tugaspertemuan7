<?php
require_once '../../koneksi.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $pekerjaan = trim($_POST['pekerjaan']);
    $status = 'Aktif'; // Default Active
    $tanggal_daftar = date('Y-m-d'); // Default Hari Ini
    
    // Generate Kode Anggota Unik (Format: AGT-YMD-Rand)
    $kode_anggota = 'AGT-' . date('Ymd') . '-' . rand(100, 999);

    // Validasi Required
    if(empty($nama) || empty($email) || empty($telepon) || empty($alamat) || empty($tanggal_lahir) || empty($jenis_kelamin)) {
        $errors[] = "Semua field bertanda * wajib diisi.";
    }

    // Validasi Email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    } else {
        // Cek Email Unique
        $stmtCek = $pdo->prepare("SELECT id_anggota FROM anggota WHERE email = ?");
        $stmtCek->execute([$email]);
        if($stmtCek->rowCount() > 0) $errors[] = "Email sudah terdaftar.";
    }

    // Validasi Telepon (08...)
    if(!preg_match('/^08[0-9]{8,13}$/', $telepon)) {
        $errors[] = "Format nomor telepon tidak valid. Harus diawali 08 dan berjumlah 10-15 digit.";
    }

    // Validasi Umur (Minimal 10 Tahun)
    if(!empty($tanggal_lahir)) {
        $dob = new DateTime($tanggal_lahir);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        if($age < 10) $errors[] = "Umur minimal harus 10 tahun.";
    }

    // Upload Foto (Optional)
    $foto_name = null;
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0 && empty($errors)) {
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_info = pathinfo($_FILES['foto']['name']);
        $ext = strtolower($file_info['extension']);
        
        if(!in_array($ext, $allowed_ext)) {
            $errors[] = "Format foto harus JPG, JPEG, atau PNG.";
        } else {
            $foto_name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_name);
        }
    }

    // Jika tidak ada error, eksekusi Prepare Statement Insert
    if(empty($errors)) {
        $sql = "INSERT INTO anggota (kode_anggota, nama, email, telepon, alamat, tanggal_lahir, jenis_kelamin, pekerjaan, tanggal_daftar, status, foto) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $kode_anggota, $nama, $email, $telepon, $alamat, $tanggal_lahir, 
            $jenis_kelamin, $pekerjaan, $tanggal_daftar, $status, $foto_name
        ]);
        
        header("Location: index.php?msg=Data Anggota berhasil ditambahkan.");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 mb-5">
    <div class="card shadow-sm max-w-lg mx-auto" style="max-width: 800px;">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Tambah Anggota Baru</h4>
        </div>
        <div class="card-body">
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach($errors as $err) echo "<li>$err</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama" class="form-control" value="<?= $_POST['nama'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" value="<?= $_POST['email'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Telepon (08xxx) *</label>
                        <input type="text" name="telepon" class="form-control" value="<?= $_POST['telepon'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= $_POST['tanggal_lahir'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Jenis Kelamin *</label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">Pilih...</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control" value="<?= $_POST['pekerjaan'] ?? '' ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label>Alamat Lengkap *</label>
                        <textarea name="alamat" class="form-control" rows="3" required><?= $_POST['alamat'] ?? '' ?></textarea>
                    </div>
                    <div class="col-12 mb-4">
                        <label>Upload Foto (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg">
                        <small class="text-muted">Format JPG/PNG.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Anggota</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>