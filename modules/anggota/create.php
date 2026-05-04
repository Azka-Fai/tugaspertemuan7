<?php
require_once '../../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_anggota = $_POST['kode_anggota'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $pekerjaan = $_POST['pekerjaan'];
    
    // Default values sesuai soal
    $tanggal_daftar = date('Y-m-d'); 
    $status = 'Aktif';
    $errors = [];

    // Validasi Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email cringe, yang bener dong bro.";
    }

    // Validasi Telepon (Harus mulai 08 dan panjang 10-15)
    if (!preg_match('/^08[0-9]{8,13}$/', $telepon)) {
        $errors[] = "Nomor HP wajib diawali '08' dan minimal 10 digit.";
    }

    // Validasi Umur Minimal 10 Tahun
    $umur = date_diff(date_create($tanggal_lahir), date_create('today'))->y;
    if ($umur < 10) {
        $errors[] = "Bocil dilarang daftar, minimal 10 tahun.";
    }

    // Upload Foto (Opsional)
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = time() . '_' . uniqid() . '.' . $ext; // Biar nama file unik
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto);
    }

    if (empty($errors)) {
        $sql = "INSERT INTO anggota (kode_anggota, nama, email, telepon, alamat, tanggal_lahir, jenis_kelamin, pekerjaan, tanggal_daftar, status, foto) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kode_anggota, $nama, $email, $telepon, $alamat, $tanggal_lahir, $jenis_kelamin, $pekerjaan, $tanggal_daftar, $status, $foto]);
        
        header("Location: index.php?msg=sukses_tambah");
        exit();
    } else {
        // Tampilin error di HTML lo pake looping foreach ($errors as $error)
    }
}
?>