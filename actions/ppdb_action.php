<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

 $action = $_GET['action'] ?? '';

// Detail Pendaftar (AJAX)
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM pendaftaran WHERE id_daftar = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    }
    exit;
}

// Update data pendaftaran dan Upload Dokumen oleh calon siswa
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasRole('calon_siswa')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
        exit;
    }

    $id_daftar = (int)($_POST['id_daftar'] ?? 0);
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $nama_ortu = trim($_POST['nama_ortu'] ?? '');
    $no_telepon_ortu = trim($_POST['no_telepon_ortu'] ?? '');
    $nilai_rata = $_POST['nilai_rata'] ?? null;

    $db = getDB();
    
    // Pastikan pendaftaran milik user yang login
    $stmt = $db->prepare("SELECT id_daftar, foto_kk, foto_akte, foto_rapor FROM pendaftaran WHERE id_daftar = ? AND id_user = ?");
    $stmt->execute([$id_daftar, $_SESSION['user_id']]);
    $existing_data = $stmt->fetch();

    if (!$existing_data) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        exit;
    }

       // ==========================================
    // PROSES UPLOAD DOKUMEN
    // ==========================================
    $uploadDir = __DIR__ . '/../uploads/dokumen_ppdb/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowed_image_pdf = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowed_image = ['jpg', 'jpeg', 'png'];
    
    // Variabel untuk nama file (default dari data lama)
    $foto_kk_name = $existing_data['foto_kk'] ?? '';
    $foto_akte_name = $existing_data['foto_akte'] ?? '';
    $foto_rapor_names_str = $existing_data['foto_rapor'] ?? '';

    // ==========================================
    // CEK FLAG HAPUS FILE DARI FORM
    // ==========================================
    if (isset($_POST['hapus_foto_kk']) && $_POST['hapus_foto_kk'] == '1') {
        if (!empty($foto_kk_name) && file_exists($uploadDir . $foto_kk_name)) {
            unlink($uploadDir . $foto_kk_name); // Hapus fisik
        }
        $foto_kk_name = ''; // Kosongkan database
    }

    if (isset($_POST['hapus_foto_akte']) && $_POST['hapus_foto_akte'] == '1') {
        if (!empty($foto_akte_name) && file_exists($uploadDir . $foto_akte_name)) {
            unlink($uploadDir . $foto_akte_name);
        }
        $foto_akte_name = '';
    }

    if (isset($_POST['hapus_foto_rapor']) && $_POST['hapus_foto_rapor'] == '1') {
        if (!empty($foto_rapor_names_str)) {
            $old_rapors = explode(',', $foto_rapor_names_str);
            foreach ($old_rapors as $old_r) {
                if (file_exists($uploadDir . $old_r)) unlink($uploadDir . $old_r);
            }
        }
        $foto_rapor_names_str = '';
    }

    // ==========================================
    // PROSES UPLOAD FILE BARU (Jika ada)
    // ==========================================
    // 1. Upload Kartu Keluarga
    if (isset($_FILES['foto_kk']) && $_FILES['foto_kk']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_kk']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_image_pdf)) {
            // Jika ada file baru, pastikan file lama (jika ada dan tidak dihapus di atas) ikut terhapus
            if (!empty($existing_data['foto_kk']) && file_exists($uploadDir . $existing_data['foto_kk'])) {
                unlink($uploadDir . $existing_data['foto_kk']);
            }
            $foto_kk_name = 'KK_' . $id_daftar . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['foto_kk']['tmp_name'], $uploadDir . $foto_kk_name);
        }
    }

    // 2. Upload Akte Kelahiran
    if (isset($_FILES['foto_akte']) && $_FILES['foto_akte']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_akte']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_image_pdf)) {
            if (!empty($existing_data['foto_akte']) && file_exists($uploadDir . $existing_data['foto_akte'])) {
                unlink($uploadDir . $existing_data['foto_akte']);
            }
            $foto_akte_name = 'Akte_' . $id_daftar . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['foto_akte']['tmp_name'], $uploadDir . $foto_akte_name);
        }
    }

    // 3. Upload Rapor (Bisa Banyak File)
    if (isset($_FILES['foto_rapor'])) {
        $total_files = count($_FILES['foto_rapor']['name']);
        if ($total_files > 0 && $_FILES['foto_rapor']['error'][0] === UPLOAD_ERR_OK) {
            // Hapus semua rapor lama saat mengganti dengan yang baru
            if (!empty($existing_data['foto_rapor'])) {
                $old_rapors = explode(',', $existing_data['foto_rapor']);
                foreach ($old_rapors as $old_r) {
                    if (file_exists($uploadDir . $old_r)) unlink($uploadDir . $old_r);
                }
            }
            
            $rapor_array = []; // Reset array rapor
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['foto_rapor']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['foto_rapor']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_image)) {
                        $rapor_name = 'Rapor_' . $id_daftar . '_' . time() . '_' . $i . '.' . $ext;
                        move_uploaded_file($_FILES['foto_rapor']['tmp_name'][$i], $uploadDir . $rapor_name);
                        $rapor_array[] = $rapor_name;
                    }
                }
            }
            $foto_rapor_names_str = implode(',', $rapor_array);
        }
    }

    // ==========================================
    // UPDATE DATABASE
    // ==========================================
    $stmt = $db->prepare("UPDATE pendaftaran SET tempat_lahir=?, tanggal_lahir=?, nama_ortu=?, no_telepon_ortu=?, nilai_rata=?, foto_kk=?, foto_akte=?, foto_rapor=? WHERE id_daftar=?");
    $success = $stmt->execute([
        $tempat_lahir, 
        $tanggal_lahir, 
        $nama_ortu, 
        $no_telepon_ortu, 
        $nilai_rata ?: null, 
        $foto_kk_name, 
        $foto_akte_name, 
        $foto_rapor_names_str, 
        $id_daftar
    ]);

    echo json_encode(['success' => $success]);
    exit;
}

// ============================================================
// FITUR: Verifikasi Kode & Aktivasi Role Siswa
// ============================================================
if ($action === 'activate_siswa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasRole('calon_siswa')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
        exit;
    }

    $id_user = (int)($_POST['id_user'] ?? 0);
    $kode_input = trim($_POST['kode_verifikasi'] ?? '');

    if (empty($kode_input)) {
        echo json_encode(['success' => false, 'message' => 'Kode verifikasi tidak boleh kosong!']);
        exit;
    }

    if ($id_user > 0) {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id_daftar FROM pendaftaran WHERE id_user = ? AND status = 'diterima'");
        $stmt->execute([$id_user]);
        $pendaftaran = $stmt->fetch();

        if (!$pendaftaran) {
            echo json_encode(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan atau belum diterima!']);
            exit;
        }

        $kode_benar = "PPDB-" . $pendaftaran['id_daftar'];

        if ($kode_input !== $kode_benar) {
            echo json_encode(['success' => false, 'message' => 'Kode verifikasi salah! Pastikan kode sesuai dari kartu yang diberikan Humas.']);
            exit;
        }

        $stmt = $db->prepare("UPDATE users SET role = 'siswa' WHERE id_user = ? AND role = 'calon_siswa'");
        $stmt->execute([$id_user]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['user_role'] = 'siswa';
            echo json_encode(['success' => true, 'message' => 'Verifikasi berhasil!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah role!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID User tidak valid!']);
    }
    exit;
}

// ============================================================
// FITUR: Hapus Pendaftar oleh Humas/Admin
// ============================================================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasRole('humas') && !hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
        exit;
    }

    $id_daftar = (int)($_POST['id_daftar'] ?? 0);

    if ($id_daftar > 0) {
        $db = getDB();
        
        // Cek apakah ada akun user (calon_siswa) yang terhubung
        $stmt = $db->prepare("SELECT id_user FROM pendaftaran WHERE id_daftar = ?");
        $stmt->execute([$id_daftar]);
        $pendaftar = $stmt->fetch();

        $db->beginTransaction();
        try {
            // Hapus data pendaftaran
            $stmt = $db->prepare("DELETE FROM pendaftaran WHERE id_daftar = ?");
            $stmt->execute([$id_daftar]);

            // Opsional: Hapus juga akun user yang terhubung agar tidak menumpuk
            if ($pendaftar && $pendaftar['id_user']) {
                $stmt = $db->prepare("DELETE FROM users WHERE id_user = ? AND role = 'calon_siswa'");
                $stmt->execute([$pendaftar['id_user']]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Data pendaftar berhasil dihapus!']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID Pendaftar tidak valid!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);