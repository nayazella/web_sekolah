<?php
require_once __DIR__ . '/../includes/init.php';

 $action = $_GET['action'] ?? '';

// ==================
// PROSES LOGIN
// ==================
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlash('danger', 'Username dan password harus diisi!');
        redirect('../index.php');
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();


    if ($user && md5($password) === $user['password']) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_nama'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_foto'] = $user['foto'];
        $_SESSION['last_activity'] = time();

        $routes = [
            'kepala'      => '../dashboard/kepala/index.php',
            'humas'       => '../dashboard/humas/index.php',
            'siswa'       => '../dashboard/siswa/index.php',
            'calon_siswa' => '../dashboard/ppdb/index.php'
        ];

        setFlash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
        redirect($routes[$user['role']] ?? '../index.php');
    } else {
        setFlash('danger', 'Username atau password salah!');
        redirect('../index.php');
    }
}

// ==================
// PROSES REGISTRASI
// ==================
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $jk = $_POST['jenis_kelamin'] ?? '';
    $telp = trim($_POST['no_telepon'] ?? '');
    $asal = trim($_POST['asal_sekolah'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($username) || empty($password) || empty($jk) || empty($telp) || empty($asal) || empty($alamat)) {
        setFlash('danger', 'Semua field harus diisi!');
        redirect('../index.php');
    }

    if (strlen($password) < 10) {
        setFlash('danger', 'Password minimal 10 karakter!');
        redirect('../index.php');
    }

    $db = getDB();

    // Cek username sudah ada
    $stmt = $db->prepare("SELECT id_user FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        setFlash('danger', 'Username sudah digunakan! Pilih username lain.');
        redirect('../index.php');
    }

    try {
        $db->beginTransaction();

        // Buat akun user dengan role calon_siswa
        $hashedPassword = md5($password);
        $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'calon_siswa')");
        $stmt->execute([$username, $hashedPassword, $nama]);
        $userId = $db->lastInsertId();

        // Buat data pendaftaran PPDB
        $stmt = $db->prepare("INSERT INTO pendaftaran (nama_lengkap, jenis_kelamin, asal_sekolah, alamat, no_telepon, status, id_user) VALUES (?, ?, ?, ?, ?, 'menunggu', ?)");
        $stmt->execute([$nama, $jk, $asal, $alamat, $telp, $userId]);

        $db->commit();

        setFlash('success', 'Registrasi berhasil! Silakan login dengan akun Anda.');
        redirect('../index.php');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Registrasi gagal! Silakan coba lagi.');
        redirect('../index.php');
    }
}

// ==================
// PROSES LOGOUT
// ==================
if ($action === 'logout') {
    session_destroy();
    redirect('../index.php');
}

// Aksi tidak valid
redirect('../index.php');