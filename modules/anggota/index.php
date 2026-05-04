<?php
require_once '../../koneksi.php';

// Fitur Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Data_Anggota_" . date('Ymd') . ".xls");
    $is_export = true;
} else {
    $is_export = false;
}

// Inisialisasi parameter dinamis
$where = ["1=1"];
$params = [];

// Filter & Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $where[] = "(nama LIKE :search OR email LIKE :search OR telepon LIKE :search)";
    $params[':search'] = "%$search%";
}

$filter_status = $_GET['status'] ?? '';
if (!empty($filter_status)) {
    $where[] = "status = :status";
    $params[':status'] = $filter_status;
}

$filter_jk = $_GET['jenis_kelamin'] ?? '';
if (!empty($filter_jk)) {
    $where[] = "jenis_kelamin = :jk";
    $params[':jk'] = $filter_jk;
}

$where_clause = implode(" AND ", $where);

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Menghitung Total Data untuk Pagination
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM anggota WHERE $where_clause");
$stmtTotal->execute($params);
$total_data = $stmtTotal->fetch()['total'];
$total_pages = ceil($total_data / $limit);

// Query Utama List Anggota
$sql = "SELECT * FROM anggota WHERE $where_clause ORDER BY id_anggota DESC";
if (!$is_export) {
    $sql .= " LIMIT :limit OFFSET :offset";
}

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
if (!$is_export) {
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
}
$stmt->execute();
$anggota = $stmt->fetchAll();

// Statistik Dashboard
$statStmt = $pdo->query("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status='Aktif' THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN status='Nonaktif' THEN 1 ELSE 0 END) as nonaktif
    FROM anggota");
$stats = $statStmt->fetch();

if ($is_export) {
    // Mode Export HTML Table Sederhana
    echo "<table border='1'><tr><th>Kode</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Jenis Kelamin</th><th>Status</th><th>Tgl Daftar</th></tr>";
    foreach ($anggota as $row) {
        echo "<tr><td>{$row['kode_anggota']}</td><td>{$row['nama']}</td><td>{$row['email']}</td><td>{$row['telepon']}</td><td>{$row['jenis_kelamin']}</td><td>{$row['status']}</td><td>{$row['tanggal_daftar']}</td></tr>";
    }
    echo "</table>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Anggota Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4">Data Anggota Perpustakaan</h2>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body"><h5 class="card-title">Total Anggota</h5><h3><?= $stats['total'] ?></h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body"><h5 class="card-title">Anggota Aktif</h5><h3><?= $stats['aktif'] ?? 0 ?></h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-secondary mb-3">
                <div class="card-body"><h5 class="card-title">Anggota Nonaktif</h5><h3><?= $stats['nonaktif'] ?? 0 ?></h3></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <a href="create.php" class="btn btn-primary">+ Tambah Anggota</a>
        <a href="?export=excel&<?= http_build_query($_GET) ?>" class="btn btn-success">Export Excel</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari Nama/Email/Telepon..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- Semua Status --</option>
                        <option value="Aktif" <?= $filter_status == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Nonaktif" <?= $filter_status == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="jenis_kelamin" class="form-select">
                        <option value="">-- Semua Gender --</option>
                        <option value="Laki-laki" <?= $filter_jk == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= $filter_jk == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive bg-white shadow-sm p-3 rounded">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Foto</th>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($anggota)): ?>
                    <tr><td colspan="7" class="text-center">Data tidak ditemukan.</td></tr>
                <?php endif; ?>
                <?php foreach($anggota as $row): ?>
                <tr>
                    <td>
                        <?php if($row['foto'] && file_exists("uploads/".$row['foto'])): ?>
                            <img src="uploads/<?= $row['foto'] ?>" alt="Foto" width="50" height="50" class="rounded-circle object-fit-cover">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/50" alt="No Foto" class="rounded-circle">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['kode_anggota']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['nama']) ?></strong><br>
                        <small class="text-muted"><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['email']) ?><br>
                        <small><?= htmlspecialchars($row['telepon']) ?></small>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['jenis_kelamin'] == 'Laki-laki' ? 'info' : 'warning' ?>">
                            <?= $row['jenis_kelamin'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['status'] == 'Aktif' ? 'success' : 'secondary' ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit.php?id=<?= $row['id_anggota'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete.php?id=<?= $row['id_anggota'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus anggota ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <nav>
            <ul class="pagination justify-content-center">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $filter_status ?>&jenis_kelamin=<?= $filter_jk ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>
</body>
</html>