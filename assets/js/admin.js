/**
 * NomReNom - Admin Dashboard & Analytics (Chart.js)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 0. Mobile & Tablet Sidebar Navigation Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            adminSidebar.classList.toggle('show');
        });

        // Close sidebar on tap outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && adminSidebar.classList.contains('show')) {
                if (!adminSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    adminSidebar.classList.remove('show');
                }
            }
        });
    }

    // 1. Daily Reservations & Collections Trend (Line Chart)
    const trendsCtx = document.getElementById('reservationsTrendChart');
    if (trendsCtx && typeof Chart !== 'undefined') {
        const trendLabels = JSON.parse(trendsCtx.getAttribute('data-labels') || '[]');
        const trendReservations = JSON.parse(trendsCtx.getAttribute('data-reservations') || '[]');
        const trendCollected = JSON.parse(trendsCtx.getAttribute('data-collected') || '[]');

        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Total Reservations',
                        data: trendReservations,
                        borderColor: '#2D6A4F',
                        backgroundColor: 'rgba(45, 106, 79, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Successfully Collected',
                        data: trendCollected,
                        borderColor: '#D4A373',
                        backgroundColor: 'rgba(212, 163, 115, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // 2. Snack Box vs Meal Box Ratio (Doughnut Chart)
    const boxRatioCtx = document.getElementById('boxRatioChart');
    if (boxRatioCtx && typeof Chart !== 'undefined') {
        const snackCount = parseInt(boxRatioCtx.getAttribute('data-snack') || 0, 10);
        const mealCount = parseInt(boxRatioCtx.getAttribute('data-meal') || 0, 10);

        new Chart(boxRatioCtx, {
            type: 'doughnut',
            data: {
                labels: ['Snack Box (40,000 VND)', 'Meal Box (70,000 VND)'],
                datasets: [{
                    data: [snackCount, mealCount],
                    backgroundColor: ['#E76F51', '#1B4332'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
