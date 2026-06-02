<?php
 $page_title = 'Dashboard';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('kepala')) {
    redirect('index.php');
}

 $db = getDB();

// Statistik
 $totalInfo = $db->query("SELECT COUNT(*) FROM informasi")->fetchColumn();
 $menunggu = $db->query("SELECT COUNT(*) FROM informasi WHERE status='menunggu_persetujuan'")->fetchColumn();
 $disetujui = $db->query("SELECT COUNT(*) FROM informasi WHERE status='disetujui'")->fetchColumn();
 $ditolak = $db->query("SELECT COUNT(*) FROM informasi WHERE status='ditolak'")->fetchColumn();
 $totalPendaftar = $db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
 $diterima = $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='diterima'")->fetchColumn();

// Informasi menunggu persetujuan
 $pendingInfo = $db->query("
    SELECT i.*, k.nama_kategori, u.nama_lengkap as penulis 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    LEFT JOIN users u ON i.id_user = u.id_user 
    WHERE i.status = 'menunggu_persetujuan' 
    ORDER BY i.created_at DESC
")->fetchAll();

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
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h1>Dashboard Kepala Madrasah</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['user_nama']); ?></p>
            </div>
        </div>

        <!-- Statistik -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Informasi</h3>
                    <div class="stat-value"><?php echo $totalInfo; ?></div>
                </div>
                <div class="stat-icon green"><i class="fas fa-newspaper"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Menunggu Persetujuan</h3>
                    <div class="stat-value"><?php echo $menunggu; ?></div>
                    <?php if ($menunggu > 0): ?>
                        <div class="stat-change down"><i class="fas fa-clock"></i> Perlu ditinjau</div>
                    <?php endif; ?>
                </div>
                <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Disetujui</h3>
                    <div class="stat-value"><?php echo $disetujui; ?></div>
                </div>
                <div class="stat-icon blue"><i class="fas fa-check-double"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Pendaftar PPDB</h3>
                    <div class="stat-value"><?php echo $totalPendaftar; ?></div>
                    <div class="stat-change up"><i class="fas fa-user-plus"></i> <?php echo $diterima; ?> diterima</div>
                </div>
                <div class="stat-icon purple"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>

        <!-- Informasi Menunggu Persetujuan -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock" style="color:var(--warning);margin-right:0.5rem;"></i>Informasi Menunggu Persetujuan</h3>
                <!-- PERBAIKAN 1: Tambah /web_sekolah/ -->
                <a href="/web_sekolah/dashboard/kepala/informasi.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($pendingInfo)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color:var(--success);"></i>
                        <h3>Semua sudah ditinjau</h3>
                        <p>Tidak ada informasi yang menunggu persetujuan saat ini.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($pendingInfo as $info): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($info['judul']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($info['nama_kategori'] ?? '-'); ?></span></td>
                                    <td><?php echo htmlspecialchars($info['penulis'] ?? '-'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($info['tanggal'] ?? $info['created_at'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-sm btn-success" onclick="approveInfo(<?php echo $info['id_info']; ?>, 'disetujui')" title="Setujui">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="openRejectModal(<?php echo $info['id_info']; ?>)" title="Tolak">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                            <button class="btn btn-sm btn-secondary" onclick="openDetailModal(<?php echo $info['id_info']; ?>)" title="Detail">
                                                <i class="fas fa-eye"></i>
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

<!-- Modal Tolak Informasi -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Tolak Informasi</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')"><i class="fas fa-times"></i></button>
        </div>
        <!-- PERBAIKAN 2: Tambah /web_sekolah/ -->
        <form action="/web_sekolah/actions/approve_action.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="id_info" id="reject_id_info">
                <input type="hidden" name="status" value="ditolak">
                <div class="form-group">
                    <label>Alasan Penolakan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Batal</button>
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
        <div class="modal-body" id="detailContent">
            <!-- Diisi via JS -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>
</div>

<script>
function approveInfo(id, status) {
    if (confirm('Apakah Anda yakin ingin menyetujui informasi ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        // PERBAIKAN 3: Tambah /web_sekolah/
        form.action = '/web_sekolah/actions/approve_action.php';
        form.innerHTML = `
            <input type="hidden" name="id_info" value="${id}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function openRejectModal(id) {
    document.getElementById('reject_id_info').value = id;
    openModal('rejectModal');
}

function openDetailModal(id) {
    // PERBAIKAN 4: Tambah /web_sekolah/
    fetch(`/web_sekolah/actions/informasi_action.php?action=detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const info = data.data;
                document.getElementById('detailContent').innerHTML = `
                    <h4 style="margin-bottom:0.5rem;font-weight:700;">${info.judul}</h4>
                    <div style="margin-bottom:1rem;display:flex;gap:0.5rem;">
                        <span class="badge badge-info">${info.nama_kategori || '-'}</span>
                        <span class="badge badge-warning">Menunggu</span>
                    </div>
                    <p style="font-size:0.9rem;color:var(--text-secondary);line-height:1.7;">${info.isi}</p>
                    <div style="margin-top:1rem;font-size:0.8rem;color:var(--text-muted);">
                        <p><strong>Dibuat oleh:</strong> ${info.penulis || '-'}</p>
                        <p><strong>Tanggal:</strong> ${info.tanggal || '-'}</p>
                    </div>
                `;
                openModal('detailModal');
            }
        });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>