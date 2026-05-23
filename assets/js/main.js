/* ============================================
   SPK Karyawan Terbaik - Main JavaScript
   ============================================ */

// ============ TOAST NOTIFICATIONS ============
const Toast = {
    container: null,

    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 4000) {
        this.init();
        const icons = {
            success: '✓',
            error:   '✕',
            warning: '⚠',
            info:    'ℹ'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg)   { this.show(msg, 'error'); },
    warning(msg) { this.show(msg, 'warning'); },
    info(msg)    { this.show(msg, 'info'); }
};

// ============ MODAL ============
const Modal = {
    open(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    },

    close(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    },

    closeAll() {
        document.querySelectorAll('.modal-overlay.show').forEach(m => {
            m.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
};

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        Modal.closeAll();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') Modal.closeAll();
});

// ============ SPINNER ============
const Spinner = {
    show(text = 'Memproses...') {
        let overlay = document.getElementById('spinner-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'spinner-overlay';
            overlay.className = 'spinner-overlay';
            overlay.innerHTML = `
                <div class="spinner"></div>
                <div class="spinner-text">${text}</div>
            `;
            document.body.appendChild(overlay);
        } else {
            overlay.querySelector('.spinner-text').textContent = text;
        }
        requestAnimationFrame(() => overlay.classList.add('show'));
    },

    hide() {
        const overlay = document.getElementById('spinner-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
};

// ============ DELETE CONFIRMATION ============
function confirmDelete(url, itemName) {
    const modal = document.getElementById('modal-delete');
    if (modal) {
        document.getElementById('delete-item-name').textContent = itemName || 'item ini';
        document.getElementById('btn-confirm-delete').onclick = () => {
            window.location.href = url;
        };
        Modal.open('modal-delete');
    } else {
        if (confirm(`Apakah Anda yakin ingin menghapus "${itemName}"?`)) {
            window.location.href = url;
        }
    }
}

// ============ SIDEBAR TOGGLE (mobile) ============
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

// ============ FORM VALIDATION ============
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return valid;
}

// ============ BOBOT TOTAL CHECKER ============
function checkBobotTotal() {
    const inputs = document.querySelectorAll('.bobot-input');
    let total = 0;
    inputs.forEach(input => {
        total += parseFloat(input.value) || 0;
    });

    const display = document.getElementById('bobot-total-display');
    if (display) {
        const rounded = Math.round(total * 10000) / 10000;
        display.textContent = rounded.toFixed(4);
        const container = display.closest('.bobot-total');
        if (container) {
            const isValid = Math.abs(rounded - 1.0) < 0.0001;
            container.classList.toggle('valid', isValid);
            container.classList.toggle('invalid', !isValid);
        }
    }
}

// ============ PENILAIAN GRID ============
function fillAllValues(value) {
    document.querySelectorAll('.penilaian-table input[type="number"]').forEach(input => {
        input.value = value;
    });
}

// ============ AUTO-DISMISS URL PARAMS ============
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);

    if (params.get('success') === '1') {
        Toast.success(params.get('msg') || 'Data berhasil disimpan.');
    } else if (params.get('success') === '0') {
        Toast.error(params.get('msg') || 'Terjadi kesalahan.');
    } else if (params.get('deleted') === '1') {
        Toast.success('Data berhasil dihapus.');
    } else if (params.get('error') === 'access_denied') {
        Toast.error('Akses ditolak. Anda tidak memiliki izin.');
    }

    // Clean URL
    if (params.has('success') || params.has('deleted') || params.has('error')) {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
    }

    // Init bobot checker
    document.querySelectorAll('.bobot-input').forEach(input => {
        input.addEventListener('input', checkBobotTotal);
    });
    checkBobotTotal();
});

// ============ EDIT FORM POPULATE ============
function populateEditForm(data, formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    Object.keys(data).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
            field.value = data[key];
            if (field.tagName === 'SELECT') {
                field.dispatchEvent(new Event('change'));
            }
        }
    });
}

// ============ NUMBER FORMAT ============
function formatNumber(num, decimals = 6) {
    return parseFloat(num).toFixed(decimals);
}

// ============ PERIODE FORMAT ============
function formatPeriode(periode) {
    if (!periode) return '-';
    const [year, month] = periode.split('-');
    const months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${months[parseInt(month)]} ${year}`;
}
