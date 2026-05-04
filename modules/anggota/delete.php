<?php
require_once '../../koneksi.php';

// Pastikan ada parameter ID yang dikirim
if (isset($_GET['id'])) {
    $id_anggota = $_GET['id'];

    // Ambil nama file foto dulu sebelum datanya dihapus
    $stmt = $pdo->prepare("SELECT foto FROM anggota WHERE id_anggota = ?");
    $stmt->execute([$id_anggota]);
    $row = $stmt->fetch();

    if ($row) {
        // Proses Hapus Foto Fisik
        if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])) {
            unlink('uploads/' . $row['foto']);
        }

        // Proses Hapus Data dari Database
        $stmtDelete = $pdo->prepare("DELETE FROM anggota WHERE id_anggota = ?");
        $stmtDelete->execute([$id_anggota]);

        // Redirect balik ke index bawa pesan sukses
        header("Location: index.php?msg=sukses_hapus");
        exit();
    } else {
        header("Location: index.php?msg=data_tidak_ditemukan");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>