<?php
require_once '../../koneksi.php';

// Setup Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Setup Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = "";
$params = [];

if (!empty($search)) {
    $searchQuery = "WHERE nama LIKE ? OR email LIKE ? OR telepon LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Hitung total data buat pagination
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM anggota $searchQuery");
$stmtTotal->execute($params);
$total_data = $stmtTotal->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Ambil data sesuai page
$sql = "SELECT * FROM anggota $searchQuery ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$anggota_list = $stmt->fetchAll();
?>

<!-- Nanti di HTML lo buat badgenya pake operator ternary gini: -->
<!-- <span class="badge <?= $row['status'] == 'Aktif' ? 'bg-success' : 'bg-danger' ?>"><?= $row['status'] ?></span> -->