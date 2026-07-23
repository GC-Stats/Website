/**
 * GC-Stats — Admin analytics chart
 *
 * Loaded only on /admin/analytics (separate Vite entry point, see
 * vite.config.js) so Chart.js never ships on the rest of the admin panel.
 * Reads its dataset from the JSON script tag rendered by
 * resources/views/admin/analytics/index.blade.php.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Legend,
    Tooltip,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip);

const REGION_COLORS = {
    EURO: '#facc15',
    AMER: '#60a5fa',
    APAC: '#4ade80',
    OTHE: '#a1a1aa',
};

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('admin-analytics-hourly-chart');
    const dataEl = document.getElementById('admin-analytics-hourly-data');

    if (!canvas || !dataEl) return;

    const { labels, regions } = JSON.parse(dataEl.textContent);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: Object.entries(regions).map(([region, values]) => ({
                label: region,
                data: values,
                borderColor: REGION_COLORS[region] || '#a1a1aa',
                backgroundColor: 'transparent',
                tension: 0.3,
                pointRadius: 0,
                borderWidth: 2,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } }, beginAtZero: true },
            },
            plugins: {
                legend: { labels: { color: '#9ca3af', font: { size: 10 } } },
            },
        },
    });
});
