<?php
 $page_title = 'Daftar Informasi';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('kepala')) { redirect('index.php'); }

 $db = getDB();

// Ambil semua informasi dari semua humas
 $informasi = $db->query("
    SELECT i.*, k.nama_kategori, u.nama_lengkap as penulis 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    LEFT JOIN users u ON i.id_user = u.id_user 
    ORDER BY 
        CASE WHEN i.status = 'menunggu_persetujuan' THEN 1 ELSE 2 END,
        i.created_at DESC
")->fetchAll();

 $flash = getFlash();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- LINK CSS OVERRIDE -->
<link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>

    <div class="content">
        
        <!-- DIBUNGKUS DENGAN CONTAINER -->
        <div class="dashboard-container">

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Banner Hijau -->
            <div class="page-header-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1><i class="fas fa-newspaper" style="margin-right: 0.5rem;"></i> Validasi Informasi</h1>
                    <p>Periksa, setujui, dan kelola pengajuan informasi dari humas madrasah</p>
                </div>
            </div>

            <!-- Tabel Informasi -->
            <div class="card">
                <div class="card-body" style="padding:0;">
                    <?php if (empty($informasi)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Belum ada informasi</h3>
                            <p>Belum ada pengajuan informasi dari humas.</p>
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
                                        <th>Penulis</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $statusBadge = ['draft' => 'badge-secondary', 'menunggu_persetujuan' => 'badge-warning', 'disetujui' => 'badge-success', 'ditolak' => 'badge-danger'];
                                    $statusLabel = ['draft' => 'Draft', 'menunggu_persetujuan' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
                                    foreach ($informasi as $info): ?>
                                    <tr id="row-<?php echo $info['id_info']; ?>" style="<?php echo $info['status'] === 'menunggu_persetujuan' ? 'background:var(--warning-light);' : ''; ?>">
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($info['judul']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($info['nama_kategori'] ?? '-'); ?></span></td>
                                        <td><span class="badge <?php echo $statusBadge[$info['status']]; ?>"><?php echo $statusLabel[$info['status']]; ?></span></td>
                                        <td><?php echo htmlspecialchars($info['penulis'] ?? '-'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($info['tanggal'] ?? $info['created_at'])); ?></td>
                                        <td>
                                            <div class="action-btns">
                                                <?php if ($info['status'] === 'menunggu_persetujuan'): ?>
                                                    <form action="/web_sekolah/actions/approve_action.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="id_info" value="<?php echo $info['id_info']; ?>">
                                                        <input type="hidden" name="status" value="disetujui">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Setujui"><i class="fas fa-check"></i></button>
                                                    </form>
                                                    <button class="btn btn-sm btn-warning" onclick="openRejectModalKepala(<?php echo $info['id_info']; ?>)" title="Tolak"><i class="fas fa-times"></i></button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-secondary" onclick="openDetailModal(<?php echo $info['id_info']; ?>)" title="Detail"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteInformasi(<?php echo $info['id_info']; ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
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

<!-- Modal Tolak -->
<div class="modal-overlay" id="rejectModalKepala">
    <div class="modal">
        <div class="modal-header">
            <h3>Tolak Informasi</h3>
            <button class="modal-close" onclick="closeModal('rejectModalKepala')"><i class="fas fa-times"></i></button>
        </div>
        <form action="/web_sekolah/actions/approve_action.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="id_info" id="reject_id_info_k">
                <input type="hidden" name="status" value="ditolak">
                <div class="form-group">
                    <label>Alasan Penolakan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModalKepala')">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Tolak</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Informasi -->
<div class="modal-overlay" id="detailModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Detail Informasi</h3>
            <button class="modal-close" onclick="closeModal('detailModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="detailContent"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>
</div>

<script>
function openRejectModalKepala(id) {
    document.getElementById('reject_id_info_k').value = id;
    openModal('rejectModalKepala');
}

function deleteInformasi(id) {
    if (confirmDelete('Apakah Anda yakin ingin menghapus informasi ini secara permanen?')) {
        fetch('/web_sekolah/actions/informasi_action.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id_info=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Informasi berhasil dihapus!', 'success');
                const row = document.getElementById('row-' + id);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                showToast(data.message || 'Gagal menghapus informasi!', 'error');
            }
        });
    }
}

function openDetailModal(id) {
    fetch(`/web_sekolah/actions/informasi_action.php?action=detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const info = data.data;
                const statusBadge = {'draft':'badge-secondary','menunggu_persetujuan':'badge-warning','disetujui':'badge-success','ditolak':'badge-danger'};
                const statusLabel = {'draft':'Draft','menunggu_persetujuan':'Menunggu','disetujui':'Disetujui','ditolak':'Ditolak'};
                
                let catatanHTML = '';
                if (info.catatan_kepala) {
                    catatanHTML = `
                        <div style="margin-top:1rem; padding:0.75rem; background:var(--danger-light); border-left:4px solid var(--danger); border-radius:4px;">
                            <strong style="color:var(--danger);">Catatan Penolakan:</strong><br>
                            ${info.catatan_kepala}
                        </div>
                    `;
                }

                document.getElementById('detailContent').innerHTML = `
                    <h4 style="margin-bottom:0.5rem;font-weight:700;">${info.judul}</h4>
                    <div style="margin-bottom:1rem;display:flex;gap:0.5rem;">
                        <span class="badge badge-info">${info.nama_kategori || '-'}</span>
                        <span class="badge ${statusBadge[info.status] || 'badge-secondary'}">${statusLabel[info.status] || info.status}</span>
                    </div>
                    <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.7;white-space:pre-line;">${info.isi}</p>
                    <div style="margin-top:1rem;font-size:0.8rem;color:var(--text-muted);">
                        <p><strong>Dibuat oleh:</strong> ${info.penulis || '-'}</p>
                        <p><strong>Tanggal:</strong> ${info.tanggal || '-'}</p>
                    </div>
                    ${catatanHTML}
                `;
                openModal('detailModal');
            }
        });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>