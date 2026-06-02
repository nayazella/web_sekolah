<?php
require_once 'includes/init.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    $routes = [
        'kepala' => 'dashboard/kepala/index.php',
        'humas' => 'dashboard/humas/index.php',
        'siswa' => 'dashboard/siswa/index.php',
        'calon_siswa' => 'dashboard/ppdb/index.php'
    ];
    redirect($routes[$role] ?? 'website.php');
}

// LOGIKA: Cek apakah datang dari tombol "Daftar PPDB" di website publik
 $show_register = isset($_GET['show']) && $_GET['show'] === 'register';

 $login_tab_class = $show_register ? '' : 'active';
 $register_tab_class = $show_register ? 'active' : '';
 $login_form_class = $show_register ? '' : 'active';
 $register_form_class = $show_register ? 'active' : '';

 $flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MTSs An-Nahl</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DIPERBAIKI: Menambahkan <?php echo time(); ?> agar browser tidak menyimpan cache CSS lama -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-page">
        <!-- Panel Kiri - Branding -->
        <div class="auth-left">
            <div class="auth-brand">
                <div class="brand-icon">
                    <i class="fas fa-mosque"></i>
                </div>
                <h1>MTSs An-Nahl</h1>
                <p>Sistem Informasi Madrasah</p>
            </div>
            <div class="auth-features">
                <div class="auth-feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>Manajemen informasi terintegrasi</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-user-graduate"></i>
                    <span>Pendaftaran PPDB online</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-chart-line"></i>
                    <span>Monitoring dan persetujuan</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-bell"></i>
                    <span>Notifikasi dan pengumuman otomatis</span>
                </div>
            </div>
        </div>

        <!-- Panel Kanan - Form -->
        <div class="auth-right">
            <div class="auth-form-container">
                
                <!-- Tombol Kembali ke Website -->
                <div style="margin-bottom: 20px; text-align: left;">
                    <a href="website.php" style="color: #0f6674; text-decoration: none; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Website
                    </a>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tab Login / Register -->
                <div class="auth-tabs">
                    <button class="auth-tab <?php echo $login_tab_class; ?>" data-tab="loginForm">Masuk</button>
                    <button class="auth-tab <?php echo $register_tab_class; ?>" data-tab="registerForm">Daftar PPDB</button>
                </div>

                <!-- Form Login -->
                <div class="auth-form <?php echo $login_form_class; ?>" id="loginForm">
                    <h2>Selamat Datang</h2>
                    <p class="form-subtitle">Masuk ke sistem informasi MTSs An-Nahl</p>

                    <form action="auth/process.php?action=login" method="POST">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:0.5rem;">
                            <i class="fas fa-sign-in-alt"></i> Masuk
                        </button>
                    </form>

                </div>

                <!-- Form Registrasi PPDB -->
                <div class="auth-form <?php echo $register_form_class; ?>" id="registerForm">
                    <h2>Registrasi PPDB</h2>
                    <p class="form-subtitle">Buat akun untuk mendaftar sebagai calon peserta didik baru</p>

                    <form action="auth/process.php?action=register" method="POST" onsubmit="return validateForm('registerFormEl')">
                        <div id="registerFormEl">
                            <div class="form-group">
                                <label for="reg_nama">Nama Lengkap</label>
                                <input type="text" id="reg_nama" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reg_username">Username</label>
                                    <input type="text" id="reg_username" name="username" class="form-control" placeholder="Pilih username" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_password">Password</label>
                                    <input type="password" id="reg_password" name="password" class="form-control" placeholder="Min. 10 karakter" required minlength="10">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="reg_jk">Jenis Kelamin</label>
                                    <select id="reg_jk" name="jenis_kelamin" class="form-control" required>
                                        <option value="">Pilih</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="reg_telp">No. Telepon</label>
                                    <input type="tel" id="reg_telp" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reg_asal">Asal Sekolah</label>
                                <input type="text" id="reg_asal" name="asal_sekolah" class="form-control" placeholder="Nama sekolah asal" required>
                            </div>
                            <div class="form-group">
                                <label for="reg_alamat">Alamat</label>
                                <textarea id="reg_alamat" name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap" required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:0.5rem;">
                            <i class="fas fa-user-plus"></i> Daftar Akun
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>