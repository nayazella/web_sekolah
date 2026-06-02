<?php
 $page_title = 'Kelola Informasi';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('humas')) { redirect('index.php'); }

 $db = getDB();

 $filter_status = $_GET['status'] ?? '';
 $filter_kategori = $_GET['kategori'] ?? '';

 $where = "1=1";
 $params = [];

// PERBAIKAN: Gunakan !empty() agar filter kosong tidak ter eksekusi
if (!empty($filter_status)) {
    $where .= " AND i.status = ?";
    $params[] = $filter_status;
}
if (!empty($filter_kategori)) {
    $where .= " AND i.id_kategori = ?";
    $params[] = $filter_kategori;
}

 $stmt = $db->prepare("
    SELECT i.*, k.nama_kategori, u.nama_lengkap as penulis 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    LEFT JOIN users u ON i.id_user = u.id_user 
    WHERE $where 
    ORDER BY i.created_at DESC
");
 $stmt->execute($params);
 $informasi = $stmt->fetchAll();

 $kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
 $flash = getFlash();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- 1. LINK CSS OVERRIDE: Memaksa browser memuat CSS terbaru -->
<link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

    <div class="content">
        
        <!-- 2. DIBUNGKUS DENGAN CONTAINER AGAR UKURANNYA PROPORSIONAL -->
        <div class="dashboard-container">

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- 3. DIUBAH: Menjadi Banner Hijau dengan tombol di dalamnya -->
            <div class="page-header-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1><i class="fas fa-newspaper" style="margin-right: 0.5rem;"></i> Kelola Informasi</h1>
                    <p>Buat, edit, dan kelola semua informasi madrasah</p>
                </div>
                <div class="page-actions">
                    <a href="informasi_form.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.4);"><i class="fas fa-plus"></i> Tambah Informasi</a>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-2">
                <div class="card-body">
                    <form method="GET" class="filter-bar">
                        <div class="search-input">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="searchInfo" placeholder="Cari informasi...">
                        </div>
                        <select name="status" class="form-control filter-select">
                            <option value="" <?php echo $filter_status === '' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="menunggu_persetujuan" <?php echo $filter_status === 'menunggu_persetujuan' ? 'selected' : ''; ?>>Menunggu Persetujuan</option>
                            <option value="disetujui" <?php echo $filter_status === 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="ditolak" <?php echo $filter_status === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                        <select name="kategori" class="form-control filter-select">
                            <option value="" <?php echo $filter_kategori === '' ? 'selected' : ''; ?>>Semua Kategori</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?php echo $kat['id_kategori']; ?>" <?php echo $filter_kategori == $kat['id_kategori'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    </form>
                </div>
            </div>

            <!-- Tabel Informasi -->
            <div class="card">
                <div class="card-body" style="padding:0;">
                    <?php if (empty($informasi)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Tidak ada informasi</h3>
                            <p>Mulai buat informasi baru dengan klik tombol "Tambah Informasi".</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table" id="infoTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Penulis</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $statusBadge = ['draft' => 'badge-secondary', 'menunggu_persetujuan' => 'badge-warning', 'disetujui' => 'badge-success', 'ditolak' => 'badge-danger'];
                                    $statusLabel = ['draft' => 'Draft', 'menunggu_persetujuan' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
                                    foreach ($informasi as $info): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($info['judul']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($info['nama_kategori'] ?? '-'); ?></span></td>
                                        <td><span class="badge <?php echo $statusBadge[$info['status']] ?? 'badge-secondary'; ?>"><?php echo $statusLabel[$info['status']] ?? $info['status']; ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($info['tanggal'] ?? $info['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($info['penulis'] ?? '-'); ?></td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="informasi_form.php?id=<?php echo $info['id_info']; ?>" class="btn btn-sm btn-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                                <?php if ($info['status'] === 'draft'): ?>
                                                <form action="/web_sekolah/actions/informasi_action.php?action=submit" method="POST" style="display:inline;">
                                                      <input type="hidden" name="id_info" value="<?php echo $info['id_info']; ?>">
                                                      <button type="submit" class="btn btn-sm btn-primary" title="Ajukan">
                                                      <i class="fas fa-paper-plane"></i>
                                                </button>
                                                </form>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteInfo(<?php echo $info['id_info']; ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- Penutup dashboard-container -->
    </div>
</div>

<script>
function deleteInfo(id) {
    if (!confirm('Yakin ingin menghapus informasi ini?')) return;

  fetch('/web_sekolah/actions/informasi_action.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_info=' + encodeURIComponent(id)
    })
    .then(r => r.text())
    .then(text => {
        console.log("RAW:", text);
        return JSON.parse(text);
    })
    .then(data => {
        if (data.success) {
            showToast('Berhasil dihapus', 'success');
            location.reload();
        } else {
            showToast('Gagal menghapus', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Server error', 'error');
    });
}

// Inisialisasi pencarian
document.addEventListener('DOMContentLoaded', function() {
    if (typeof filterTable === 'function') {
        filterTable('searchInfo', 'infoTable');
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>