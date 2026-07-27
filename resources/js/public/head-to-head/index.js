/**
 * GC-Stats — Face to Face radar chart
 *
 * Loaded only on pages that render <x-public.head-to-head> (team/tournament
 * maps pages, the match page, and the standalone OBS broadcast widget — see
 * their @vite() calls) so Chart.js never ships on the rest of the site.
 * Reads its dataset from the JSON script tag rendered alongside each
 * `[data-h2h-id]` widget. Win rate only, one polygon per team; a custom
 * plugin draws each team's "wins/played" record directly under the map's
 * axis label (two-color text isn't something Chart.js's own pointLabels
 * option can render), so the sample size sits right on the chart instead
 * of a separate list. In solo mode (no `payload.team_b`), a second,
 * unlabeled, low-opacity dataset (`payload.played`) is drawn behind the
 * win% polygon so relative sample size across maps reads visually too.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

import {
    Chart,
    RadarController,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
} from 'chart.js';

Chart.register(RadarController, RadialLinearScale, PointElement, LineElement, Filler, Tooltip);

// Draws "wins/played · wins/played" (each half colored to its team) just
// below each map's name, reusing Chart.js's own computed label positions
// (scale._pointLabelItems) so it lines up regardless of chart size/wrapping.
const recordLabelsPlugin = {
    id: 'h2hRecordLabels',
    afterDraw(chart, args, opts) {
        const scale = chart.scales.r;

        if (!opts.record || !scale || !scale._pointLabelItems) return;

        const { ctx } = chart;
        ctx.save();
        ctx.font = '600 10px sans-serif';
        ctx.textBaseline = 'top';

        scale._pointLabelItems.forEach((item, i) => {
            if (!item.visible) return;

            const textA = opts.record.a[i];
            const textB = opts.record.b ? opts.record.b[i] : null;
            if (!textA) return;

            const y = item.bottom + 2;

            if (!textB) {
                const widthA = ctx.measureText(textA).width;
                let startX = item.left;
                if (item.textAlign === 'center') startX = item.left + (item.right - item.left - widthA) / 2;
                else if (item.textAlign === 'right') startX = item.right - widthA;

                ctx.textAlign = 'left';
                ctx.fillStyle = opts.colorA;
                ctx.fillText(textA, startX, y);
                return;
            }

            const sep = ' · ';
            const widthA = ctx.measureText(textA).width;
            const widthSep = ctx.measureText(sep).width;
            const widthB = ctx.measureText(textB).width;
            const total = widthA + widthSep + widthB;

            let startX = item.left;
            if (item.textAlign === 'center') startX = item.left + (item.right - item.left - total) / 2;
            else if (item.textAlign === 'right') startX = item.right - total;

            ctx.textAlign = 'left';
            ctx.fillStyle = opts.colorA;
            ctx.fillText(textA, startX, y);
            ctx.fillStyle = 'rgba(255,255,255,0.35)';
            ctx.fillText(sep, startX + widthA, y);
            ctx.fillStyle = opts.colorB;
            ctx.fillText(textB, startX + widthA + widthSep, y);
        });

        ctx.restore();
    },
};

Chart.register(recordLabelsPlugin);

function buildChart(widget) {
    const id = widget.dataset.h2hId;
    const dataEl = document.getElementById(`h2h-data-${id}`);
    const canvas = document.getElementById(`h2h-canvas-${id}`);

    if (!dataEl || !canvas) return;

    const payload = JSON.parse(dataEl.textContent);

    const datasets = [];

    if (payload.played) {
        datasets.push({
            label: false,
            data: payload.played,
            borderColor: 'rgba(255,255,255,0.18)',
            backgroundColor: 'rgba(255,255,255,0.06)',
            pointRadius: 0,
            borderWidth: 1,
            fill: true,
            order: 2,
        });
    }

    datasets.push({
        label: payload.team_a.name,
        data: payload.win.a,
        trueValues: payload.winRate.a,
        borderColor: payload.team_a.color,
        backgroundColor: `${payload.team_a.color}26`,
        pointBackgroundColor: payload.team_a.color,
        borderWidth: 2,
        pointRadius: 3,
        order: 1,
    });

    if (payload.team_b) {
        datasets.push({
            label: payload.team_b.name,
            data: payload.win.b,
            trueValues: payload.winRate.b,
            borderColor: payload.team_b.color,
            backgroundColor: `${payload.team_b.color}26`,
            pointBackgroundColor: payload.team_b.color,
            borderWidth: 2,
            pointRadius: 3,
        });
    }

    new Chart(canvas, {
        type: 'radar',
        data: {
            labels: payload.labels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 16,
            },
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    grid: { color: 'rgba(255,255,255,0.08)' },
                    angleLines: { color: 'rgba(255,255,255,0.08)' },
                    pointLabels: {
                        color: '#e5e7eb',
                        font: { size: 11, weight: 'bold' },
                    },
                    ticks: {
                        display: false,
                        backdropColor: 'transparent',
                    },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    filter: (item) => item.dataset.label !== false,
                    callbacks: {
                        label: (ctx) => {
                            const value = ctx.dataset.trueValues ? ctx.dataset.trueValues[ctx.dataIndex] : ctx.formattedValue;
                            return `${ctx.dataset.label}: ${value}%`;
                        },
                    },
                },
                h2hRecordLabels: {
                    record: payload.record,
                    colorA: payload.team_a.color,
                    colorB: payload.team_b ? payload.team_b.color : null,
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-h2h-id]').forEach(buildChart);
});
