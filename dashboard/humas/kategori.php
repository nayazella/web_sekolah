<?php
 $page_title = 'Kelola Kategori';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('humas')) { redirect('index.php'); }

 $db = getDB();

// Ambil semua data kategori
 $kategori_list = $db->query("SELECT k.*, (SELECT COUNT(*) FROM informasi WHERE id_kategori = k.id_kategori) as jumlah_informasi FROM kategori k ORDER BY k.nama_kategori")->fetchAll();

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
                    <h1><i class="fas fa-tags" style="margin-right: 0.5rem;"></i> Kelola Kategori Informasi</h1>
                    <p>Kelola kategori untuk mengklasifikasikan berita dan pengumuman</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.4);" onclick="openAddModal()"><i class="fas fa-plus"></i> Tambah Kategori</button>
                </div>
            </div>

            <!-- Tabel Kategori -->
            <div class="card">
                <div class="card-body" style="padding:0;">
                    <?php if (empty($kategori_list)): ?>
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <h3>Belum ada kategori</h3>
                            <p>Buat kategori baru untuk mengelompokkan informasi madrasah.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table" id="kategoriTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kategori</th>
                                        <th>Deskripsi</th>
                                        <th>Jumlah Informasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($kategori_list as $kat): ?>
                                    <tr id="row-<?php echo $kat['id_kategori']; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($kat['nama_kategori']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($kat['deskripsi'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo $kat['jumlah_informasi']; ?> informasi</span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <button class="btn btn-sm btn-secondary" onclick="openEditModal(<?php echo $kat['id_kategori']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteKategori(<?php echo $kat['id_kategori']; ?>)" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Modal Tambah/Edit Kategori -->
<div class="modal-overlay" id="kategoriModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Kategori Baru</h3>
            <button class="modal-close" onclick="closeModal('kategoriModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="formKategori" onsubmit="saveKategori(event)">
            <div class="modal-body">
                <input type="hidden" name="id_kategori" id="id_kategori">
                <div class="form-group">
                    <label for="nama_kategori">Nama Kategori</label>
                    <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" placeholder="Contoh: Pengumuman" required>
                </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan singkat tentang kategori ini"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('kategoriModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Kategori Baru';
    document.getElementById('formKategori').reset();
    document.getElementById('id_kategori').value = '';
    openModal('kategoriModal');
}

function openEditModal(id) {
    fetch(`/web_sekolah/actions/kategori_action.php?action=detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerText = 'Edit Kategori';
                document.getElementById('id_kategori').value = data.data.id_kategori;
                document.getElementById('nama_kategori').value = data.data.nama_kategori;
                document.getElementById('deskripsi').value = data.data.deskripsi || '';
                openModal('kategoriModal');
            } else {
                showToast(data.message || 'Gagal mengambil data!', 'error');
            }
        });
}

function saveKategori(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formKategori'));
    
    fetch('/web_sekolah/actions/kategori_action.php?action=save', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Kategori berhasil disimpan!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Gagal menyimpan kategori!', 'error');
        }
    })
    .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}

function deleteKategori(id) {
    if (confirmDelete('Apakah Anda yakin ingin menghapus kategori ini? Informasi yang terkait akan kehilangan kategorinya.')) {
        fetch('/web_sekolah/actions/kategori_action.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id_kategori=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Kategori berhasil dihapus!', 'success');
                const row = document.getElementById('row-' + id);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                showToast(data.message || 'Gagal menghapus kategori!', 'error');
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>