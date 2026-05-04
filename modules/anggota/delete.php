<?php
require_once '../../koneksi.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    // 1. Ambil data untuk mengecek nama file foto
    $stmtCek = $pdo->prepare("SELECT foto FROM anggota WHERE id_anggota = ?");
    $stmtCek->execute([$id]);
    $anggota = $stmtCek->fetch();

    if ($anggota) {
        // 2. Hapus file foto dari folder uploads/ jika ada
        if (!empty($anggota['foto']) && file_exists("uploads/" . $anggota['foto'])) {
            unlink("uploads/" . $anggota['foto']);
        }

        // --- INI TAMBAHAN SAKTINYA BIAR GAK ERROR 1451 ---
        // 2.5 Bersihin dulu data anak di tabel transaksi yang nyangkut sama id ini
        $stmtHapusTransaksi = $pdo->prepare("DELETE FROM transaksi WHERE id_anggota = ?");
        $stmtHapusTransaksi->execute([$id]);
        // ------------------------------------------------

        // 3. Baru deh aman buat hapus record dari database
        $stmtDel = $pdo->prepare("DELETE FROM anggota WHERE id_anggota = ?");
        $stmtDel->execute([$id]);

        header("Location: index.php?msg=Data Anggota berhasil dihapus.");
        exit;
    }
}

header("Location: index.php");
exit;