<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Pastikan hanya Humas yang bisa akses
if (!hasRole('humas')) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
    exit;
}

 $db = getDB();
 $action = $_GET['action'] ?? '';

// ===================== DETAIL KATEGORI =====================
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
    }
    exit;
}

// ===================== SIMPAN / UPDATE KATEGORI =====================
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_kategori)) {
        echo json_encode(['success' => false, 'message' => 'Nama kategori wajib diisi!']);
        exit;
    }

    try {
        if ($id_kategori > 0) {
            $stmt = $db->prepare("UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?");
            $stmt->execute([$nama_kategori, $deskripsi, $id_kategori]);
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil diperbarui!']);
        } else {
            $cek = $db->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori = ?");
            $cek->execute([$nama_kategori]);
            if ($cek->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Nama kategori sudah digunakan!']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)");
            $stmt->execute([$nama_kategori, $deskripsi]);
            echo json_encode(['success' => true, 'message' => 'Kategori baru berhasil ditambahkan!']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit;
}

// ===================== HAPUS KATEGORI =====================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);

    if ($id_kategori <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid!']);
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmt->execute([$id_kategori]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan atau sudah dihapus.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori: ' . $e->getMessage()]);
    }
    exit;
}

// ===================== DAFTAR INFORMASI PER KATEGORI =====================
if ($action === 'list_informasi') {
    $id_kategori = (int)($_GET['id'] ?? 0);
    
    $stmt = $db->prepare("
        SELECT i.id_info, i.judul, i.tanggal, i.status, u.nama_lengkap as penulis 
        FROM informasi i 
        LEFT JOIN users u ON i.id_user = u.id_user 
        WHERE i.id_kategori = ? 
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$id_kategori]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($data !== false) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data informasi']);
    }
    exit;
}

// ===================== LEPAS INFORMASI DARI KATEGORI (BARU) =====================
if ($action === 'unlink_info' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_info = (int)($_POST['id_info'] ?? 0);

    if ($id_info <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID Informasi tidak valid!']);
        exit;
    }

    try {
        // Hanya mengosongkan id_kategori, TIDAK menghapus data informasinya
        $stmt = $db->prepare("UPDATE informasi SET id_kategori = NULL WHERE id_info = ?");
        $stmt->execute([$id_info]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Informasi berhasil dilepas dari kategori ini!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal melepas informasi. Mungkin sudah tidak memiliki kategori.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    exit;
}

// Jika aksi tidak dikenali
echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);