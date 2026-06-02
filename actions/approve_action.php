<?php
require_once __DIR__ . '/../includes/init.php';

// Khusus Kepala Madrasah
if (!hasRole('kepala')) {
    setFlash('danger', 'Akses ditolak!');
    redirect('/web_sekolah/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_info = (int)($_POST['id_info'] ?? 0);
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    $redirect_back = $_SERVER['HTTP_REFERER'] ?? '/web_sekolah/dashboard/kepala/index.php';

    if (!in_array($status, ['disetujui', 'ditolak'])) {
        setFlash('danger', 'Status tidak valid!');
        redirect($redirect_back);
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE informasi SET status = ?, catatan_kepala = ? WHERE id_info = ? AND status = 'menunggu_persetujuan'");
    $stmt->execute([$status, $catatan, $id_info]);

    if ($stmt->rowCount() > 0) {
        $msg = $status === 'disetujui' ? 'Informasi berhasil disetujui!' : 'Informasi berhasil ditolak!';
        setFlash('success', $msg);
    } else {
        setFlash('danger', 'Gagal memperbarui status informasi!');
    }

    redirect($redirect_back);
}

redirect('/web_sekolah/dashboard/kepala/index.php');