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

    return new Chart(canvas, {
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

    return new Chart(canvas, {
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

const initializeReportTrendChart = (Chart, canvas) => {
    const data = parseChartData(canvas);
    if (!data || !Array.isArray(data.labels) || !Array.isArray(data.datasets)) return;

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.values,
                borderColor: dataset.color,
                backgroundColor: `${dataset.color}20`,
                pointBackgroundColor: dataset.color,
                borderDash: dataset.dashed ? [5, 5] : [],
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHitRadius: 10,
                pointHoverRadius: 5,
            })),
        },
        options: {
            ...sharedOptions,
            plugins: {
                ...sharedOptions.plugins,
                legend: {
                    ...sharedOptions.plugins.legend,
                    position: 'top',
                    align: 'end',
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', autoSkip: true, maxTicksLimit: 12 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { color: '#64748b', precision: 0 },
                },
            },
        },
    });
};

const initializeClassDistributionChart = (Chart, canvas) => {
    const data = parseChartData(canvas);
    if (!data || !Array.isArray(data.labels) || !Array.isArray(data.datasets)) return;

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: data.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.values,
                backgroundColor: dataset.color,
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 72,
            })),
        },
        options: {
            ...sharedOptions,
            plugins: {
                ...sharedOptions.plugins,
                legend: {
                    ...sharedOptions.plugins.legend,
                    position: 'top',
                    align: 'end',
                },
                tooltip: {
                    ...sharedOptions.plugins.tooltip,
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y.toLocaleString('id-ID')} murid`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#475569', font: { weight: 700 } } },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { color: '#64748b', precision: 0 },
                },
            },
        },
    });
};

const initializeCohortChart = (Chart, canvas) => {
    const data = parseChartData(canvas);
    if (!data || !Array.isArray(data.labels) || !Array.isArray(data.values)) return;

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Jumlah murid',
                data: data.values,
                    backgroundColor: ['#64748b', '#2563eb', '#0ea5e9', '#f97316', '#e11d48', '#6366f1', '#10b981'],
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 64,
            }],
        },
        options: {
            ...sharedOptions,
            plugins: {
                ...sharedOptions.plugins,
                legend: { display: false },
                tooltip: {
                    ...sharedOptions.plugins.tooltip,
                    callbacks: {
                        label: (context) => `${Math.abs(context.parsed.y).toLocaleString('id-ID')} murid`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#475569', font: { weight: 600 } } },
                y: {
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { color: '#64748b', precision: 0 },
                },
            },
        },
    });
};

const chartInstances = new Map();

const initializeDashboardCharts = async () => {
    const trendCharts = document.querySelectorAll('[data-dashboard-trend-chart]');
    const statusCharts = document.querySelectorAll('[data-dashboard-status-chart]');
    const reportTrendCharts = document.querySelectorAll('[data-report-trend-chart]');
    const classDistributionCharts = document.querySelectorAll('[data-class-distribution-chart]');
    const cohortCharts = document.querySelectorAll('[data-cohort-chart]');

    if (trendCharts.length === 0 && statusCharts.length === 0 && reportTrendCharts.length === 0 && classDistributionCharts.length === 0 && cohortCharts.length === 0) return;

    const { default: Chart } = await import('chart.js/auto');
    trendCharts.forEach((canvas) => {
        if (chartInstances.has(canvas) || !canvas.isConnected) return;
        const chart = initializeTrendChart(Chart, canvas);
        if (chart) chartInstances.set(canvas, chart);
    });
    statusCharts.forEach((canvas) => {
        if (chartInstances.has(canvas) || !canvas.isConnected) return;
        const chart = initializeStatusChart(Chart, canvas);
        if (chart) chartInstances.set(canvas, chart);
    });
    reportTrendCharts.forEach((canvas) => {
        if (chartInstances.has(canvas) || !canvas.isConnected) return;
        const chart = initializeReportTrendChart(Chart, canvas);
        if (chart) chartInstances.set(canvas, chart);
    });
    classDistributionCharts.forEach((canvas) => {
        if (chartInstances.has(canvas) || !canvas.isConnected) return;
        const chart = initializeClassDistributionChart(Chart, canvas);
        if (chart) chartInstances.set(canvas, chart);
    });
    cohortCharts.forEach((canvas) => {
        if (chartInstances.has(canvas) || !canvas.isConnected) return;
        const chart = initializeCohortChart(Chart, canvas);
        if (chart) chartInstances.set(canvas, chart);
    });
};

document.addEventListener('DOMContentLoaded', initializeDashboardCharts);
document.addEventListener('simak:navigated', initializeDashboardCharts);
document.addEventListener('simak:before-navigate', (event) => {
    const root = event.detail?.root;
    if (!root) return;

    for (const [canvas, chart] of chartInstances) {
        if (!root.contains(canvas)) continue;
        chart.destroy();
        chartInstances.delete(canvas);
    }
});
