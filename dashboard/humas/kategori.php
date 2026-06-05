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

<link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

    <div class="content">
        <div class="dashboard-container">

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="page-header-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1><i class="fas fa-tags" style="margin-right: 0.5rem;"></i> Kelola Kategori Informasi</h1>
                    <p>Kelola kategori untuk mengklasifikasikan berita dan pengumuman</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.4);" onclick="openAddModal()"><i class="fas fa-plus"></i> Tambah Kategori</button>
                </div>
            </div>

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
                                        <th>Informasi Terkait</th>
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
                                            <!-- PERBAIKAN: Tambahkan id unik pada badge berdasarkan id_kategori -->
                                            <span id="badge-kat-<?php echo $kat['id_kategori']; ?>" class="badge badge-primary" style="cursor: pointer; font-size: 0.85rem; padding: 6px 12px;" onclick="openListInformasi(<?php echo $kat['id_kategori']; ?>, '<?php echo htmlspecialchars(addslashes($kat['nama_kategori'])); ?>')">
                                                <?php echo $kat['jumlah_informasi']; ?> informasi <i class="fas fa-arrow-right" style="margin-left:5px;"></i>
                                            </span>
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

        </div>
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

<!-- Modal Daftar Informasi per Kategori -->
<div class="modal-overlay" id="listInformasiModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="listInfoTitle">Informasi dalam Kategori</h3>
            <button class="modal-close" onclick="closeModal('listInformasiModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="listInfoBody" style="max-height: 65vh; overflow-y: auto;">
            <p>Memuat data...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('listInformasiModal')">Tutup</button>
        </div>
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

// FUNGSI MODAL LIST INFORMASI
function openListInformasi(id_kategori, nama_kategori) {
    document.getElementById('listInfoTitle').innerText = `Informasi Kategori: ${nama_kategori}`;
    document.getElementById('listInfoBody').innerHTML = '<p>Memuat data informasi...</p>';
    openModal('listInformasiModal');

    fetch(`/web_sekolah/actions/kategori_action.php?action=list_informasi&id=${id_kategori}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.data.length === 0) {
                    html = '<div class="empty-state" style="padding:20px 0;"><i class="fas fa-inbox"></i><h3>Kosong</h3><p>Belum ada informasi di kategori ini.</p></div>';
                } else {
                    html = '<div style="display:flex; flex-direction:column; gap:12px;">';
                    const statusBadge = {'draft':'badge-secondary','menunggu_persetujuan':'badge-warning','disetujui':'badge-success','ditolak':'badge-danger'};
                    const statusLabel = {'draft':'Draft','menunggu_persetujuan':'Menunggu','disetujui':'Disetujui','ditolak':'Ditolak'};

                    data.data.forEach(info => {
                        // PERBAIKAN: Kirim id_kategori ke fungsi unlink
                        html += `
                            <div id="info-row-${info.id_info}" style="border:1px solid #e0e0e0; padding:15px; border-radius:8px; background:#ffffff; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <div style="flex:1; margin-right:15px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:5px;">
                                        <strong style="font-size:1.05rem; color:#2c3e50;">${info.judul}</strong>
                                        <span class="badge ${statusBadge[info.status] || 'badge-secondary'}">${statusLabel[info.status] || info.status}</span>
                                    </div>
                                    <div style="font-size:0.85rem; color:#7f8c8d; display:flex; gap:15px; flex-wrap:wrap;">
                                        <span><i class="fas fa-user"></i> ${info.penulis || 'Tidak diketahui'}</span>
                                        <span><i class="fas fa-calendar-alt"></i> ${info.tanggal || '-'}</span>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-warning" onclick="unlinkInfoFromKategori(${info.id_info}, ${id_kategori})" title="Lepas dari Kategori">
                                    <i class="fas fa-unlink"></i>
                                </button>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                document.getElementById('listInfoBody').innerHTML = html;
            } else {
                document.getElementById('listInfoBody').innerHTML = '<p style="color:red;">Gagal memuat data.</p>';
            }
        })
        .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}

// FUNGSI LEPAS INFORMASI DARI KATEGORI (DENGAN UPDATE REAL-TIME)
function unlinkInfoFromKategori(id_info, id_kategori) {
    if (!confirm('Lepas informasi ini dari kategori? (Data informasi tetap ada, hanya kategorinya yang dikosongkan)')) return;

    fetch('/web_sekolah/actions/kategori_action.php?action=unlink_info', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_info=' + id_info
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Informasi berhasil dilepas!', 'success');
            
            // 1. Hilangkan baris dari modal dengan animasi
            const row = document.getElementById('info-row-' + id_info);
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                row.style.transition = 'all 0.3s ease';
                setTimeout(() => row.remove(), 300);
            }

            // 2. Update angka pada badge di tabel utama secara real-time
            const badge = document.getElementById('badge-kat-' + id_kategori);
            if (badge) {
                // Ambil angka saat ini dari teks badge (contoh: "5 informasi" -> "5")
                let currentText = badge.innerText.match(/\d+/)[0];
                let newCount = parseInt(currentText) - 1;
                
                // Jika mencapai 0, biarkan 0
                if (newCount < 0) newCount = 0;
                
                // Update isi HTML badge
                badge.innerHTML = `${newCount} informasi <i class="fas fa-arrow-right" style="margin-left:5px;"></i>`;
            }
        } else {
            showToast(data.message || 'Gagal melepas informasi!', 'error');
        }
    })
    .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>