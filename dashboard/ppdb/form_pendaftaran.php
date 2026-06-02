<?php
 $page_title = 'Form Pendaftaran';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('calon_siswa')) { redirect('index.php'); }

 $db = getDB();
 $userId = $_SESSION['user_id'];

 $stmt = $db->prepare("SELECT * FROM pendaftaran WHERE id_user = ?");
 $stmt->execute([$userId]);
 $pendaftaran = $stmt->fetch();

if (!$pendaftaran) {
    redirect('/web_sekolah/dashboard/ppdb/index.php');
}

if ($pendaftaran['status'] !== 'menunggu') {
    setFlash('warning', 'Anda tidak dapat mengubah data karena pendaftaran sudah diproses.');
    redirect('/web_sekolah/dashboard/ppdb/index.php');
}
 $flash = getFlash();

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
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h1>Form Pendaftaran PPDB</h1>
                <p>Lengkapi data diri dan dokumen Anda untuk pendaftaran</p>
            </div>
            <a href="/web_sekolah/dashboard/ppdb/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form id="ppdbForm" onsubmit="submitPPDB(event)" enctype="multipart/form-data">
                    <input type="hidden" name="id_daftar" value="<?php echo $pendaftaran['id_daftar']; ?>">
                    
                    <h3 style="margin-bottom: 20px; color: var(--primary); border-bottom: 2px solid var(--light); padding-bottom: 10px;">
                        <i class="fas fa-user"></i> Data Diri
                    </h3>

                    <!-- ... Bagian Data Diri Tetap Sama ... -->
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($pendaftaran['nama_lengkap']); ?>" disabled>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Kota kelahiran" value="<?php echo htmlspecialchars($pendaftaran['tempat_lahir'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" value="<?php echo $pendaftaran['tanggal_lahir'] ?? ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama_ortu">Nama Orang Tua/Wali</label>
                            <input type="text" id="nama_ortu" name="nama_ortu" class="form-control" placeholder="Nama ayah/ibu/wali" value="<?php echo htmlspecialchars($pendaftaran['nama_ortu'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="no_telepon_ortu">No. Telp Orang Tua</label>
                            <input type="tel" id="no_telepon_ortu" name="no_telepon_ortu" class="form-control" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($pendaftaran['no_telepon_ortu'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nilai_rata">Nilai Rata-rata Raport</label>
                        <input type="number" step="0.01" min="0" max="100" id="nilai_rata" name="nilai_rata" class="form-control" placeholder="Contoh: 85.50" value="<?php echo $pendaftaran['nilai_rata'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Asal Sekolah</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($pendaftaran['asal_sekolah'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea class="form-control" disabled><?php echo htmlspecialchars($pendaftaran['alamat'] ?? ''); ?></textarea>
                    </div>

                    <!-- BAGIAN UPLOAD DOKUMEN -->
                    <h3 style="margin-top: 40px; margin-bottom: 20px; color: var(--primary); border-bottom: 2px solid var(--light); padding-bottom: 10px;">
                        <i class="fas fa-file-upload"></i> Upload Dokumen
                    </h3>

                    <div class="form-row">
                        <!-- Kartu Keluarga -->
                        <div class="form-group">
                            <label for="input_foto_kk">Foto Kartu Keluarga</label>
                            <!-- Hidden input untuk penanda hapus -->
                            <input type="hidden" name="hapus_foto_kk" id="hapus_foto_kk" value="0">
                            
                            <?php if (!empty($pendaftaran['foto_kk'])): ?>
                            <div id="preview_kk_container" style="position: relative; display: inline-block; margin-bottom: 10px;">
                                <a href="javascript:void(0)" onclick="openPreviewModal('<?php echo $uploadBaseUrl . $pendaftaran['foto_kk']; ?>', 'Kartu Keluarga')" style="cursor: pointer;">
                                    <?php if (isPdf($pendaftaran['foto_kk'])): ?>
                                        <i class="fas fa-file-pdf" style="font-size: 3rem; color: red;"></i>
                                    <?php else: ?>
                                        <img src="<?php echo $uploadBaseUrl . $pendaftaran['foto_kk']; ?>" style="max-width: 150px; max-height: 100px; border-radius: 5px; border: 1px solid #ccc;">
                                    <?php endif; ?>
                                </a>
                                <button type="button" onclick="hapusPreview('kk')" style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endif; ?>

                            <input type="file" id="input_foto_kk" name="foto_kk" class="form-control" accept="image/*,.pdf" <?php echo empty($pendaftaran['foto_kk']) ? 'required' : ''; ?>>
                            <small style="color: #6c757d;">Format: JPG, PNG, PDF (Maks. 2MB). Pilih file baru jika ingin mengganti.</small>
                        </div>

                        <!-- Akte Kelahiran -->
                        <div class="form-group">
                            <label for="input_foto_akte">Foto Akte Kelahiran</label>
                            <input type="hidden" name="hapus_foto_akte" id="hapus_foto_akte" value="0">
                            
                            <?php if (!empty($pendaftaran['foto_akte'])): ?>
                            <div id="preview_akte_container" style="position: relative; display: inline-block; margin-bottom: 10px;">
                                <a href="javascript:void(0)" onclick="openPreviewModal('<?php echo $uploadBaseUrl . $pendaftaran['foto_akte']; ?>', 'Akte Kelahiran')" style="cursor: pointer;">
                                    <?php if (isPdf($pendaftaran['foto_akte'])): ?>
                                        <i class="fas fa-file-pdf" style="font-size: 3rem; color: red;"></i>
                                    <?php else: ?>
                                        <img src="<?php echo $uploadBaseUrl . $pendaftaran['foto_akte']; ?>" style="max-width: 150px; max-height: 100px; border-radius: 5px; border: 1px solid #ccc;">
                                    <?php endif; ?>
                                </a>
                                <button type="button" onclick="hapusPreview('akte')" style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endif; ?>

                            <input type="file" id="input_foto_akte" name="foto_akte" class="form-control" accept="image/*,.pdf" <?php echo empty($pendaftaran['foto_akte']) ? 'required' : ''; ?>>
                            <small style="color: #6c757d;">Format: JPG, PNG, PDF (Maks. 2MB). Pilih file baru jika ingin mengganti.</small>
                        </div>
                    </div>

                    <!-- Rapor -->
                    <div class="form-group">
                        <label for="input_foto_rapor">Foto Rapor (Bisa lebih dari 1 gambar)</label>
                        <input type="hidden" name="hapus_foto_rapor" id="hapus_foto_rapor" value="0">
                        
                        <?php 
                        if (!empty($pendaftaran['foto_rapor'])):
                            $rapor_files = explode(',', $pendaftaran['foto_rapor']);
                            echo '<div id="preview_rapor_container" style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 10px; position: relative;">';
                            $rapor_idx = 1;
                            foreach ($rapor_files as $r_file) {
                                echo '<div style="position: relative;">';
                                echo '<a href="javascript:void(0)" onclick="openPreviewModal(\''.$uploadBaseUrl.$r_file.'\', \'Rapor '.$rapor_idx.'\')" style="cursor: pointer;">';
                                if (isPdf($r_file)) {
                                    echo '<i class="fas fa-file-pdf" style="font-size: 3rem; color: red;"></i>';
                                } else {
                                    echo '<img src="'.$uploadBaseUrl.$r_file.'" style="max-width: 100px; max-height: 80px; border-radius: 5px; border: 1px solid #ccc;">';
                                }
                                echo '</a>';
                                echo '</div>';
                                $rapor_idx++;
                            }
                            // Tombol hapus semua rapor
                            echo '<button type="button" onclick="hapusPreview(\'rapor\')" style="position: absolute; top: -10px; right: -10px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"><i class="fas fa-times"></i></button>';
                            echo '</div>';
                        endif;
                        ?>

                        <input type="file" id="input_foto_rapor" name="foto_rapor[]" class="form-control" accept="image/*,.pdf" multiple <?php echo empty($pendaftaran['foto_rapor']) ? 'required' : ''; ?>>
                        <small style="color: #6c757d;">Format: JPG, PNG. Pilih beberapa file sekaligus (Maks. 5 file @2MB).</small>
                        <div id="raporCount" style="margin-top:5px; font-weight:600; color:var(--primary);"></div>
                    </div>

                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
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
document.getElementById('input_foto_rapor').addEventListener('change', function(e) {
    const count = e.target.files.length;
    const countDiv = document.getElementById('raporCount');
    if (count > 0) {
        countDiv.innerText = `${count} file gambar rapor baru dipilih`;
    } else {
        countDiv.innerText = '';
    }
});

// Fungsi Hapus Preview File
function hapusPreview(type) {
    if(confirm('Anda yakin ingin menghapus file ini? Klik "Simpan Perubahan" untuk menerapkan.')) {
        const container = document.getElementById(`preview_${type}_container`);
        if(container) container.style.display = 'none';
        
        // Set hidden input menjadi 1 agar backend tahu file ini dihapus
        document.getElementById(`hapus_foto_${type}`).value = '1';
        
        // Jadikan input file wajib diisi lagi karena file lama dihapus
        document.getElementById(`input_foto_${type}`).required = true;
    }
}

function submitPPDB(e) {
    e.preventDefault();
    const form = document.getElementById('ppdbForm');
    const formData = new FormData(form);

    fetch('/web_sekolah/actions/ppdb_action.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Data dan dokumen berhasil disimpan!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Gagal menyimpan data!', 'error');
        }
    })
    .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}

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