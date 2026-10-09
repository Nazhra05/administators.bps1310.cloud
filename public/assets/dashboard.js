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
            const isOpen = adminMenu.classList.toggle('show');
            adminStatusBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (!adminStatusBtn.contains(e.target) && !adminMenu.contains(e.target)) {
                adminMenu.classList.remove('show');
                adminStatusBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && adminMenu.classList.contains('show')) {
                adminMenu.classList.remove('show');
                adminStatusBtn.setAttribute('aria-expanded', 'false');
                adminStatusBtn.focus();
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

    // 5. Pencegahan Klik Ganda & Indikator Proses (Task 7.13)
    document.querySelectorAll('form').forEach((form) => {
        if ((form.method || '').toUpperCase() === 'GET') return;

        form.addEventListener('submit', (e) => {
            // Jika konfirmasi dialog dibatalkan oleh pengguna, jangan lanjutkan
            if (e.defaultPrevented) return;

            // Periksa validitas formulir standar HTML5
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }

            if (form.dataset.submitting === 'true') {
                e.preventDefault();
                return false;
            }

            form.dataset.submitting = 'true';

            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                const loadingText = submitBtn.getAttribute('data-loading-text') || 'Menyimpan...';
                submitBtn.disabled = true;

                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = '<span class="spinner"></span> ' + loadingText;
                } else {
                    submitBtn.value = loadingText;
                }
            }
        });
    });

    // 6. Live Preview Unggah Logo Layanan (Task 7.13)
    document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
        const previewTargetId = input.getAttribute('data-preview');
        const previewContainer = document.getElementById(previewTargetId);
        const errorContainer = document.getElementById(previewTargetId + '_error');
        if (!previewContainer) return;

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) {
                return;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                if (errorContainer) {
                    errorContainer.textContent = 'Format berkas tidak didukung. Harap pilih gambar JPG, PNG, atau WebP.';
                    errorContainer.style.display = 'block';
                }
                input.value = '';
                previewContainer.style.display = 'none';
                return;
            }

            if (file.size > maxSize) {
                const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                if (errorContainer) {
                    errorContainer.textContent = 'Ukuran berkas terlalu besar (' + sizeMb + ' MB). Batas maksimum adalah 2 MB.';
                    errorContainer.style.display = 'block';
                }
                input.value = '';
                previewContainer.style.display = 'none';
                return;
            }

            if (errorContainer) {
                errorContainer.textContent = '';
                errorContainer.style.display = 'none';
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = previewContainer.querySelector('img');
                if (img) {
                    img.src = e.target.result;
                }
                const nameLabel = previewContainer.querySelector('.preview-filename');
                if (nameLabel) {
                    nameLabel.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                }
                previewContainer.style.display = 'flex';
            };
            reader.readAsDataURL(file);
        });
    });
});
