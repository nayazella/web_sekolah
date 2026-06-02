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
            // Update kategori yang sudah ada
            $stmt = $db->prepare("UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?");
            $stmt->execute([$nama_kategori, $deskripsi, $id_kategori]);
            echo json_encode(['success' => true, 'message' => 'Kategori berhasil diperbarui!']);
        } else {
            // Tambah kategori baru
            // Cek duplikat nama
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
        // Karena di database sudah di-set ON DELETE SET NULL pada tabel informasi,
        // menghapus kategori akan membuat id_kategori pada informasi terkait menjadi NULL.
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

// Jika aksi tidak dikenali
echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);