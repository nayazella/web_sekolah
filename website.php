<?php
// Halaman Publik (Tanpa Login)
require_once __DIR__ . '/includes/init.php';

 $db = getDB();

// Ambil informasi berstatus disetujui DAN bertarget publik
 $stmt = $db->prepare("
    SELECT i.*, k.nama_kategori 
    FROM informasi i 
    LEFT JOIN kategori k ON i.id_kategori = k.id_kategori 
    WHERE i.status = 'disetujui' AND i.target_audiens = 'publik' 
    ORDER BY i.tanggal DESC LIMIT 6
");
 $stmt->execute();
 $berita_publik = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTs An-Nahl - Website Resmi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #227226; --secondary: #f59e0b; --bg: #f8fafc; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Anti tertutup navbar */
        #beranda, #tentang, #berita {
            scroll-margin-top: 80px;
        }

        body { background: var(--bg); color: #333; overflow-x: hidden; }
        
        /* Navbar */
        .navbar { 
            background: var(--primary); 
            padding: 15px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            color: white; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            box-shadow: 0 2px 10px rgba(72, 165, 76, 0.1); 
            width: 100%;
        }
        .navbar h2 { font-size: 1.5rem; }
        .navbar-links a { color: white; text-decoration: none; margin-left: 25px; font-weight: 500; transition: color 0.3s; }
        .navbar-links a:hover { color: var(--secondary); }
        
        /* Hero Section */
        .hero { 
            background: linear-gradient(rgba(29, 88, 21, 0.85), rgba(26, 66, 22, 0.9)), url('foto/sekolah.jpg') center/cover no-repeat; 
            background-attachment: fixed; 
            color: white; 
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center; 
            padding: 0 50px; 
        }
        .hero h1 { font-size: 3.5rem; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .hero p { font-size: 1.2rem; max-width: 600px; margin-bottom: 40px; }
        .btn-hero { background: var(--secondary); color: white; padding: 15px 35px; border-radius: 30px; text-decoration: none; font-size: 1.2rem; font-weight: bold; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4); transition: transform 0.2s, box-shadow 0.2s; }
        .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(245, 158, 11, 0.6); }
        
        /* Container */
        .container { max-width: 1200px; margin: 80px auto; padding: 0 20px; }
        .section-title { text-align: center; margin-bottom: 50px; color: var(--primary); font-size: 2.2rem; position: relative; }
        .section-title::after { content: ''; display: block; width: 60px; height: 4px; background: var(--secondary); margin: 10px auto 0; border-radius: 2px; }

        /* Tentang Kami */
        .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; }
        .about-text p { line-height: 1.8; font-size: 1.05rem; color: #555; margin-bottom: 20px; text-align: justify; }
        
        /* PERBAIKAN CSS STATISTIK */
        .stats-row { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 25px; 
            margin-top: 40px; 
        }
        .stat-box { 
            background: white; 
            padding: 30px 20px; 
            border-radius: 12px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.06); 
            text-align: center; 
            border-top: 4px solid var(--primary); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-box:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
        }
        .stat-icon { 
            width: 55px; 
            height: 55px; 
            background: rgba(34, 114, 38, 0.1); /* Warna hijau muda transparan */
            border-radius: 50%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0 auto 15px auto; 
            color: var(--primary); 
            font-size: 1.4rem; 
        }
        .stat-box h3 { 
            color: var(--primary); 
            font-size: 2.5rem; 
            margin-bottom: 5px; 
            font-weight: 800; 
            line-height: 1; 
        }
        .stat-box p { 
            color: #555; 
            font-size: 0.9rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 0; 
        }
        /* AKHIR PERBAIKAN CSS STATISTIK */

        /* PERBAIKAN CSS TENTANG KAMI */
        .about-img { 
            height: 100%; 
            min-height: 400px; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            position: relative;
            overflow: hidden;
        }

        /* Berita Section */
        .news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; }
        .news-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
        .news-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .news-card-body { padding: 25px; }
        .news-card .badge-publik { background: var(--primary); color: white; padding: 5px 10px; border-radius: 5px; font-size: 0.75rem; }
        .news-card h3 { color: var(--primary); margin: 15px 0 10px; font-size: 1.2rem; }
        .news-card .meta { font-size: 0.85rem; color: #777; margin-bottom: 15px; display: flex; justify-content: space-between; }
        .news-card p { font-size: 0.95rem; line-height: 1.6; color: #555; }
        .read-more { color: var(--secondary); font-weight: 600; font-size: 0.9rem; margin-top: 15px; display: inline-block; }
        
        /* Footer */
        .footer { background: #1e293b; color: white; text-align: center; padding: 30px 20px; margin-top: 60px; }
        .footer p { margin-bottom: 10px; }
        .footer a { color: var(--secondary); text-decoration: none; }

        /* Modal Pop-up */
        .publik-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; }
        .publik-modal-overlay.show { display: flex; }
        .publik-modal { background: white; width: 90%; max-width: 600px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2); animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .publik-modal-header { padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .publik-modal-header h3 { color: var(--primary); font-size: 1.1rem; margin: 0; }
        .publik-modal-close { background: none; border: none; font-size: 1.2rem; color: #777; cursor: pointer; padding: 5px; }
        .publik-modal-close:hover { color: #333; }
        .publik-modal-body { padding: 25px; max-height: 60vh; overflow-y: auto; }
        .publik-modal-body .modal-meta { display: flex; gap: 10px; margin-bottom: 20px; font-size: 0.85rem; color: #666; }
        .publik-modal-body .modal-content-text { font-size: 1rem; line-height: 1.8; color: #444; white-space: pre-line; }
        .publik-modal-footer { padding: 15px 25px; border-top: 1px solid #eee; text-align: right; background: #f8fafc; }
        .btn-cancel { background: #e2e8f0; color: #475569; padding: 10px 25px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-cancel:hover { background: #cbd5e1; }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; flex-wrap: wrap; gap: 15px; }
            .navbar-links { width: 100%; display: flex; justify-content: center; gap: 15px; }
            .navbar-links a { margin-left: 0; }
            .hero h1 { font-size: 2.5rem; }
            .about-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; } /* Stat box stack vertically on mobile */
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <h2><i class="fas fa-school"></i> MTs An-Nahl</h2>
        <div class="navbar-links">
            <a href="#beranda">Beranda</a>
            <a href="#tentang">Tentang Kami</a>
            <a href="#berita">Berita</a>
            <a href="/web_sekolah/index.php" style="background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 20px;">Login Sistem</a>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero" id="beranda">
        <h1>Membangun Generasi Berprestasi</h1>
        <p>Turn Your Ambition into Achievement. Bergabunglah bersama kami dan wujudkan potensi terbaik putra-putri Anda di MTs An-Nahl.</p>
        <a href="/web_sekolah/index.php?show=register" class="btn-hero"><i class="fas fa-user-plus"></i> Daftar PPDB Sekarang</a>
    </div>

    <!-- Tentang Kami Section -->
    <div class="container" id="tentang">
        <h2 class="section-title">Tentang MTs An-Nahl</h2>
        <div class="about-grid">
            <div class="about-text">
                <p>MTs An-Nahl adalah lembaga pendidikan Islam yang berkomitmen untuk membentuk generasi berilmu, berakhlakul karimah, dan berprestasi. Dengan kurikulum terpadu dan lingkungan belajar yang kondusif, kami siap mengantar peserta didik menuju kesuksesan dunia dan akhirat.</p>
                <p>Kami percaya bahwa setiap siswa memiliki potensi luar biasa. Melalui bimbingan tenaga pengajar yang profesional dan fasilitas lengkap, MTs An-Nahl terus berinovasi dalam metode pembelajaran.</p>
                
                <!-- PERBAIKAN HTML STATISTIK (DITAMBAHKAN IKON) -->
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>50</h3>
                        <p>Siswa Aktif</p>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h3>15</h3>
                        <p>Tenaga Pengajar</p>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon"><i class="fas fa-award"></i></div>
                        <h3>15</h3>
                        <p>Tahun Berdiri</p>
                    </div>
                </div>
                <!-- AKHIR PERBAIKAN HTML STATISTIK -->

            </div>

            <div class="about-img" style="background: url('foto/foto.jpg') center/cover no-repeat;">
            </div>
        </div>
    </div>

    <!-- Berita & Informasi Publik -->
    <div class="container" id="berita">
        <h2 class="section-title">Berita & Pengumuman Terkini</h2>
        <?php if (empty($berita_publik)): ?>
            <p style="text-align:center; color:#777;">Belum ada berita publik saat ini.</p>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($berita_publik as $berita): ?>
                <div class="news-card" onclick="openBeritaModal(<?php echo $berita['id_info']; ?>)">
                    <div class="news-card-body">
                        <div class="meta">
                            <span class="badge-publik"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($berita['nama_kategori'] ?? 'Umum'); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($berita['tanggal'])); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($berita['judul']); ?></h3>
                        <p><?php echo substr(htmlspecialchars($berita['isi']), 0, 120); ?>...</p>
                        <div class="read-more">Baca Selengkapnya <i class="fas fa-arrow-right"></i></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Pop-up Berita -->
    <div class="publik-modal-overlay" id="beritaModal">
        <div class="publik-modal">
            <div class="publik-modal-header">
                <h3 id="modalJudul">Detail Berita</h3>
                <button class="publik-modal-close" onclick="closeBeritaModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="publik-modal-body" id="modalBody">
                <div class="modal-meta" id="modalMeta"></div>
                <div class="modal-content-text" id="modalIsi">Memuat berita...</div>
            </div>
            <div class="publik-modal-footer">
                <button class="btn-cancel" onclick="closeBeritaModal()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> MTs An-Nahl. Sistem Informasi Sekolah.</p>
        <p style="font-size: 0.9rem; color: #94a3b8;">Jl. Maunori No. 20, Kecamatan Nangaroro | <a href="/web_sekolah/index.php">Login Admin</a></p>
    </div>

    <!-- JavaScript -->
    <script>
    function openBeritaModal(id) {
        document.getElementById('beritaModal').classList.add('show');
        document.getElementById('modalIsi').innerText = 'Memuat berita...';
        document.getElementById('modalMeta').innerHTML = '';
        document.getElementById('modalJudul').innerText = 'Detail Berita';

        fetch(`/web_sekolah/actions/informasi_action.php?action=detail&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const b = data.data;
                    document.getElementById('modalJudul').innerText = b.judul;
                    document.getElementById('modalMeta').innerHTML = `
                        <span><i class="fas fa-tag"></i> ${b.nama_kategori || 'Umum'}</span>
                        <span><i class="fas fa-calendar-alt"></i> ${b.tanggal || '-'}</span>
                    `;
                    document.getElementById('modalIsi').innerText = b.isi;
                } else {
                    document.getElementById('modalIsi').innerText = 'Gagal memuat berita.';
                }
            })
            .catch(() => {
                document.getElementById('modalIsi').innerText = 'Terjadi kesalahan jaringan.';
            });
    }

    function closeBeritaModal() {
        document.getElementById('beritaModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('beritaModal');
        if (event.target == modal) {
            closeBeritaModal();
        }
    }
    </script>
</body>
</html>