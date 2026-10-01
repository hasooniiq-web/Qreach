import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// Charts read their colours from the design tokens (CSS variables), so light
// and dark mode each get a palette of their own and the charts redraw when the
// theme changes. Data comes from data-chart attributes (server-rendered, real
// numbers) because inline scripts are blocked by the CSP (`script-src 'self'`).

const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

function palette() {
    const dark = document.documentElement.dataset.theme === 'dark';
    return {
        text: css('--color-ink-500'),
        grid: css('--color-ink-100'),
        surface: css('--color-surface') || '#fff',
        // One accent + neutrals. Restrained on purpose: no rainbow.
        series: [css('--color-ink-900'), '#FFC107', css('--color-ink-400'), css('--color-status-danger')],
        area: dark ? 0.14 : 0.08,
    };
}

function hexToRgba(color, alpha) {
    const m = /^#([0-9a-f]{6})$/i.exec(color);
    if (!m) return color;
    const n = parseInt(m[1], 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
}

function build(canvas, config) {
    const p = palette();
    const doughnut = config.type === 'doughnut';

    return new Chart(canvas, {
        type: config.type ?? 'line',
        data: {
            labels: config.labels,
            datasets: config.datasets.map((dataset, i) => {
                if (doughnut) {
                    return {
                        label: dataset.label,
                        data: dataset.data,
                        backgroundColor: [p.series[0], p.series[1], p.series[2], p.series[3]],
                        borderColor: p.surface,
                        borderWidth: 3,
                        hoverOffset: 4,
                    };
                }
                const color = p.series[i % p.series.length];
                return {
                    label: dataset.label,
                    data: dataset.data,
                    borderColor: color,
                    backgroundColor: hexToRgba(color, p.area),
                    borderWidth: 2,
                    tension: 0.35,
                    fill: config.type !== 'bar',
                    pointRadius: config.labels.length <= 31 ? 2 : 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: color,
                };
            }),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: doughnut ? '72%' : undefined,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 500, easing: 'easeOutCubic' },
            plugins: {
                legend: {
                    display: !doughnut && config.datasets.length > 1,
                    position: 'top',
                    align: 'end',
                    labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, color: p.text, font: { family: 'Cairo', size: 12 } },
                },
                tooltip: {
                    rtl: true,
                    titleFont: { family: 'Cairo' },
                    bodyFont: { family: 'Cairo' },
                    backgroundColor: css('--color-ink-900'),
                    titleColor: css('--color-canvas'),
                    bodyColor: css('--color-canvas'),
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: !doughnut,
                },
            },
            scales: doughnut ? {} : {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: p.text, maxTicksLimit: 8, font: { family: 'Cairo', size: 11 } } },
                y: { beginAtZero: true, border: { display: false }, grid: { color: p.grid }, ticks: { color: p.text, precision: 0, maxTicksLimit: 5, font: { family: 'Cairo', size: 11 } } },
            },
        },
    });
}

const charts = [];

document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    const config = JSON.parse(canvas.dataset.chart);
    charts.push({ canvas, config, chart: build(canvas, config) });

    // Remove the skeleton placeholder once the chart has drawn.
    const box = canvas.closest('[data-chart-box]');
    if (box) {
        box.classList.remove('skeleton');
        box.style.background = 'transparent';
    }

    // Legend dots next to a donut take the same colours as its slices.
    if (config.type === 'doughnut') {
        const p = palette();
        document.querySelectorAll('[data-legend-dot]').forEach((dot) => {
            dot.style.background = p.series[Number(dot.dataset.legendDot)] ?? p.series[0];
        });
    }
});

// Redraw with the other palette when the theme is switched.
new MutationObserver(() => {
    charts.forEach((entry, i) => {
        entry.chart.destroy();
        charts[i].chart = build(entry.canvas, entry.config);
    });
    const p = palette();
    document.querySelectorAll('[data-legend-dot]').forEach((dot) => {
        dot.style.background = p.series[Number(dot.dataset.legendDot)] ?? p.series[0];
    });
}).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
