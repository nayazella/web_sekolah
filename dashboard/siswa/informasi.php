<?php
 $page_title = 'Informasi';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('siswa')) { redirect('index.php'); }

 $db = getDB();

// PERBAIKAN: Ambil informasi yang disetujui DAN bertarget internal (khusus siswa)
 $informasi = $db->query("
    SELECT i.*, k.nama_kategori 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    WHERE i.status = 'disetujui' AND i.target_audiens = 'internal' 
    ORDER BY i.tanggal DESC, i.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- LINK CSS OVERRIDE -->
<link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/topbar.php'; ?>
    <div class="content">
        
        <!-- DIBUNGKUS DENGAN CONTAINER AGAR UKURANNYA PROPORSIONAL -->
        <div class="dashboard-container">

            <!-- DIUBAH: Menjadi Banner Hijau -->
            <div class="page-header-banner" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1><i class="fas fa-bullhorn" style="margin-right: 0.5rem;"></i> Semua Informasi</h1>
                    <p>Berita dan pengumuman resmi dari madrasah</p>
                </div>
                <!-- Tombol disesuaikan warnanya agar selaras di dalam banner hijau -->
                <a href="/web_sekolah/dashboard/siswa/index.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.4);">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="info-list">
                <?php if (empty($informasi)): ?>
                    <div class="card"><div class="card-body empty-state"><i class="fas fa-inbox"></i><h3>Belum ada informasi</h3></div></div>
                <?php else: ?>
                    <?php foreach ($informasi as $info): ?>
                    <div class="info-card" id="card-<?php echo $info['id_info']; ?>">
                        <div class="info-card-meta">
                            <span class="badge badge-primary"><?php echo htmlspecialchars($info['nama_kategori'] ?? 'Umum'); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($info['tanggal'] ?? $info['created_at'])); ?></span>
                        </div>
                        <h4><?php echo htmlspecialchars($info['judul']); ?></h4>
                        <p><?php echo nl2br(htmlspecialchars($info['isi'])); ?></p>
                        
                        <!-- Tombol Aksi (Hanya Baca) -->
                        <div style="margin-top: 1rem;">
                            <button class="btn btn-sm btn-secondary" onclick="openDetailModal(<?php echo $info['id_info']; ?>)">
                                <i class="fas fa-eye"></i> Baca Selengkapnya
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div> <!-- Penutup dashboard-container -->
    </div>
</div>

<!-- Modal Detail Informasi (Pop-up) -->
<div class="modal-overlay" id="detailModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Detail Informasi</h3>
            <button class="modal-close" onclick="closeModal('detailModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="detailContent">
            <!-- Diisi via JavaScript -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>
</div>

<script>
// Fungsi untuk membuka modal detail
function openDetailModal(id) {
    fetch(`/web_sekolah/actions/informasi_action.php?action=detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const info = data.data;
                document.getElementById('detailContent').innerHTML = `
                    <h4 style="margin-bottom:0.5rem;font-weight:700;">${info.judul}</h4>
                    <div style="margin-bottom:1rem;display:flex;gap:0.5rem;">
                        <span class="badge badge-info">${info.nama_kategori || 'Umum'}</span>
                        <span class="badge badge-success">Resmi</span>
                    </div>
                    <p style="font-size:0.95rem;color:var(--text-secondary);line-height:1.7;white-space:pre-line;">${info.isi}</p>
                    <div style="margin-top:1.5rem;font-size:0.8rem;color:var(--text-muted);">
                        <p><i class="fas fa-calendar-alt"></i> Tanggal: ${info.tanggal || '-'}</p>
                    </div>
                `;
                openModal('detailModal');
            } else {
                showToast(data.message || 'Gagal memuat detail!', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>