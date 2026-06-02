<?php
 $page_title = 'Profil Saya';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('siswa')) { redirect('index.php'); }

 $db = getDB();
 $userId = $_SESSION['user_id'];
 $flash = null;

// ============================================
// LOGIC: Proses Update Data Jika Form Disubmit
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nis           = trim($_POST['nis'] ?? '');
    $kelas         = trim($_POST['kelas'] ?? '');
    $tempat_lahir  = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? ''); 
    $no_telepon    = trim($_POST['no_telepon'] ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');

    if (empty($tanggal_lahir)) {
        $tanggal_lahir = null;
    }

    try {
        $updateStmt = $db->prepare("UPDATE siswa SET nis = ?, kelas = ?, tempat_lahir = ?, tanggal_lahir = ?, no_telepon = ?, alamat = ? WHERE id_user = ?");
        
        if ($updateStmt->execute([$nis, $kelas, $tempat_lahir, $tanggal_lahir, $no_telepon, $alamat, $userId])) {
            $flash = ['type' => 'success', 'message' => 'Profil berhasil diperbarui!'];
        } else {
            $flash = ['type' => 'danger', 'message' => 'Gagal memperbarui profil.'];
        }
    } catch (PDOException $e) {
        $flash = ['type' => 'danger', 'message' => 'Error Database: ' . $e->getMessage()];
    }
}

// ============================================
// AMBIL DATA TERBARU DARI DATABASE
// ============================================
 $stmt = $db->prepare("SELECT * FROM siswa WHERE id_user = ?");
 $stmt->execute([$userId]);
 $siswa = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- LINK CSS OVERRIDE: Memaksa browser memuat CSS terbaru -->
<link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <div class="content">
        
        <!-- DIPERBAIKI: Membungkus seluruh halaman dengan dashboard-container agar ukurannya rapi -->
        <div class="dashboard-container">

            <!-- BANNER HIJAU: Menggunakan class page-header-banner -->
            <div class="page-header-banner">
                <h1><i class="fas fa-user-circle" style="margin-right: 0.5rem;"></i> Profil Saya</h1>
                <p>Kelola informasi data pribadi Anda sebagai siswa MTSs An-Nahl</p>
            </div>

            <!-- Notifikasi Flash Message -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <?php if ($siswa): ?>
            
            <!-- Hanya menggunakan grid-2, karena lebar maksimal sudah diatur oleh dashboard-container -->
            <div class="grid-2">
                <!-- Kartu Profil Kiri -->
                <div class="card">
                    <div class="card-body profile-card">
                        <div class="profile-avatar"><i class="fas fa-user"></i></div>
                        <div class="profile-name"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></div>
                        <div class="profile-role">Siswa MTSs An-Nahl</div>
                        <div style="margin-top:1rem;">
                            <?php if (!empty($siswa['kelas'])): ?>
                                <span class="badge badge-primary" style="font-size:0.85rem; padding:0.4rem 1rem;">Kelas <?php echo htmlspecialchars($siswa['kelas']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary" style="font-size:0.85rem; padding:0.4rem 1rem;">Belum ada kelas</span>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary btn-sm mt-2" onclick="openEditModal()">
                            <i class="fas fa-edit"></i> Edit Profil
                        </button>
                    </div>
                </div>

                <!-- Detail Informasi Kanan -->
                <div class="card">
                    <div class="card-header"><h3>Data Pribadi</h3></div>
                    <div class="card-body">
                        <div class="detail-list">
                            <div class="detail-item">
                                <span class="detail-label">NIS</span>
                                <span class="detail-value"><?php echo !empty($siswa['nis']) ? htmlspecialchars($siswa['nis']) : 'Belum diisi'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Jenis Kelamin</span>
                                <span class="detail-value"><?php echo isset($siswa['jenis_kelamin']) ? ($siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') : '-'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tempat Lahir</span>
                                <span class="detail-value"><?php echo !empty($siswa['tempat_lahir']) ? htmlspecialchars($siswa['tempat_lahir']) : 'Belum diisi'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tanggal Lahir</span>
                                <span class="detail-value"><?php echo !empty($siswa['tanggal_lahir']) ? date('d M Y', strtotime($siswa['tanggal_lahir'])) : 'Belum diisi'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">No. Telepon</span>
                                <span class="detail-value"><?php echo !empty($siswa['no_telepon']) ? htmlspecialchars($siswa['no_telepon']) : 'Belum diisi'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Alamat</span>
                                <span class="detail-value"><?php echo !empty($siswa['alamat']) ? htmlspecialchars($siswa['alamat']) : 'Belum diisi'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="card"><div class="card-body empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Data profil belum tersedia</h3>
                <p>Hubungi administrasi madrasah.</p>
            </div></div>
            <?php endif; ?>

        </div> <!-- Penutup dashboard-container -->
    </div>
</div>

<!-- ============================================
     MODAL EDIT PROFIL
     ============================================ -->
<?php if ($siswa): ?>
<div class="modal-overlay" id="editProfileModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Data Pribadi</h3>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nis">NIS (Nomor Induk Siswa)</label>
                    <input type="text" id="nis" name="nis" class="form-control" placeholder="Masukkan NIS" value="<?php echo htmlspecialchars($siswa['nis'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="kelas">Kelas</label>
                    <select id="kelas" name="kelas" class="form-control">
                        <option value="">-- Pilih Kelas --</option>
                        <option value="7A" <?php echo (isset($siswa['kelas']) && $siswa['kelas'] == '7A') ? 'selected' : ''; ?>>7A</option>
                        <option value="7B" <?php echo (isset($siswa['kelas']) && $siswa['kelas'] == '7B') ? 'selected' : ''; ?>>7B</option>
                        <option value="8A" <?php echo (isset($siswa['kelas']) && $siswa['kelas'] == '8A') ? 'selected' : ''; ?>>8A</option>
                        <option value="8B" <?php echo (isset($siswa['kelas']) && $siswa['kelas'] == '8B') ? 'selected' : ''; ?>>8B</option>
                        <option value="9A" <?php echo (isset($siswa['kelas']) && $siswa['kelas'] == '9A') ? 'selected' : ''; ?>>9A</option>
                        <option value="9B" <?php echo (isset($siswa['kelas']) && $siswa['kelas'] == '9B') ? 'selected' : ''; ?>>9B</option>
                    </select>
                </div>

                <!-- Baris Tempat & Tanggal Lahir -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="tempat_lahir">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Kota/Kabupaten" value="<?php echo htmlspecialchars($siswa['tempat_lahir'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="telp">No. Telepon</label>
                    <input type="tel" id="telp" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($siswa['no_telepon'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($siswa['alamat'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" name="update_profil" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    function openEditModal() {
        document.getElementById('editProfileModal').classList.add('show');
    }
    
    function closeEditModal() {
        document.getElementById('editProfileModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('editProfileModal');
        if (event.target === modal) {
            closeEditModal();
        }
    }
</script>