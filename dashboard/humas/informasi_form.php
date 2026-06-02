<?php
 $page_title = 'Form Informasi';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('humas')) { redirect('index.php'); }

 $db = getDB();
 $edit_mode = false;
 $info = null;

// Mode edit
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM informasi WHERE id_info = ?");
    $stmt->execute([$id]);
    $info = $stmt->fetch();
    if ($info) {
        $edit_mode = true;
    }
}

 $kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $action_btn = $_POST['action_btn'] ?? 'draft';
    $id_edit = (int)($_POST['id_info'] ?? 0);
    $target_audiens = $_POST['target_audiens'] ?? 'internal'; // TAMBAHAN: Ambil data target audiens

    if (empty($judul) || empty($isi)) {
        setFlash('danger', 'Judul dan isi informasi harus diisi!');
    } else {
        $status = $action_btn === 'ajukan' ? 'menunggu_persetujuan' : 'draft';

        if ($id_edit > 0) {
            // Update
            // PERBAIKAN: Tambahkan target_audiens pada query UPDATE
            $stmt = $db->prepare("UPDATE informasi SET judul=?, isi=?, id_kategori=?, tanggal=?, status=?, target_audiens=? WHERE id_info=?");
            $stmt->execute([$judul, $isi, $id_kategori ?: null, $tanggal, $status, $target_audiens, $id_edit]);
            setFlash('success', 'Informasi berhasil diperbarui!');
        } else {
            // Insert baru
            // PERBAIKAN: Tambahkan target_audiens pada query INSERT
            $stmt = $db->prepare("INSERT INTO informasi (judul, isi, id_kategori, id_user, status, tanggal, target_audiens) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$judul, $isi, $id_kategori ?: null, $_SESSION['user_id'], $status, $tanggal, $target_audiens]);
            setFlash('success', $status === 'draft' ? 'Informasi disimpan sebagai draft!' : 'Informasi diajukan untuk persetujuan!');
        }
        redirect('informasi.php');
    }
}

 $flash = getFlash();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

    <div class="content">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h1><?php echo $edit_mode ? 'Edit Informasi' : 'Buat Informasi Baru'; ?></h1>
                <p><?php echo $edit_mode ? 'Perbarui konten informasi madrasah' : 'Tambahkan informasi baru untuk madrasah'; ?></p>
            </div>
            <a href="/web_sekolah/dashboard/humas/informasi.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" onsubmit="return validateForm('infoForm')">
                    <div id="infoForm">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="id_info" value="<?php echo $info['id_info']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="judul">Judul Informasi</label>
                            <input type="text" id="judul" name="judul" class="form-control" placeholder="Masukkan judul informasi" required
                                   value="<?php echo $edit_mode ? htmlspecialchars($info['judul']) : ''; ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_kategori">Kategori</label>
                                <select id="id_kategori" name="id_kategori" class="form-control">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?php echo $kat['id_kategori']; ?>" 
                                            <?php echo ($edit_mode && $info['id_kategori'] == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tanggal">Tanggal</label>
                                <input type="date" id="tanggal" name="tanggal" class="form-control" required
                                       value="<?php echo $edit_mode ? ($info['tanggal'] ?? date('Y-m-d')) : date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <!-- TAMBAHAN: Dropdown Target Audiens -->
                        <div class="form-group">
                            <label for="target_audiens">Target Audiens</label>
                            <select id="target_audiens" name="target_audiens" class="form-control" required>
                                <option value="internal" <?php echo ($edit_mode && isset($info['target_audiens']) && $info['target_audiens'] === 'internal') ? 'selected' : ''; ?>>Internal (Khusus Siswa/Guru)</option>
                                <option value="publik" <?php echo ($edit_mode && isset($info['target_audiens']) && $info['target_audiens'] === 'publik') ? 'selected' : ''; ?>>Publik (Masyarakat Umum/Website)</option>
                            </select>
                            <small style="color: #6c757d;">Pilih "Publik" jika informasi ini ingin ditampilkan di halaman website masyarakat umum.</small>
                        </div>

                        <div class="form-group">
                            <label for="isi">Isi Informasi</label>
                            <textarea id="isi" name="isi" class="form-control" rows="8" placeholder="Tulis isi informasi..." required><?php echo $edit_mode ? htmlspecialchars($info['isi']) : ''; ?></textarea>
                        </div>

                        <div style="display:flex;gap:0.8rem;margin-top:1.5rem;">
                            <button type="submit" name="action_btn" value="draft" class="btn btn-secondary">
                                <i class="fas fa-save"></i> Simpan Draft
                            </button>
                            <button type="submit" name="action_btn" value="ajukan" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Ajukan Persetujuan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>