<?php
 $page_title = 'Dashboard Siswa';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('siswa')) {
    redirect('index.php');
}

 $db = getDB();
 $userId = $_SESSION['user_id'];

// Ambil data siswa
 $stmt = $db->prepare("SELECT * FROM siswa WHERE id_user = ?");
 $stmt->execute([$userId]);
 $siswa = $stmt->fetch();

// PERBAIKAN: Ambil informasi terbaru yang disetujui DAN bertarget internal
 $stmt = $db->prepare("
    SELECT i.*, k.nama_kategori 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    WHERE i.status = 'disetujui' AND i.target_audiens = 'internal' 
    ORDER BY i.tanggal DESC, i.created_at DESC LIMIT 6
");
 $stmt->execute();
 $informasi = $stmt->fetchAll();

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
        
        <!-- DIBUNGKUS DENGAN CONTAINER AGAR UKURANNYA PROPORSIONAL -->
        <div class="dashboard-container">

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- DIUBAH: Menjadi Banner Hijau -->
            <div class="page-header-banner">
                <h1><i class="fas fa-tachometer-alt" style="margin-right: 0.5rem;"></i> Dashboard Siswa</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['user_nama']); ?>!</p>
            </div>

            <!-- Profil Singkat -->
            <div class="card mb-3">
                <div class="card-body" style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
                    <div class="profile-avatar" style="width:60px;height:60px;font-size:1.5rem;flex-shrink:0;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1">
                        <h3 style="margin-bottom:0.2rem;"><?php echo htmlspecialchars($siswa['nama_lengkap'] ?? $_SESSION['user_nama']); ?></h3>
                        <div style="display:flex;gap:1rem;font-size:0.85rem;color:var(--text-secondary);flex-wrap:wrap;">
                            <span><i class="fas fa-id-badge" style="color:var(--primary);"></i> NIS: <?php echo htmlspecialchars($siswa['nis'] ?? '-'); ?></span>
                            <span><i class="fas fa-chalkboard" style="color:var(--primary);"></i> Kelas: <?php echo htmlspecialchars($siswa['kelas'] ?? '-'); ?></span>
                        </div>
                    </div>
                    <a href="/web_sekolah/dashboard/siswa/profil.php" class="btn btn-sm btn-primary"><i class="fas fa-user-circle"></i> Profil Lengkap</a>
                </div>
            </div>

            <!-- Informasi Terbaru -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bullhorn" style="color:var(--warning);margin-right:0.5rem;"></i>Pengumuman & Informasi</h3>
                    <a href="/web_sekolah/dashboard/siswa/informasi.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                </div>
                <div class="card-body info-list">
                    <?php if (empty($informasi)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Belum ada informasi</h3>
                            <p>Informasi dari madrasah akan ditampilkan di sini.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($informasi as $info): ?>
                        <div class="info-card">
                            <div class="info-card-meta">
                                <span class="badge badge-primary"><?php echo htmlspecialchars($info['nama_kategori'] ?? 'Umum'); ?></span>
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($info['tanggal'] ?? $info['created_at'])); ?></span>
                            </div>
                            <h4><?php echo htmlspecialchars($info['judul']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($info['isi'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- Penutup dashboard-container -->
    </div>
</div>

<script>
function activateSiswaRole(userId) {
    const kodeVerifikasi = document.getElementById('kode_verifikasi').value;

    if (!kodeVerifikasi) {
        showToast('Harap masukkan kode verifikasi terlebih dahulu!', 'warning');
        return;
    }

    fetch('/web_sekolah/actions/ppdb_action.php?action=activate_siswa', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_user=' + encodeURIComponent(userId) + '&kode_verifikasi=' + encodeURIComponent(kodeVerifikasi)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Verifikasi berhasil! Selamat datang, Siswa Baru!', 'success');
            setTimeout(() => {
                window.location.href = '/web_sekolah/dashboard/siswa/index.php';
            }, 1500);
        } else {
            showToast(data.message || 'Kode verifikasi salah!', 'error');
        }
    })
    .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>