<?php 
 $user = getUser(); 
 $db = getDB(); 

// Hitung notifikasi berdasarkan role
 $notif_count = 0;
 $notif_items = [];

if (hasRole('humas')) {
    $stmt = $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'menunggu'");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $notif_count += $count;
        $notif_items[] = [
            'icon' => 'fas fa-user-clock text-warning',
            'text' => "$count pendaftar baru menunggu diverifikasi.",
            'link' => '/web_sekolah/dashboard/humas/ppdb.php?status=menunggu'
        ];
    }
} elseif (hasRole('kepala')) {
    $stmt = $db->query("SELECT COUNT(*) FROM informasi WHERE status = 'menunggu_persetujuan'");
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $notif_count += $count;
        $notif_items[] = [
            'icon' => 'fas fa-file-signature text-warning',
            'text' => "$count informasi menunggu persetujuan Anda.",
            'link' => '/web_sekolah/dashboard/kepala/index.php'
        ];
    }
} elseif (hasRole('calon_siswa')) {
    $stmt = $db->prepare("SELECT status FROM pendaftaran WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $status = $stmt->fetchColumn();
    if ($status === 'diterima') {
        $notif_count++;
        $notif_items[] = [
            'icon' => 'fas fa-check-circle text-success',
            'text' => 'Selamat! Pendaftaran Anda diterima. Silakan verifikasi kode.',
            'link' => '/web_sekolah/dashboard/ppdb/index.php'
        ];
    } elseif ($status === 'ditolak') {
        $notif_count++;
        $notif_items[] = [
            'icon' => 'fas fa-times-circle text-danger',
            'text' => 'Mohon maaf, pendaftaran Anda ditolak.',
            'link' => '/web_sekolah/dashboard/ppdb/index.php'
        ];
    }
}
?>

<header class="topbar">
    <div class="topbar-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <span class="breadcrumb-item"><?php echo ucfirst($user['role'] === 'calon_siswa' ? 'PPDB' : $user['role']); ?></span>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item active"><?php echo $page_title ?? 'Dashboard'; ?></span>
        </div>
    </div>
    <div class="topbar-right" style="position: relative;">
        
        <!-- Notifikasi -->
        <div class="topbar-notif" id="notifBtn" style="cursor: pointer;">
            <i class="fas fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span class="notif-dot"></span>
            <?php endif; ?>
        </div>
        
        <!-- Dropdown Notifikasi -->
        <div class="notif-dropdown-menu" id="notifMenu">
            <div class="notif-header">
                <h6 style="margin:0;">Notifikasi</h6>
            </div>
            <div class="notif-body">
                <?php if (empty($notif_items)): ?>
                    <div class="notif-empty">
                        <i class="fas fa-bell-slash" style="font-size: 1.5rem; color: #ccc; margin-bottom: 5px;"></i>
                        <p style="margin:0; color: #888;">Tidak ada notifikasi baru</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notif_items as $item): ?>
                        <a href="<?php echo $item['link']; ?>" class="notif-item">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['text']; ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Profile (Hanya Tampilan, tanpa dropdown) -->
        <div class="topbar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($user['nama']); ?></span>
                <span class="user-role"><?php echo ucfirst($user['role'] === 'calon_siswa' ? 'Calon Siswa' : $user['role']); ?></span>
            </div>
        </div>
    </div>
</header>

<!-- Style untuk Dropdown Notifikasi-->
<style>
.notif-dropdown-menu {
    display: none;
    position: absolute;
    top: 55px; 
    right: 50px;
    width: 320px;
    background: #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    border-radius: 8px;
    z-index: 9999;
    overflow: hidden;
}
.notif-dropdown-menu.show {
    display: block;
}
.notif-header {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}
.notif-body {
    max-height: 250px;
    overflow-y: auto;
}
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 15px;
    border-bottom: 1px solid #f1f1f1;
    text-decoration: none;
    color: #333;
    transition: background 0.2s;
}
.notif-item:hover {
    background: #f9f9f9;
}
.notif-item i {
    margin-top: 3px;
    font-size: 1.1rem;
}
.notif-item span {
    font-size: 0.85rem;
    line-height: 1.4;
}
.notif-empty {
    padding: 30px 15px;
    text-align: center;
}
</style>

<!-- JavaScript Hanya untuk Notifikasi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifBtn = document.getElementById('notifBtn');
    const notifMenu = document.getElementById('notifMenu');

    // Toggle Notifikasi
    if (notifBtn && notifMenu) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifMenu.classList.toggle('show');
        });
    }

    // Tutup dropdown saat klik area lain
    document.addEventListener('click', function(e) {
        if (notifMenu && !notifBtn.contains(e.target) && !notifMenu.contains(e.target)) {
            notifMenu.classList.remove('show');
        }
    });
});
</script>