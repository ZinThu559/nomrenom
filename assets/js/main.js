/**
 * NomReNom - Main Client JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Enable Bootstrap tooltips if any
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss standard alerts after 7 seconds
    setTimeout(function () {
        var alerts = document.querySelectorAll('.flash-container .alert:not(.alert-permanent)');
        alerts.forEach(function (alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 7000);

    // Navbar scroll background enhancement
    var navbar = document.querySelector('.navbar-nomrenom');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 30) {
                navbar.classList.add('shadow-sm');
                navbar.style.backgroundColor = 'rgba(253, 251, 248, 0.98)';
            } else {
                navbar.classList.remove('shadow-sm');
                navbar.style.backgroundColor = 'rgba(253, 251, 248, 0.95)';
            }
        });
    }
});

/**
 * Format raw number to VND string in browser
 * @param {number} amount 
 * @returns {string}
 */
function formatVndJs(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
}
