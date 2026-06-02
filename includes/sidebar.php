<?php
 $user = getUser();
 $role = $user['role'] ?? '';
 $current = basename($_SERVER['PHP_SELF']);

$folder_dashboard = $role;
 if ($role === 'calon_siswa') {
     $folder_dashboard = 'ppdb'; 
 }

 $base = '../../dashboard/' . $folder_dashboard;
 
 $db = getDB();
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <i class="fas fa-mosque"></i>
            </div>
            <div class="logo-text">
                <span class="logo-title">MTSs An-Nahl</span>
                <span class="logo-subtitle">Sistem Informasi</span>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'kepala'): ?>
            <div class="nav-section">
                <span class="nav-label">MENU UTAMA</span>
                <a href="<?php echo $base; ?>/index.php" class="nav-item <?php echo $current === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i><span>Dashboard</span>
                </a>
                <a href="<?php echo $base; ?>/informasi.php" class="nav-item <?php echo $current === 'informasi' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i><span>Informasi</span>
                    <?php
                    $db = getDB();
                    $pending = $db->query("SELECT COUNT(*) FROM informasi WHERE status='menunggu_persetujuan'")->fetchColumn();
                    if ($pending > 0): ?>
                        <span class="nav-badge"><?php echo $pending; ?></span>
                    <?php endif; ?>
                </a>
            </div>

        <?php elseif ($role === 'humas'): ?>
            <div class="nav-section">
                <span class="nav-label">MENU UTAMA</span>
                <a href="<?php echo $base; ?>/index.php" class="nav-item <?php echo $current === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i><span>Dashboard</span>
                </a>
            </div>
            <div class="nav-section">
                <span class="nav-label">KELOLA DATA</span>
                <a href="<?php echo $base; ?>/informasi.php" class="nav-item <?php echo in_array($current, ['informasi','informasi_form']) ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i><span>Informasi</span>
                </a>
                <a href="<?php echo $base; ?>/kategori.php" class="nav-item <?php echo $current === 'kategori' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i><span>Kategori</span>
                </a>
                <a href="<?php echo $base; ?>/ppdb.php" class="nav-item <?php echo $current === 'ppdb' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i><span>Pendaftaran PPDB</span>
                    <?php
                    $new = $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status='menunggu'")->fetchColumn();
                    if ($new > 0): ?>
                        <span class="nav-badge"><?php echo $new; ?></span>
                    <?php endif; ?>
                </a>
            </div>

        <?php elseif ($role === 'siswa'): ?>
            <div class="nav-section">
                <span class="nav-label">MENU UTAMA</span>
                <a href="<?php echo $base; ?>/index.php" class="nav-item <?php echo $current === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i><span>Dashboard</span>
                </a>
                <a href="<?php echo $base; ?>/informasi.php" class="nav-item <?php echo $current === 'informasi' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i><span>Informasi</span>
                </a>
                <a href="<?php echo $base; ?>/profil.php" class="nav-item <?php echo $current === 'profil' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i><span>Profil Saya</span>
                </a>
            </div>

        <?php elseif ($role === 'calon_siswa'): ?>
            <div class="nav-section">
                <span class="nav-label">MENU PPDB</span>
                <a href="<?php echo $base; ?>/index.php" class="nav-item <?php echo $current === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i><span>Dashboard</span>
                </a>
                <a href="<?php echo $base; ?>/form_pendaftaran.php" class="nav-item <?php echo $current === 'form_pendaftaran' ? 'active' : ''; ?>">
                    <i class="fas fa-edit"></i><span>Form Pendaftaran</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
    <a href="/web_sekolah/auth/process.php?action=logout" onclick="return confirm('Apakah Anda yakin ingin Logout?')" class="sidebar-link" style="color: #ff9800; display: flex; align-items: center; gap: 10px; text-decoration: none; padding: 15px; width: 100%; box-sizing: border-box;">
       <i class="fas fa-sign-out-alt"></i>
       <span>Logout</span>
    </a>
</div>
</aside>