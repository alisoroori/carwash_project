document.addEventListener('DOMContentLoaded', function() {
    setupDateFilter();
    loadReports('month'); // Default to monthly view
});

function setupDateFilter() {
    const dateRange = document.getElementById('dateRange');
    const customRange = document.getElementById('customRange');
    const applyRange = document.getElementById('applyRange');

    dateRange.addEventListener('change', function(e) {
        if (e.target.value === 'custom') {
            customRange.style.display = 'flex';
        } else {
            customRange.style.display = 'none';
            loadReports(e.target.value);
        }
    });

    applyRange.addEventListener('click', function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        if (startDate && endDate) {
            loadReports('custom', { startDate, endDate });
        }
    });
}

async function loadReports(period, customDates = null) {
    try {
        let url = `/carwash_project/backend/api/admin/reports.php?period=${period}`;
        if (customDates) {
            url += `&start=${customDates.startDate}&end=${customDates.endDate}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            updateSummaryCards(data.summary);
            updateCharts(data.charts);
            updateDetailedReports(data.details);
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading reports:', error);
        showNotification('Failed to load reports', 'error');
    }
}

function updateSummaryCards(summary) {
    document.getElementById('totalRevenue').textContent = 
        `$${summary.revenue.toFixed(2)}`;
    document.getElementById('totalBookings').textContent = 
        summary.bookings;
    document.getElementById('avgRating').textContent = 
        summary.rating.toFixed(1);

    // Update trends
    updateTrend('revenueTrend', summary.revenueTrend);
    updateTrend('bookingsTrend', summary.bookingsTrend);
    updateTrend('ratingTrend', summary.ratingTrend);
}

function updateTrend(elementId, trend) {
    const element = document.getElementById(elementId);
    const percentage = Math.abs(trend).toFixed(1);
    element.className = `trend ${trend >= 0 ? 'up' : 'down'}`;
    element.textContent = `${trend >= 0 ? '↑' : '↓'} ${percentage}%`;
}

function updateCharts(chartData) {
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: chartData.revenue.labels,
            datasets: [{
                label: 'Revenue',
                data: chartData.revenue.data,
                borderColor: '#3498db',
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Popular Services Chart
    new Chart(document.getElementById('servicesChart'), {
        type: 'pie',
        data: {
            labels: chartData.services.labels,
            datasets: [{
                data: chartData.services.data,
                backgroundColor: [
                    '#2ecc71', '#3498db', '#9b59b6', 
                    '#f1c40f', '#e74c3c', '#1abc9c'
                ]
            }]
        }
    });

    // Bookings Chart
    new Chart(document.getElementById('bookingsChart'), {
        type: 'bar',
        data: {
            labels: chartData.bookings.labels,
            datasets: [{
                label: 'Bookings',
                data: chartData.bookings.data,
                backgroundColor: '#3498db'
            }]
        }
    });

    // Ratings Chart
    new Chart(document.getElementById('ratingsChart'), {
        type: 'bar',
        data: {
            labels: ['1★', '2★', '3★', '4★', '5★'],
            datasets: [{
                label: 'Ratings Distribution',
                data: chartData.ratings.data,
                backgroundColor: '#f1c40f'
            }]
        }
    });
}

function updateDetailedReports(details) {
    const tbody = document.getElementById('reportsTableBody');
    tbody.innerHTML = details.map(item => `
        <tr>
            <td>${item.carwash_name}</td>
            <td>${item.total_bookings}</td>
            <td>$${item.revenue.toFixed(2)}</td>
            <td>${item.avg_rating.toFixed(1)} ★</td>
            <td>${item.popular_service}</td>
        </tr>
    `).join('');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}