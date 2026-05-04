<?php
require_once '../../config/database.php';
 
// Cek apakah ada ID di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=ID buku tidak valid");
    exit();
}
 
$id_buku = (int)$_GET['id'];
 
// Ambil data buku untuk pesan konfirmasi
$stmt = $conn->prepare("SELECT judul FROM buku WHERE id_buku = ?");
$stmt->bind_param("i", $id_buku);
$stmt->execute();
$result = $stmt->get_result();
 
if ($result->num_rows == 0) {
    $stmt->close();
    closeConnection();
    header("Location: index.php?error=Buku tidak ditemukan");
    exit();
}
 
$buku = $result->fetch_assoc();
$judul_buku = $buku['judul'];
$stmt->close();
 
// Proses delete
$stmt = $conn->prepare("DELETE FROM buku WHERE id_buku = ?");
$stmt->bind_param("i", $id_buku);
 
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        closeConnection();
        header("Location: index.php?success=" . urlencode("Buku '$judul_buku' berhasil dihapus"));
        exit();
    } else {
        $stmt->close();
        closeConnection();
        header("Location: index.php?error=Gagal menghapus data");
        exit();
    }
} else {
    $error = $stmt->error;
    $stmt->close();
    closeConnection();
    header("Location: index.php?error=" . urlencode("Error database: $error"));
    exit();
}
?>