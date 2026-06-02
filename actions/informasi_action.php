<?php
require_once __DIR__ . '/../includes/init.php';

 $action = $_GET['action'] ?? '';

// ATUR HAK AKSES BERDASARKAN AKSI
if ($action === 'delete') {
    // PERBAIKAN: Tambahkan role 'kepala' agar bisa menghapus
    // Hilangkan 'siswa' karena seharusnya siswa tidak boleh menghapus informasi madrasah
    if (!hasRole('humas') && !hasRole('admin') && !hasRole('kepala')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
        exit;
    }
} else if ($action === 'detail') {
    // Izinkan siapa saja (termasuk pengunjung publik) untuk melihat detail
} else if ($action === 'submit') {
    // Untuk aksi submit, hanya humas dan admin
    if (!hasRole('humas') && !hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak!']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid!']);
    exit;
}

 $db = getDB();

// PROSES DETAIL (AJAX)
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    
    $where_extra = "";
    // Jika belum login (pengunjung website publik), hanya tampilkan berita disetujui & publik
    if (!isset($_SESSION['user_id'])) {
        $where_extra = " AND i.status = 'disetujui' AND i.target_audiens = 'publik'";
    }

    $stmt = $db->prepare("SELECT i.*, k.nama_kategori, u.nama_lengkap as penulis FROM informasi i LEFT JOIN kategori k ON i.id_kategori = k.id_kategori LEFT JOIN users u ON i.id_user = u.id_user WHERE i.id_info = ? {$where_extra}");
    $stmt->execute([$id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($info) {
        echo json_encode(['success' => true, 'data' => $info]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan!']);
    }
    exit;
}

// PROSES DELETE (AJAX)
if ($action === 'delete') {
    $id = (int)($_POST['id_info'] ?? 0);
    
    // TAMBAHAN: Validasi kepemilikan untuk Humas
    // Jika yang login Humas, pastikan dia hanya menghapus informasi miliknya sendiri
    if (hasRole('humas')) {
        $stmtCek = $db->prepare("SELECT id_user FROM informasi WHERE id_info = ?");
        $stmtCek->execute([$id]);
        $dataInfo = $stmtCek->fetch();
        
        if (!$dataInfo || $dataInfo['id_user'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak bisa menghapus informasi ini!']);
            exit;
        }
    }
    // Jika Kepala atau Admin, langsung diizinkan menghapus tanpa cek kepemilikan

    $stmt = $db->prepare("DELETE FROM informasi WHERE id_info = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal dihapus!']);
    }
    exit;
}

// PROSES SUBMIT DRAFT KE MENUNGGU (AJAX)
if ($action === 'submit') {
    $id = (int)($_POST['id_info'] ?? 0);
    $stmt = $db->prepare("UPDATE informasi SET status = 'menunggu_persetujuan' WHERE id_info = ? AND status = 'draft'");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Berhasil diajukan!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal diajukan!']);
    }
    exit;
}