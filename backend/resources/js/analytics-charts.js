import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const BRAND_YELLOW = '#FFC107';
const INK_700 = '#262626';
const INK_200 = '#D4D4D4';

// Reads chart definitions from data-chart-* attributes rather than inline
// <script> blocks — inline scripts are blocked by this app's own CSP
// (script-src 'self'), and this keeps chart data out of the JS bundle so
// it stays server-rendered, real data (never fabricated in JS).
document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    const config = JSON.parse(canvas.dataset.chart);

    new Chart(canvas, {
        type: config.type ?? 'line',
        data: {
            labels: config.labels,
            datasets: config.datasets.map((dataset, i) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: i === 0 ? BRAND_YELLOW : INK_700,
                backgroundColor: i === 0 ? 'rgba(255, 193, 7, 0.15)' : 'rgba(38, 38, 38, 0.08)',
                borderWidth: 2,
                tension: 0.3,
                fill: config.type !== 'bar',
                pointRadius: 2,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: config.datasets.length > 1, labels: { usePointStyle: true } },
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: INK_200 }, ticks: { precision: 0 } },
            },
        },
    });
});
