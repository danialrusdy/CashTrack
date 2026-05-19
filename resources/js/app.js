import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

// Count-up animation utility
window.countUp = function (element, target, duration = 1200) {
    const start = 0;
    const startTime = performance.now();
    const isFloat = target % 1 !== 0;

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = start + (target - start) * eased;

        if (element.dataset.prefix) {
            element.textContent = element.dataset.prefix + formatNumber(current, isFloat);
        } else {
            element.textContent = formatNumber(current, isFloat);
        }

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    requestAnimationFrame(update);
};

function formatNumber(num, isFloat) {
    if (isFloat) {
        return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    return Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Initialize count-up on all elements with data-count-target
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-count-target]').forEach(el => {
        const raw = el.dataset.countTarget.replace(/[^0-9.]/g, '');
        const target = parseFloat(raw) || 0;
        setTimeout(() => countUp(el, target), 200);
    });
});

// Confirm delete helper
window.confirmDelete = function (form) {
    if (confirm('Yakin ingin menghapus transaksi ini?')) {
        form.submit();
    }
};
