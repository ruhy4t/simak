const parseChartData = (element) => {
    try {
        return JSON.parse(element.dataset.chart);
    } catch {
        return null;
    }
};

const sharedOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 18,
                color: '#475569',
                font: { family: 'inherit', weight: 600 },
            },
        },
        tooltip: {
            backgroundColor: '#0f172a',
            padding: 12,
            cornerRadius: 10,
            titleFont: { family: 'inherit', weight: 700 },
            bodyFont: { family: 'inherit' },
        },
    },
};

const initializeTrendChart = (Chart, canvas) => {
    const data = parseChartData(canvas);
    if (!data || data.labels.length === 0) return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Mutasi Masuk',
                    data: data.masuk,
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.12)',
                    pointBackgroundColor: '#0ea5e9',
                    pointRadius: data.labels.length > 31 ? 2 : 3,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                },
                {
                    label: 'Mutasi Keluar',
                    data: data.keluar,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.08)',
                    pointBackgroundColor: '#f97316',
                    pointRadius: data.labels.length > 31 ? 2 : 3,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                },
            ],
        },
        options: {
            ...sharedOptions,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 12,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: {
                        color: '#64748b',
                        precision: 0,
                    },
                },
            },
        },
    });
};

const initializeStatusChart = (Chart, canvas) => {
    const data = parseChartData(canvas);
    if (!data || data.values.every((value) => value === 0)) return;

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: ['#f59e0b', '#f43f5e', '#10b981', '#8b5cf6', '#0ea5e9'],
                borderColor: '#ffffff',
                borderWidth: 4,
                hoverOffset: 8,
            }],
        },
        options: {
            ...sharedOptions,
            cutout: '68%',
            interaction: {
                mode: 'nearest',
                intersect: true,
            },
            plugins: {
                ...sharedOptions.plugins,
                legend: {
                    ...sharedOptions.plugins.legend,
                    position: 'bottom',
                },
            },
        },
    });
};

document.addEventListener('DOMContentLoaded', async () => {
    const trendCharts = document.querySelectorAll('[data-dashboard-trend-chart]');
    const statusCharts = document.querySelectorAll('[data-dashboard-status-chart]');

    if (trendCharts.length === 0 && statusCharts.length === 0) return;

    const { default: Chart } = await import('chart.js/auto');
    trendCharts.forEach((canvas) => initializeTrendChart(Chart, canvas));
    statusCharts.forEach((canvas) => initializeStatusChart(Chart, canvas));
});
