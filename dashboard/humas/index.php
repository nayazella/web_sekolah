<?php
 $page_title = 'Dashboard';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('humas')) {
    redirect('../../index.php');
}

 $db = getDB();

// Statistik
 $totalInfo = $db->query("SELECT COUNT(*) FROM informasi")->fetchColumn();
 $draftInfo = $db->query("SELECT COUNT(*) FROM informasi WHERE status='draft'")->fetchColumn();
 $pendingInfo = $db->query("SELECT COUNT(*) FROM informasi WHERE status='menunggu_persetujuan'")->fetchColumn();
 $approvedInfo = $db->query("SELECT COUNT(*) FROM informasi WHERE status='disetujui'")->fetchColumn();
 $totalPendaftar = $db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
 $menungguPPDB = $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='menunggu'")->fetchColumn();
 $diterimaPPDB = $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='diterima'")->fetchColumn();

// Informasi terbaru
 $recentInfo = $db->query("
    SELECT i.*, k.nama_kategori 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    ORDER BY i.created_at DESC LIMIT 5
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
                <h1>Dashboard Humas</h1>
                <p>Kelola informasi dan pendaftaran PPDB MTSs An-Nahl</p>
            </div>
            <div class="page-actions">
                <a href="informasi_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Buat Informasi</a>
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
                    <h3>Draft</h3>
                    <div class="stat-value"><?php echo $draftInfo; ?></div>
                </div>
                <div class="stat-icon yellow"><i class="fas fa-file-alt"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Menunggu Setuju</h3>
                    <div class="stat-value"><?php echo $pendingInfo; ?></div>
                </div>
                <div class="stat-icon blue"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Pendaftar PPDB</h3>
                    <div class="stat-value"><?php echo $totalPendaftar; ?></div>
                    <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?php echo $menungguPPDB; ?> menunggu</div>
                </div>
                <div class="stat-icon purple"><i class="fas fa-user-graduate"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Diterima PPDB</h3>
                    <div class="stat-value"><?php echo $diterimaPPDB; ?></div>
                </div>
                <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Informasi Publik</h3>
                    <div class="stat-value"><?php echo $approvedInfo; ?></div>
                </div>
                <div class="stat-icon blue"><i class="fas fa-globe"></i></div>
            </div>
        </div>

        <!-- Informasi Terbaru -->
        <div class="card">
            <div class="card-header">
                <h3>Informasi Terbaru</h3>
                <a href="/dashboard/humas/informasi.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recentInfo)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum ada informasi</h3>
                        <p>Mulai buat informasi baru untuk dipublikasikan.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table" id="recentTable">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInfo as $info): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($info['judul']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($info['nama_kategori'] ?? '-'); ?></span></td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'draft' => 'badge-secondary',
                                            'menunggu_persetujuan' => 'badge-warning',
                                            'disetujui' => 'badge-success',
                                            'ditolak' => 'badge-danger'
                                        ];
                                        $statusLabel = [
                                            'draft' => 'Draft',
                                            'menunggu_persetujuan' => 'Menunggu',
                                            'disetujui' => 'Disetujui',
                                            'ditolak' => 'Ditolak'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $statusBadge[$info['status']] ?? 'badge-secondary'; ?>">
                                            <?php echo $statusLabel[$info['status']] ?? $info['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($info['tanggal'] ?? $info['created_at'])); ?></td>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>