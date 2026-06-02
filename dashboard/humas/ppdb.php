<?php
 $page_title = 'Kelola PPDB';
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('humas')) { redirect('index.php'); }

 $db = getDB();

// Proses aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_daftar = (int)($_POST['id_daftar'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if ($action === 'terima' && $id_daftar > 0) {
        $db->beginTransaction();
        try {
            // Update status pendaftaran
            $stmt = $db->prepare("UPDATE pendaftaran SET status = 'diterima', catatan = ? WHERE id_daftar = ?");
            $stmt->execute([$catatan, $id_daftar]);

            // Ambil data pendaftar
            $stmt = $db->prepare("SELECT * FROM pendaftaran WHERE id_daftar = ?");
            $stmt->execute([$id_daftar]);
            $pendaftar = $stmt->fetch();

            if ($pendaftar && $pendaftar['id_user']) {
                // Buat data siswa (NIS disiapkan, tapi akun belum aktif sebagai siswa)
                $nis = '2024' . str_pad($id_daftar, 3, '0', STR_PAD_LEFT);
                $stmt = $db->prepare("INSERT INTO siswa (nis, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, kelas, alamat, no_telepon, id_user) VALUES (?, ?, ?, ?, ?, '7A', ?, ?, ?)");
                $stmt->execute([
                    $nis,
                    $pendaftar['nama_lengkap'],
                    $pendaftar['jenis_kelamin'],
                    $pendaftar['tempat_lahir'],
                    $pendaftar['tanggal_lahir'],
                    $pendaftar['alamat'],
                    $pendaftar['no_telepon'],
                    $pendaftar['id_user']
                ]);
            }

            $db->commit();
            setFlash('success', 'Pendaftar berhasil diterima! Silakan cetak kartu verifikasi dan berikan ke siswa.');
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('danger', 'Gagal memproses penerimaan: ' . $e->getMessage());
        }
        redirect('/web_sekolah/dashboard/humas/ppdb.php');
    }

    if ($action === 'tolak' && $id_daftar > 0) {
        $stmt = $db->prepare("UPDATE pendaftaran SET status = 'ditolak', catatan = ? WHERE id_daftar = ?");
        $stmt->execute([$catatan, $id_daftar]);
        setFlash('success', 'Pendaftaran telah ditolak.');
        redirect('/web_sekolah/dashboard/humas/ppdb.php');
    }
}

// Filter
 $filter_status = $_GET['status'] ?? '';
 $where = "1=1";
 $params = [];
if ($filter_status) {
    $where .= " AND p.status = ?";
    $params[] = $filter_status;
}

 $stmt = $db->prepare("SELECT p.*, u.username FROM pendaftaran p LEFT JOIN users u ON p.id_user = u.id_user WHERE $where ORDER BY p.created_at DESC");
 $stmt->execute($params);
 $pendaftar_list = $stmt->fetchAll();

 $stats = [
    'total' => $db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn(),
    'menunggu' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='menunggu'")->fetchColumn(),
    'diterima' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='diterima'")->fetchColumn(),
    'ditolak' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='ditolak'")->fetchColumn(),
];

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
                <h1>Kelola Pendaftaran PPDB</h1>
                <p>Kelola pendaftaran calon peserta didik baru</p>
            </div>
        </div>

        <!-- Statistik PPDB -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Pendaftar</h3>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                </div>
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Menunggu</h3>
                    <div class="stat-value"><?php echo $stats['menunggu']; ?></div>
                </div>
                <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Diterima</h3>
                    <div class="stat-value"><?php echo $stats['diterima']; ?></div>
                </div>
                <div class="stat-icon blue"><i class="fas fa-user-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Ditolak</h3>
                    <div class="stat-value"><?php echo $stats['ditolak']; ?></div>
                </div>
                <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-2">
            <div class="card-body">
                <div class="filter-bar">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchPPDB" placeholder="Cari nama pendaftar...">
                    </div>
                    <a href="?status=menunggu" class="btn btn-sm <?php echo $filter_status === 'menunggu' ? 'btn-warning' : 'btn-secondary'; ?>">Menunggu</a>
                    <a href="?status=diterima" class="btn btn-sm <?php echo $filter_status === 'diterima' ? 'btn-success' : 'btn-secondary'; ?>">Diterima</a>
                    <a href="?status=ditolak" class="btn btn-sm <?php echo $filter_status === 'ditolak' ? 'btn-danger' : 'btn-secondary'; ?>">Ditolak</a>
                    <a href="?" class="btn btn-sm btn-secondary">Semua</a>
                </div>
            </div>
        </div>

        <!-- Tabel Pendaftar -->
        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (empty($pendaftar_list)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <h3>Belum ada pendaftar</h3>
                        <p>Data pendaftaran PPDB akan muncul di sini.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table" id="ppdbTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Asal Sekolah</th>
                                    <th>No. Telepon</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $statusBadge = ['menunggu' => 'badge-warning', 'diterima' => 'badge-success', 'ditolak' => 'badge-danger'];
                                $statusLabel = ['menunggu' => 'Menunggu', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak'];
                                foreach ($pendaftar_list as $p): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nama_lengkap']); ?></strong></td>
                                    <td><?php echo $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    <td><?php echo htmlspecialchars($p['asal_sekolah'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($p['no_telepon']); ?></td>
                                    <td><span class="badge <?php echo $statusBadge[$p['status']]; ?>"><?php echo $statusLabel[$p['status']]; ?></span></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-sm btn-secondary" onclick="openDetailPPDB(<?php echo $p['id_daftar']; ?>)" title="Detail"><i class="fas fa-eye"></i></button>
                                            <?php if ($p['status'] === 'menunggu'): ?>
                                                <button class="btn btn-sm btn-success" onclick="openTerimaModal(<?php echo $p['id_daftar']; ?>)" title="Terima"><i class="fas fa-check"></i></button>
                                                <button class="btn btn-sm btn-danger" onclick="openTolakModal(<?php echo $p['id_daftar']; ?>)" title="Tolak"><i class="fas fa-times"></i></button>
                                            <?php elseif ($p['status'] === 'diterima'): ?>
                                                <button class="btn btn-sm btn-warning" onclick="openCetakKartuModal(<?php echo $p['id_daftar']; ?>)" title="Cetak Kartu Verifikasi">
                                                    <i class="fas fa-print"></i> 
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger" onclick="deletePendaftar(<?php echo $p['id_daftar']; ?>)" title="Hapus Pendaftar">
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

<!-- Modal Terima -->
<div class="modal-overlay" id="terimaModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Terima Pendaftar</h3>
            <button class="modal-close" onclick="closeModal('terimaModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="terima">
                <input type="hidden" name="id_daftar" id="terima_id_daftar">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Pendaftar yang diterima TIDAK langsung menjadi siswa. Anda harus mencetak Kartu Verifikasi dan memberikannya ke siswa untuk mengaktifkan akun mereka.
                </div>
                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: Diterima di kelas 7A"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('terimaModal')">Batal</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Terima</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal-overlay" id="tolakModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Tolak Pendaftar</h3>
            <button class="modal-close" onclick="closeModal('tolakModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="tolak">
                <input type="hidden" name="id_daftar" id="tolak_id_daftar">
                <div class="form-group">
                    <label>Alasan Penolakan</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('tolakModal')">Batal</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Tolak</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail PPDB -->
<div class="modal-overlay" id="detailPPDBModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Detail Pendaftar</h3>
            <button class="modal-close" onclick="closeModal('detailPPDBModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="detailPPDBContent"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detailPPDBModal')">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Cetak Kartu Pop-up -->
<div class="modal-overlay" id="cetakKartuModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Cetak Kartu Verifikasi</h3>
            <button class="modal-close" onclick="closeModal('cetakKartuModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="cetakKartuContent" style="display:flex; justify-content:center; background:#f9f9f9; padding:20px;">
            <p>Memuat data kartu...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('cetakKartuModal')">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="printKartu()">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
    </div>
</div>

<script>
function openTerimaModal(id) {
    document.getElementById('terima_id_daftar').value = id;
    openModal('terimaModal');
}

function openTolakModal(id) {
    document.getElementById('tolak_id_daftar').value = id;
    openModal('tolakModal');
}

function openDetailPPDB(id) {
    fetch(`/web_sekolah/actions/ppdb_action.php?action=detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const p = data.data;
                const statusBadge = {'menunggu':'badge-warning','diterima':'badge-success','ditolak':'badge-danger'};
                const statusLabel = {'menunggu':'Menunggu','diterima':'Diterima','ditolak':'Ditolak'};
                
                // ==========================================
                // LOGICA MENAMPILKAN DOKUMEN UPLOAD
                // ==========================================
                let dokumenHTML = '';
                const baseUrl = '/web_sekolah/uploads/dokumen_ppdb/';

                if (p.foto_kk || p.foto_akte || p.foto_rapor) {
                    dokumenHTML += `
                        <div style="margin-top: 25px; border-top: 2px solid #eee; padding-top: 20px;">
                            <h4 style="color: var(--primary); margin-bottom: 15px;"><i class="fas fa-paperclip"></i> Dokumen Upload Siswa</h4>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    `;
                    
                    // 1. Cek Kartu Keluarga
                    if (p.foto_kk) {
                        let isPdf = p.foto_kk.split('.').pop().toLowerCase() === 'pdf';
                        dokumenHTML += `
                            <div style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
                                <a href="${baseUrl}${p.foto_kk}" target="_blank" download style="text-decoration:none; color:inherit;">
                                    ${isPdf ? '<i class="fas fa-file-pdf" style="font-size:3.5rem; color:red;"></i>' : `<img src="${baseUrl}${p.foto_kk}" style="max-width: 130px; max-height: 100px; border-radius:5px; object-fit: cover;">`}
                                    <br><small style="margin-top:5px; display:block;">Kartu Keluarga <i class="fas fa-download"></i></small>
                                </a>
                            </div>
                        `;
                    }

                    // 2. Cek Akte Kelahiran
                    if (p.foto_akte) {
                        let isPdf = p.foto_akte.split('.').pop().toLowerCase() === 'pdf';
                        dokumenHTML += `
                            <div style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
                                <a href="${baseUrl}${p.foto_akte}" target="_blank" download style="text-decoration:none; color:inherit;">
                                    ${isPdf ? '<i class="fas fa-file-pdf" style="font-size:3.5rem; color:red;"></i>' : `<img src="${baseUrl}${p.foto_akte}" style="max-width: 130px; max-height: 100px; border-radius:5px; object-fit: cover;">`}
                                    <br><small style="margin-top:5px; display:block;">Akte Kelahiran <i class="fas fa-download"></i></small>
                                </a>
                            </div>
                        `;
                    }

                    // 3. Cek Rapor (Bisa banyak file)
                    if (p.foto_rapor) {
                        let rapors = p.foto_rapor.split(',');
                        rapors.forEach((r, index) => {
                            if(r.trim() !== '') {
                                let isPdf = r.split('.').pop().toLowerCase() === 'pdf';
                                dokumenHTML += `
                                    <div style="text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa;">
                                        <a href="${baseUrl}${r}" target="_blank" download style="text-decoration:none; color:inherit;">
                                            ${isPdf ? '<i class="fas fa-file-pdf" style="font-size:3.5rem; color:red;"></i>' : `<img src="${baseUrl}${r}" style="max-width: 130px; max-height: 100px; border-radius:5px; object-fit: cover;">`}
                                            <br><small style="margin-top:5px; display:block;">Rapor ${index+1} <i class="fas fa-download"></i></small>
                                        </a>
                                    </div>
                                `;
                            }
                        });
                    }

                    dokumenHTML += `</div></div>`;
                }
                // ==========================================

                document.getElementById('detailPPDBContent').innerHTML = `
                    <div class="detail-list">
                        <div class="detail-item"><span class="detail-label">Nama Lengkap</span><span class="detail-value">${p.nama_lengkap}</span></div>
                        <div class="detail-item"><span class="detail-label">Jenis Kelamin</span><span class="detail-value">${p.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</span></div>
                        <div class="detail-item"><span class="detail-label">Tempat, Tgl Lahir</span><span class="detail-value">${p.tempat_lahir || '-'}, ${p.tanggal_lahir || '-'}</span></div>
                        <div class="detail-item"><span class="detail-label">Asal Sekolah</span><span class="detail-value">${p.asal_sekolah || '-'}</span></div>
                        <div class="detail-item"><span class="detail-label">No. Telepon</span><span class="detail-value">${p.no_telepon}</span></div>
                        <div class="detail-item"><span class="detail-label">Nama Ortu</span><span class="detail-value">${p.nama_ortu || '-'}</span></div>
                        <div class="detail-item"><span class="detail-label">No. Telp Ortu</span><span class="detail-value">${p.no_telepon_ortu || '-'}</span></div>
                        <div class="detail-item"><span class="detail-label">Alamat</span><span class="detail-value">${p.alamat || '-'}</span></div>
                        <div class="detail-item"><span class="detail-label">Nilai Rata-rata</span><span class="detail-value">${p.nilai_rata || '-'}</span></div>
                        <div class="detail-item"><span class="detail-label">Status</span><span class="detail-value"><span class="badge ${statusBadge[p.status]}">${statusLabel[p.status]}</span></span></div>
                        ${p.catatan ? `<div class="detail-item"><span class="detail-label">Catatan</span><span class="detail-value">${p.catatan}</span></div>` : ''}
                    </div>
                    
                    ${dokumenHTML} <!-- Sisipkan dokumen di sini -->
                `;
                openModal('detailPPDBModal');
            }
        });
}

function openCetakKartuModal(id) {
    document.getElementById('cetakKartuContent').innerHTML = '<p>Memuat data kartu...</p>';
    openModal('cetakKartuModal');

    fetch(`/web_sekolah/dashboard/humas/cetak_kartu.php?ajax=1&id=${id}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('cetakKartuContent').innerHTML = html;
        })
        .catch(() => showToast('Gagal memuat data kartu!', 'error'));
}

function deletePendaftar(id) {
    if (!confirm('Yakin ingin menghapus data pendaftar ini?')) return;

    fetch('/web_sekolah/actions/ppdb_action.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_daftar=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Data pendaftar berhasil dihapus', 'success');
            const row = document.querySelector(`button[onclick="deletePendaftar(${id})"]`).closest('tr');
            if (row) {
                row.style.opacity = '0';
                row.style.transition = 'opacity 0.3s ease';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            showToast(data.message || 'Gagal menghapus data!', 'error');
        }
    })
    .catch(() => showToast('Terjadi kesalahan jaringan!', 'error'));
}

function printKartu() {
    const content = document.getElementById('cetakKartuContent').innerHTML;
    
    const printWindow = window.open('', '', 'height=600,width=400');
    printWindow.document.write('<html><head><title>Cetak Kartu Verifikasi</title>');
    printWindow.document.write(`
        <style>
            body { font-family: Arial, sans-serif; margin: 0; display:flex; justify-content:center; align-items:center; min-height:100vh;}
            .kartu { border: 2px solid #000; width: 350px; padding: 20px; text-align: center; border-radius: 10px; }
            .kartu h3 { margin: 0 0 5px 0; text-transform: uppercase; }
            .kartu .label { font-size: 12px; color: #555; margin-top: 10px; }
            .kartu .nama { font-size: 18px; font-weight: bold; margin: 5px 0; }
            .kartu .kode-box { margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px dashed #333; border-radius: 5px; }
            .kartu .kode { font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #d9534f; }
            .kartu .catatan { font-size: 10px; color: #777; margin-top: 15px; }
            .ttd-area { display: flex; justify-content: space-between; margin-top: 30px; font-size: 12px; text-align: center; }
        </style>
    `);
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    printWindow.print();
    
    printWindow.onafterprint = function() {
        printWindow.close();
        closeModal('cetakKartuModal');
    };
}

document.addEventListener('DOMContentLoaded', function() {
    filterTable('searchPPDB', 'ppdbTable');
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>