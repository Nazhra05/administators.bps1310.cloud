/**
 * Toast Notification Utility for Dashboard Admin
 * BPS Kabupaten Solok Selatan
 * Menggantikan dialog alert() browser dengan notifikasi mengambang modern non-blocking.
 */

(function () {
    window.showToast = function (message, type = 'success', duration = 4000) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast-item toast-' + (type || 'info');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');

        const iconSymbol = type === 'success' ? '✓' : (type === 'error' ? '✕' : (type === 'warning' ? '⚠️' : 'ℹ️'));

        toast.innerHTML = `
            <div class="toast-icon-wrap">${iconSymbol}</div>
            <div class="toast-body">${message}</div>
            <button type="button" class="toast-close-btn" aria-label="Tutup notifikasi">&times;</button>
        `;

        const closeBtn = toast.querySelector('.toast-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                dismissToast(toast);
            });
        }

        container.appendChild(toast);

        // Trigger animasi masuk
        requestAnimationFrame(function () {
            toast.classList.add('toast-active');
        });

        // Auto dismiss jika duration > 0
        if (duration > 0) {
            setTimeout(function () {
                dismissToast(toast);
            }, duration);
        }

        return toast;
    };

    function dismissToast(toast) {
        if (!toast || toast.classList.contains('toast-exit')) return;
        toast.classList.remove('toast-active');
        toast.classList.add('toast-exit');
        setTimeout(function () {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
})();
