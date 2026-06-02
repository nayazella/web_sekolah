<?php
require_once __DIR__ . '/../../includes/init.php';

if (!hasRole('humas') && !hasRole('admin')) { exit('Akses ditolak'); }

 $db = getDB();
 $id_daftar = (int)($_GET['id'] ?? 0);
 $is_ajax = isset($_GET['ajax']); // Cek apakah dipanggil dari modal

 $stmt = $db->prepare("SELECT p.*, u.username FROM pendaftaran p LEFT JOIN users u ON p.id_user = u.id_user WHERE p.id_daftar = ?");
 $stmt->execute([$id_daftar]);
 $siswa = $stmt->fetch();

if (!$siswa || $siswa['status'] !== 'diterima') {
    exit("Data tidak valid atau siswa belum diterima.");
}

 $kode_verifikasi = "PPDB-" . $siswa['id_daftar'];

// Variabel untuk menyimpan HTML kartu
 $kartu_html = '
<div class="kartu">
    <h3>Madrasah An-Nahl</h3>
    <p style="margin:0; font-size: 12px;">Kartu Bukti Penerimaan PPDB</p>
    <hr style="border: 1px solid #000; margin: 10px 0;">
    
    <div class="label">Nama Calon Siswa</div>
    <div class="nama">'.htmlspecialchars($siswa['nama_lengkap']).'</div>
    
    <div class="label">Asal Sekolah</div>
    <div style="font-weight: bold;">'.htmlspecialchars($siswa['asal_sekolah'] ?? '-').'</div>

    <div class="kode-box">
        <div class="label">Kode Verifikasi Akun</div>
        <div class="kode">'.$kode_verifikasi.'</div>
    </div>

    <div class="catatan">
        *Siswa wajib memasukkan kode di atas saat login pertama kali untuk mengaktifkan akun Dashboard Siswa.
    </div>

    <div class="ttd-area">
        <div>
            <p>Orang Tua/Wali</p>
            <br><br>
            <p>(.........................)</p>
        </div>
        <div>
            <p>Kepala Madrasah</p>
            <br><br>
            <p>(.........................)</p>
        </div>
    </div>
</div>';

// Jika dipanggil via AJAX (dari modal), cukup cetak HTML kartunya saja
if ($is_ajax) {
    echo $kartu_html;
    exit;
}

// Jika dipanggil langsung di browser (fallback), tampilkan halaman cetak utuh
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Verifikasi - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .kartu { border: 2px solid #000; width: 350px; margin: 0 auto; padding: 20px; text-align: center; border-radius: 10px; }
        .kartu h3 { margin: 0 0 5px 0; text-transform: uppercase; }
        .kartu .label { font-size: 12px; color: #555; margin-top: 10px; }
        .kartu .nama { font-size: 18px; font-weight: bold; margin: 5px 0; }
        .kartu .kode-box { margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px dashed #333; border-radius: 5px; }
        .kartu .kode { font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #d9534f; }
        .kartu .catatan { font-size: 10px; color: #777; margin-top: 15px; }
        .ttd-area { display: flex; justify-content: space-between; margin-top: 30px; font-size: 12px; text-align: center; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">🖨️ Cetak Kartu Ini</button>
    </div>
    <?php echo $kartu_html; ?>
</body>
</html>