<?php
require_once '../../koneksi.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = ?");
$stmt->execute([$id]);
$anggota = $stmt->fetch();

if (!$anggota) {
    die("Data tidak ditemukan.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $pekerjaan = trim($_POST['pekerjaan']);
    $status = $_POST['status'];
    
    // Validasi Required
    if(empty($nama) || empty($email) || empty($telepon) || empty($alamat) || empty($tanggal_lahir) || empty($jenis_kelamin)) {
        $errors[] = "Semua field bertanda * wajib diisi.";
    }

    // Validasi Email & Unique Check (Kecuali ID saat ini)
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    } else {
        $stmtCek = $pdo->prepare("SELECT id_anggota FROM anggota WHERE email = ? AND id_anggota != ?");
        $stmtCek->execute([$email, $id]);
        if($stmtCek->rowCount() > 0) $errors[] = "Email sudah digunakan anggota lain.";
    }

    // Validasi Telepon
    if(!preg_match('/^08[0-9]{8,13}$/', $telepon)) {
        $errors[] = "Format nomor telepon tidak valid. Harus diawali 08.";
    }

    // Validasi Umur
    if(!empty($tanggal_lahir)) {
        $dob = new DateTime($tanggal_lahir);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        if($age < 10) $errors[] = "Umur minimal harus 10 tahun.";
    }

    // Foto Handling
    $foto_name = $anggota['foto']; // Pakai foto lama by default
    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0 && empty($errors)) {
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_info = pathinfo($_FILES['foto']['name']);
        $ext = strtolower($file_info['extension']);
        
        if(in_array($ext, $allowed_ext)) {
            // Hapus foto lama jika ada
            if($anggota['foto'] && file_exists("uploads/" . $anggota['foto'])) {
                unlink("uploads/" . $anggota['foto']);
            }
            // Upload foto baru
            $foto_name = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_name);
        } else {
            $errors[] = "Format foto harus JPG, JPEG, atau PNG.";
        }
    }

    // Update Eksekusi
    if(empty($errors)) {
        $sql = "UPDATE anggota SET nama=?, email=?, telepon=?, alamat=?, tanggal_lahir=?, jenis_kelamin=?, pekerjaan=?, status=?, foto=? WHERE id_anggota=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nama, $email, $telepon, $alamat, $tanggal_lahir, 
            $jenis_kelamin, $pekerjaan, $status, $foto_name, $id
        ]);
        
        header("Location: index.php?msg=Data Anggota berhasil diperbarui.");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 mb-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
        <div class="card-header bg-warning">
            <h4 class="mb-0">Edit Data Anggota</h4>
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
                        <label>Kode Anggota (Readonly)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($anggota['kode_anggota']) ?>" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Status Anggota</label>
                        <select name="status" class="form-select">
                            <option value="Aktif" <?= $anggota['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= $anggota['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? $anggota['nama']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $anggota['email']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Telepon (08xxx) *</label>
                        <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($_POST['telepon'] ?? $anggota['telepon']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? $anggota['tanggal_lahir']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Jenis Kelamin *</label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="Laki-laki" <?= ($anggota['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= ($anggota['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control" value="<?= htmlspecialchars($_POST['pekerjaan'] ?? $anggota['pekerjaan']) ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label>Alamat Lengkap *</label>
                        <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($_POST['alamat'] ?? $anggota['alamat']) ?></textarea>
                    </div>
                    <div class="col-12 mb-4">
                        <label>Update Foto (Kosongkan jika tidak ingin mengubah)</label>
                        <?php if($anggota['foto']): ?>
                            <div class="mb-2"><img src="uploads/<?= $anggota['foto'] ?>" height="80" class="rounded"></div>
                        <?php endif; ?>
                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg">
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">Update Data</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>