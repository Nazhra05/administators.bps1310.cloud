/**
 * BPS Solok Selatan - Master Dashboard JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.getElementById('mainContent');

    function toggleSidebar() {
        if (!sidebar) return;
        const isOpen = sidebar.classList.toggle('sidebar-open');
        if (overlay) overlay.classList.toggle('show', isOpen);
        if (mainContent && window.innerWidth < 1024) {
            mainContent.classList.toggle('sidebar-open', isOpen);
        }
    }

    if (mobileBtn) {
        mobileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            if (sidebar && sidebar.classList.contains('sidebar-open')) {
                toggleSidebar();
            }
        });
    }

    // 2. Admin Dropdown Toggle
    const adminStatusBtn = document.getElementById('adminStatusBtn');
    const adminMenu = document.getElementById('adminMenu');

    if (adminStatusBtn && adminMenu) {
        adminStatusBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            adminMenu.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!adminStatusBtn.contains(e.target) && !adminMenu.contains(e.target)) {
                adminMenu.classList.remove('show');
            }
        });
    }

    // 3. Confirm Dialog on Forms / Actions
    document.querySelectorAll('[data-confirm]').forEach((elem) => {
        elem.addEventListener('click', (e) => {
            const msg = elem.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // 4. Auto-dismiss Flash Alerts after 6 seconds
    document.querySelectorAll('.alert-auto-dismiss').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 6000);
    });
});
