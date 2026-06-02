<?php
 $page_title = 'Dashboard PPDB';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('calon_siswa')) {
    redirect('index.php');
}

 $db = getDB();
 $userId = $_SESSION['user_id'];

// Ambil data pendaftaran
 $stmt = $db->prepare("SELECT * FROM pendaftaran WHERE id_user = ?");
 $stmt->execute([$userId]);
 $pendaftaran = $stmt->fetch();

// Ambil informasi PPDB yang disetujui
 $stmt = $db->prepare("
    SELECT i.*, k.nama_kategori 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    WHERE i.status = 'disetujui' AND (i.id_kategori = 4 OR i.judul LIKE '%PPDB%')
    ORDER BY i.tanggal DESC LIMIT 5
");
 $stmt->execute();
 $info_ppdb = $stmt->fetchAll();

 $flash = getFlash();

// Helper untuk cek ekstensi file
function isPdf($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
}
 $uploadBaseUrl = '/web_sekolah/uploads/dokumen_ppdb/';

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
                <h1>Dashboard PPDB</h1>
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['user_nama']); ?></p>
            </div>
        </div>

        <!-- Status Pendaftaran -->
        <?php if ($pendaftaran): ?>
            <?php
            $statusConfig = [
                'menunggu' => ['icon' => 'fas fa-hourglass-half', 'title' => 'Menunggu Verifikasi', 'msg' => 'Data Anda sedang diverifikasi oleh panitia PPDB.', 'color' => '#f59e0b'],
                'diterima' => ['icon' => 'fas fa-check-circle', 'title' => 'Selamat, Anda Diterima!', 'msg' => 'Silakan masukkan kode verifikasi yang tertera pada Kartu Bukti Penerimaan untuk mengaktifkan akun Siswa Anda.', 'color' => '#10b981'],
                'ditolak'  => ['icon' => 'fas fa-times-circle', 'title' => 'Pendaftaran Ditolak', 'msg' => 'Mohon maaf, pendaftaran Anda belum bisa diterima.', 'color' => '#ef4444']
            ];
            $cfg = $statusConfig[$pendaftaran['status']] ?? $statusConfig['menunggu'];
            ?>

            <div class="ppdb-status-card" style="background: linear-gradient(135deg, <?php echo $cfg['color']; ?>, <?php echo $cfg['color']; ?>dd);">
                <div class="ppdb-status-icon"><i class="<?php echo $cfg['icon']; ?>"></i></div>
                <h2><?php echo $cfg['title']; ?></h2>
                <p style="opacity:0.9; margin-top:0.5rem;"><?php echo $cfg['msg']; ?></p>
                
                <?php if ($pendaftaran['catatan']): ?>
                    <div style="margin-top:1rem; padding:0.8rem; background:rgba(255,255,255,0.15); border-radius:8px; font-size:0.85rem;">
                        <strong>Catatan:</strong> <?php echo htmlspecialchars($pendaftaran['catatan']); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Verifikasi Kode Hanya Jika Diterima -->
                <?php if ($pendaftaran['status'] === 'diterima'): ?>
                <div style="margin-top: 1.5rem; text-align: center;">
                    <div style="max-width: 350px; margin: 0 auto; background: rgba(255,255,255,0.95); padding: 1.5rem; border-radius: 10px; color: #333;">
                        <label style="font-weight:bold; display:block; margin-bottom:0.5rem;">Kode Verifikasi</label>
                        <input type="text" id="kode_verifikasi" class="form-control" placeholder="Contoh: PPDB-105" style="text-align: center; font-size: 1.1rem; font-weight: bold; margin-bottom: 1rem;" required>
                        <button class="btn btn-primary btn-block" onclick="activateSiswaRole(<?php echo $userId; ?>)">
                            <i class="fas fa-sign-in-alt"></i> Verifikasi & Beralih ke Dashboard Siswa
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ringkasan Data Diri -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Data Pendaftaran Anda</h3>
                    <?php if ($pendaftaran['status'] === 'menunggu'): ?>
                        <a href="form_pendaftaran.php" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Lengkapi/Data Ulang</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="detail-item"><span class="detail-label">Nama Lengkap</span><span class="detail-value"><?php echo htmlspecialchars($pendaftaran['nama_lengkap']); ?></span></div>
                        <div class="detail-item"><span class="detail-label">Jenis Kelamin</span><span class="detail-value"><?php echo $pendaftaran['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></span></div>
                        <div class="detail-item"><span class="detail-label">Asal Sekolah</span><span class="detail-value"><?php echo htmlspecialchars($pendaftaran['asal_sekolah'] ?? '-'); ?></span></div>
                        <div class="detail-item"><span class="detail-label">No. Telepon</span><span class="detail-value"><?php echo htmlspecialchars($pendaftaran['no_telepon']); ?></span></div>
                        <div class="detail-item"><span class="detail-label">Tempat Lahir</span><span class="detail-value"><?php echo htmlspecialchars($pendaftaran['tempat_lahir'] ?? '-'); ?></span></div>
                        <div class="detail-item"><span class="detail-label">Tanggal Lahir</span><span class="detail-value"><?php echo $pendaftaran['tanggal_lahir'] ? date('d M Y', strtotime($pendaftaran['tanggal_lahir'])) : '-'; ?></span></div>
                        <div class="detail-item"><span class="detail-label">Nama Orang Tua</span><span class="detail-value"><?php echo htmlspecialchars($pendaftaran['nama_ortu'] ?? '-'); ?></span></div>
                        <div class="detail-item"><span class="detail-label">Nilai Rata-rata</span><span class="detail-value"><?php echo $pendaftaran['nilai_rata'] ?? '-'; ?></span></div>
                    </div>

                    <!-- BAGIAN BARU: PREVIEW DOKUMEN TERUPLOAD -->
                    <div style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                        <h4 style="color: var(--primary); margin-bottom: 15px;"><i class="fas fa-paperclip"></i> Dokumen Terupload</h4>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            
                            <?php 
                            $ada_dokumen = false;

                            // 1. Kartu Keluarga
                            if (!empty($pendaftaran['foto_kk'])):
                                $ada_dokumen = true;
                                $isPdf = isPdf($pendaftaran['foto_kk']);
                            ?>
                                <div onclick="openPreviewModal('<?php echo $uploadBaseUrl . $pendaftaran['foto_kk']; ?>', 'Kartu Keluarga')" style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa; cursor: pointer; min-width: 120px;">
                                    <?php if ($isPdf): ?>
                                        <i class="fas fa-file-pdf" style="font-size:3rem; color:red;"></i>
                                    <?php else: ?>
                                        <img src="<?php echo $uploadBaseUrl . $pendaftaran['foto_kk']; ?>" style="max-width: 110px; max-height: 80px; border-radius:5px; object-fit: cover;">
                                    <?php endif; ?>
                                    <br><small style="margin-top:5px; display:block;">Kartu Keluarga <i class="fas fa-search-plus"></i></small>
                                </div>
                            <?php endif; ?>

                            <?php 
                            // 2. Akte Kelahiran
                            if (!empty($pendaftaran['foto_akte'])):
                                $ada_dokumen = true;
                                $isPdf = isPdf($pendaftaran['foto_akte']);
                            ?>
                                <div onclick="openPreviewModal('<?php echo $uploadBaseUrl . $pendaftaran['foto_akte']; ?>', 'Akte Kelahiran')" style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa; cursor: pointer; min-width: 120px;">
                                    <?php if ($isPdf): ?>
                                        <i class="fas fa-file-pdf" style="font-size:3rem; color:red;"></i>
                                    <?php else: ?>
                                        <img src="<?php echo $uploadBaseUrl . $pendaftaran['foto_akte']; ?>" style="max-width: 110px; max-height: 80px; border-radius:5px; object-fit: cover;">
                                    <?php endif; ?>
                                    <br><small style="margin-top:5px; display:block;">Akte Kelahiran <i class="fas fa-search-plus"></i></small>
                                </div>
                            <?php endif; ?>

                            <?php 
                            // 3. Rapor
                            if (!empty($pendaftaran['foto_rapor'])):
                                $ada_dokumen = true;
                                $rapors = explode(',', $pendaftaran['foto_rapor']);
                                $rapor_idx = 1;
                                foreach ($rapors as $r):
                                    if(trim($r) !== ''):
                                    $isPdf = isPdf($r);
                            ?>
                                <div onclick="openPreviewModal('<?php echo $uploadBaseUrl . $r; ?>', 'Rapor <?php echo $rapor_idx; ?>')" style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa; cursor: pointer; min-width: 120px;">
                                    <?php if ($isPdf): ?>
                                        <i class="fas fa-file-pdf" style="font-size:3rem; color:red;"></i>
                                    <?php else: ?>
                                        <img src="<?php echo $uploadBaseUrl . $r; ?>" style="max-width: 110px; max-height: 80px; border-radius:5px; object-fit: cover;">
                                    <?php endif; ?>
                                    <br><small style="margin-top:5px; display:block;">Rapor <?php echo $rapor_idx; ?> <i class="fas fa-search-plus"></i></small>
                                </div>
                            <?php 
                                    $rapor_idx++;
                                    endif;
                                endforeach; 
                            endif; 

                            if (!$ada_dokumen):
                            ?>
                                <p style="color: #777; font-size: 0.9rem; width: 100%;">Belum ada dokumen diunggah. Silakan <a href="form_pendaftaran.php">lengkapi form</a>.</p>
                            <?php endif; ?>

                        </div>
                    </div>
                    <!-- AKHIR BAGIAN DOKUMEN -->

                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-file-alt" style="color:var(--primary);"></i>
                        <h3>Belum Ada Data Pendaftaran</h3>
                        <p>Silakan lengkapi form pendaftaran PPDB terlebih dahulu.</p>
                        <a href="form_pendaftaran.php" class="btn btn-primary mt-2"><i class="fas fa-edit"></i> Isi Form Pendaftaran</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Info PPDB -->
        <?php if (!empty($info_ppdb)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Informasi PPDB</h3>
            </div>
            <div class="card-body info-list">
                <?php foreach ($info_ppdb as $info): ?>
                <div class="info-card">
                    <div class="info-card-meta">
                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($info['nama_kategori'] ?? 'Info'); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($info['tanggal'])); ?></span>
                    </div>
                    <h4><?php echo htmlspecialchars($info['judul']); ?></h4>
                    <p><?php echo htmlspecialchars($info['isi']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- MODAL PREVIEW DOKUMEN -->
<div class="modal-overlay" id="previewModal">
    <div class="modal" style="max-width: 800px; height: 85vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h3 id="previewTitle">Preview Dokumen</h3>
            <button class="modal-close" onclick="closePreviewModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="flex: 1; display: flex; justify-content: center; align-items: center; overflow: hidden; padding: 10px; background: #e9ecef;">
            <img id="previewImg" src="" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
            <iframe id="previewPdf" src="" style="width: 100%; height: 100%; border: none; display: none;"></iframe>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Tutup</button>
        </div>
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

// Fungsi Modal Preview
function openPreviewModal(url, title) {
    document.getElementById('previewTitle').innerText = title || 'Preview Dokumen';
    const imgEl = document.getElementById('previewImg');
    const pdfEl = document.getElementById('previewPdf');
    imgEl.style.display = 'none';
    pdfEl.style.display = 'none';
    
    if (url.toLowerCase().endsWith('.pdf')) {
        pdfEl.src = url;
        pdfEl.style.display = 'block';
    } else {
        imgEl.src = url;
        imgEl.style.display = 'block';
    }
    openModal('previewModal');
}

function closePreviewModal() {
    document.getElementById('previewImg').src = '';
    document.getElementById('previewPdf').src = '';
    closeModal('previewModal');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>