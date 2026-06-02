// ============================================
// SISTEM INFORMASI MTSs AN-NAHL
// JAVASCRIPT UTAMA
// ============================================

document.addEventListener('DOMContentLoaded', function() {

    // ---- Auth Tabs ----
    const authTabs = document.querySelectorAll('.auth-tab');
    const authForms = document.querySelectorAll('.auth-form');

    authTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;
            authTabs.forEach(t => t.classList.remove('active'));
            authForms.forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    // ---- Mobile Sidebar ----
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('show');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // ---- User Dropdown ----
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.getElementById('userMenu');

    if (userDropdown && userMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });

        document.addEventListener('click', function() {
            userMenu.classList.remove('show');
        });
    }

    // ---- Modal ----
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    // Tutup modal klik overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // ---- Toast Notifications ----
    window.showToast = function(message, type = 'success') {
        const container = document.querySelector('.toast-container') || createToastContainer();
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="${icons[type] || icons.info}"></i>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    // ---- Flash Message ----
    const flash = document.querySelector('.alert');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            flash.style.transition = 'all 0.3s ease';
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    }

    // ---- Form Validasi ----
    window.validateForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;

        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = 'var(--danger)';
                valid = false;
            } else {
                field.style.borderColor = 'var(--border)';
            }
        });
        return valid;
    };

    // ---- Konfirmasi Hapus ----
    window.confirmDelete = function(message) {
        return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
    };

    // ---- Search Filter Table ----
    window.filterTable = function(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    const rows = table.querySelectorAll('tbody tr');

    input.oninput = function () {
        const filter = this.value.toLowerCase();

        rows.forEach(row => {
            row.style.display =
                row.textContent.toLowerCase().includes(filter)
                ? ''
                : 'none';
        });
    };
};
    // ---- Format Tanggal ----
    window.formatDate = function(dateStr) {
        if (!dateStr) return '-';
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        const d = new Date(dateStr);
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    };

    // ---- Auto dismiss alert setelah AJAX ----
    window.handleAjaxResponse = function(response, successCallback) {
        if (response.success) {
            showToast(response.message || 'Berhasil!', 'success');
            if (successCallback) successCallback();
        } else {
            showToast(response.message || 'Terjadi kesalahan!', 'error');
        }
    };

    // ---- Sidebar active link highlight ----
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        }
    });

});