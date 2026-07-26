/**
 * GC-Stats — Developers: usage statistics chart
 *
 * Loaded only on /developers/stats (separate Vite entry point, see
 * vite.config.js) so Chart.js never ships on the rest of the developer
 * panel. Reads its dataset from the JSON script tag rendered by
 * resources/views/developers/stats/index.blade.php.
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

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('developers-stats-chart');
    const dataEl = document.getElementById('developers-stats-data');

    if (!canvas || !dataEl) return;

    const { labels, requests, errors, labelRequests, labelErrors } = JSON.parse(dataEl.textContent);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: labelRequests,
                    data: requests,
                    borderColor: '#facc15',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                },
                {
                    label: labelErrors,
                    data: errors,
                    borderColor: '#f87171',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                },
            ],
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
