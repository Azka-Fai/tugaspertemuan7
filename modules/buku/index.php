<?php
$page_title = "Data Buku";
require_once '../../config/database.php';
require_once '../../includes/header.php';

// --- 1. LOGIKA PAGINATION ---
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- 2. LOGIKA SEARCH ---
// Kita gunakan fungsi sanitize yang sudah ada di config/database.php kamu
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// --- 3. BUILD & EXECUTE QUERY ---
if (!empty($search)) {
    // Query dengan fitur pencarian
    $search_param = "%$search%";
    
    // Ambil data hasil cari
    $query = "SELECT * FROM buku 
              WHERE judul LIKE ? OR pengarang LIKE ? OR kategori LIKE ?
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Hitung total baris untuk pagination (khusus hasil cari)
    $count_query = "SELECT COUNT(*) as total FROM buku 
                    WHERE judul LIKE ? OR pengarang LIKE ? OR kategori LIKE ?";
    $stmt_count = $conn->prepare($count_query);
    $stmt_count->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt_count->execute();
    $total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
} else {
    // Query tanpa pencarian (tampil semua)
    $query = "SELECT * FROM buku ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Hitung total baris (seluruh data)
    $total_rows_query = $conn->query("SELECT COUNT(*) as total FROM buku");
    $total_rows = $total_rows_query->fetch_assoc()['total'];
}

// Hitung total halaman
$total_pages = ceil($total_rows / $limit);
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="bi bi-book"></i> Data Buku Perpustakaan</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Buku Baru
            </a>
        </div>
    </div>
    
    <?php
    // Menampilkan pesan notifikasi
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> ' . htmlspecialchars($_GET['success']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
    
    if (isset($_GET['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-x-circle"></i> ' . htmlspecialchars($_GET['error']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
    ?>
    
    <!-- Bagian Form Pencarian -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Cari judul, pengarang, atau kategori...">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                Daftar Buku
                <?php if (!empty($search)): ?>
                    <small class="badge bg-light text-dark ms-2">
                        Hasil pencarian: "<?php echo htmlspecialchars($search); ?>"
                    </small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Kode</th>
                            <th>Judul Buku</th>
                            <th>Kategori</th>
                            <th>Pengarang</th>
                            <th>Penerbit</th>
                            <th width="80">Tahun</th>
                            <th width="130">Harga</th>
                            <th width="70">Stok</th>
                            <th width="120" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><code><?php echo htmlspecialchars($row['kode_buku']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($row['judul']); ?></strong></td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?php echo htmlspecialchars($row['kategori']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['pengarang']); ?></td>
                            <td><?php echo htmlspecialchars($row['penerbit']); ?></td>
                            <td><?php echo $row['tahun_terbit']; ?></td>
                            <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td class="text-center">
                                <?php if ($row['stok'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $row['stok']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="edit.php?id=<?php echo $row['id_buku']; ?>" 
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['id_buku']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Yakin ingin menghapus buku ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Bagian Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Tombol Previous -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            Previous
                        </a>
                    </li>
                    
                    <!-- Angka Halaman -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <!-- Tombol Next -->
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="alert alert-info mt-3 mb-0 py-2">
                <small>
                    <i class="bi bi-info-circle"></i> 
                    Menampilkan <strong><?php echo $result->num_rows; ?></strong> dari total <strong><?php echo $total_rows; ?></strong> buku.
                    (Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>)
                </small>
            </div>
            
            <?php else: ?>
            <div class="alert alert-warning mb-0 text-center">
                <i class="bi bi-exclamation-triangle"></i> 
                <?php if (!empty($search)): ?>
                    Data buku dengan kata kunci "<strong><?php echo htmlspecialchars($search); ?></strong>" tidak ditemukan.
                <?php else: ?>
                    Belum ada data buku dalam database.
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Membersihkan Statement
if (isset($stmt)) $stmt->close();
if (isset($stmt_count)) $stmt_count->close();

closeConnection();
require_once '../../includes/footer.php';
?>