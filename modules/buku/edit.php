<?php
$page_title = "Edit Data Buku";
require_once '../../config/database.php';
require_once '../../includes/header.php';
 
// Cek apakah ada ID di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=ID buku tidak valid");
    exit();
}
 
$id_buku = (int)$_GET['id'];
$errors = [];
 
// Ambil data buku untuk ditampilkan di form
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $stmt = $conn->prepare("SELECT * FROM buku WHERE id_buku = ?");
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
    $stmt->close();
    
    // Set variabel untuk form
    $kode_buku = $buku['kode_buku'];
    $judul = $buku['judul'];
    $kategori = $buku['kategori'];
    $pengarang = $buku['pengarang'];
    $penerbit = $buku['penerbit'];
    $tahun = $buku['tahun_terbit'];
    $isbn = $buku['isbn'];
    $harga = $buku['harga'];
    $stok = $buku['stok'];
    $deskripsi = $buku['deskripsi'];
}
 
// Proses update jika form di-submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $kode_buku = sanitize($_POST['kode_buku']);
    $judul = sanitize($_POST['judul']);
    $kategori = sanitize($_POST['kategori']);
    $pengarang = sanitize($_POST['pengarang']);
    $penerbit = sanitize($_POST['penerbit']);
    $tahun = (int)$_POST['tahun'];
    $isbn = sanitize($_POST['isbn']);
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $deskripsi = sanitize($_POST['deskripsi']);
    
    // Validasi (sama seperti create)
    if (empty($kode_buku)) {
        $errors[] = "Kode buku wajib diisi";
    }
    
    if (empty($judul)) {
        $errors[] = "Judul buku wajib diisi";
    } elseif (strlen($judul) < 3) {
        $errors[] = "Judul minimal 3 karakter";
    }
    
    if (empty($kategori)) {
        $errors[] = "Kategori wajib dipilih";
    }
    
    if (empty($pengarang)) {
        $errors[] = "Pengarang wajib diisi";
    }
    
    if (empty($penerbit)) {
        $errors[] = "Penerbit wajib diisi";
    }
    
    if (empty($tahun) || $tahun < 1900 || $tahun > date('Y')) {
        $errors[] = "Tahun terbit tidak valid";
    }
    
    if ($harga < 0) {
        $errors[] = "Harga tidak boleh negatif";
    }
    
    if ($stok < 0) {
        $errors[] = "Stok tidak boleh negatif";
    }
    
    // Cek kode buku duplikat (kecuali untuk buku ini sendiri)
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id_buku FROM buku WHERE kode_buku = ? AND id_buku != ?");
        $stmt->bind_param("si", $kode_buku, $id_buku);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Kode buku sudah digunakan oleh buku lain";
        }
        $stmt->close();
    }
    
    // Jika tidak ada error, update database
    if (count($errors) == 0) {
        $stmt = $conn->prepare("UPDATE buku SET kode_buku = ?, judul = ?, kategori = ?, pengarang = ?, penerbit = ?, tahun_terbit = ?, isbn = ?, harga = ?, stok = ?, deskripsi = ? WHERE id_buku = ?");
        
        $stmt->bind_param("sssssissisi", 
            $kode_buku,
            $judul,
            $kategori,
            $pengarang,
            $penerbit,
            $tahun,
            $isbn,
            $harga,
            $stok,
            $deskripsi,
            $id_buku
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            closeConnection();
            header("Location: index.php?success=" . urlencode("Buku '$judul' berhasil diupdate"));
            exit();
        } else {
            $errors[] = "Error database: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>
 
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil"></i> Edit Data Buku
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Tampilkan Error -->
                    <?php if (count($errors) > 0): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle"></i> Terdapat kesalahan:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <!-- Form sama seperti create.php, tapi sudah terisi data -->
                        
                        <!-- Kode Buku & Judul -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="kode_buku" class="form-label">
                                    Kode Buku <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="kode_buku" 
                                       name="kode_buku" 
                                       value="<?php echo htmlspecialchars($kode_buku); ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label for="judul" class="form-label">
                                    Judul Buku <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="judul" 
                                       name="judul" 
                                       value="<?php echo htmlspecialchars($judul); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Kategori -->
                        <div class="mb-3">
                            <label for="kategori" class="form-label">
                                Kategori <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="kategori" name="kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Programming" <?php echo ($kategori == 'Programming') ? 'selected' : ''; ?>>Programming</option>
                                <option value="Database" <?php echo ($kategori == 'Database') ? 'selected' : ''; ?>>Database</option>
                                <option value="Web Design" <?php echo ($kategori == 'Web Design') ? 'selected' : ''; ?>>Web Design</option>
                                <option value="Networking" <?php echo ($kategori == 'Networking') ? 'selected' : ''; ?>>Networking</option>
                            </select>
                        </div>
                        
                        <!-- Pengarang & Penerbit -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pengarang" class="form-label">
                                    Pengarang <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="pengarang" 
                                       name="pengarang" 
                                       value="<?php echo htmlspecialchars($pengarang); ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="penerbit" class="form-label">
                                    Penerbit <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="penerbit" 
                                       name="penerbit" 
                                       value="<?php echo htmlspecialchars($penerbit); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Tahun & ISBN -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tahun" class="form-label">
                                    Tahun Terbit <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="tahun" 
                                       name="tahun" 
                                       value="<?php echo htmlspecialchars($tahun); ?>"
                                       min="1900" 
                                       max="<?php echo date('Y'); ?>" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="isbn" 
                                       name="isbn" 
                                       value="<?php echo htmlspecialchars($isbn); ?>">
                            </div>
                        </div>
                        
                        <!-- Harga & Stok -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="harga" class="form-label">
                                    Harga (Rp) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="harga" 
                                       name="harga" 
                                       value="<?php echo htmlspecialchars($harga); ?>"
                                       min="0" 
                                       step="1000" 
                                       required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="stok" class="form-label">
                                    Stok <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="stok" 
                                       name="stok" 
                                       value="<?php echo htmlspecialchars($stok); ?>"
                                       min="0" 
                                       required>
                            </div>
                        </div>
                        
                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" 
                                      id="deskripsi" 
                                      name="deskripsi" 
                                      rows="3"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Update Data Buku
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
 
<?php
closeConnection();
require_once '../../includes/footer.php';
?>