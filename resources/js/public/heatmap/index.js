/**
 * GC-Stats — Player positions heatmap widget renderer
 *
 * Loaded only on /widget/heatmap (separate Vite entry point, see
 * vite.config.js). Reads the already-filtered, already-normalized (0-1)
 * position list from the JSON script tag rendered by
 * resources/views/public/widget/heatmap.blade.php (see
 * App\Services\HeatmapService for how x/y get normalized) and paints a
 * density overlay on the minimap canvas.
 *
 * Density is a real 2D histogram, not a canvas-compositing trick: points
 * are binned into a coarse grid, box-blurred a few passes (cheap stand-in
 * for a gaussian blur), then every cell is colored relative to the single
 * busiest cell in this render (cell density / max density). That ratio is
 * what makes the map "coherent" regardless of how much data is in play —
 * a zone with half the traffic of the hottest zone always renders at ~50%
 * intensity, whether the filtered dataset has 300 points or 30,000. An
 * earlier version accumulated soft dots via canvas alpha compositing
 * (even non-additive 'source-over' blending) instead of a real histogram;
 * alpha compositing asymptotically approaches fully opaque once a cell has
 * "enough" overlapping hits, and with a lightly-filtered dataset (e.g. one
 * agent across every team/match, no team/side narrowing) most of the map
 * qualifies as "enough" — everything looked saturated. A true histogram
 * normalized against its own max doesn't have that failure mode.
 *
 * The ramp's base hue is configurable per-request via the widget's `color`
 * query param (see WidgetController::heatmap(), read here off the canvas's
 * `data-color` attribute) — defaults to the GC-Stats dataviz blue
 * (#2a78d6). Sequential ramp = one hue, light-and-transparent (near zero
 * density) to dark-and-opaque (max density), same shape regardless of hue.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 * @link      https://github.com/GC-Stats/Website
 */

// Higher resolution than a first pass (96) needed — a wide blur kernel was
// making cell edges disappear but also visibly inflated the highlighted
// area past the actual data footprint. Smoothing comes from finer cells
// here instead, paired with a gentle blur (see buildDensityGrid) that
// mainly anti-aliases cell boundaries rather than spreading density out.
const GRID_SIZE = 160;

// Below this fraction of the busiest cell, a cell is treated as noise (a
// single stray point) and hidden outright rather than compressed.
const NOISE_FLOOR = 0.015;

// Gamma applied to the (already max-relative) ratio before it hits the
// color ramp. Linear ratios make secondary zones vanish: a real secondary
// route used at a third of the hottest chokepoint's traffic renders at
// ~33% and reads as empty next to a busy zone at 100%. A gamma < 1 (sqrt)
// lifts low/mid ratios more than high ones — 0.33 -> ~0.57 — without
// touching the endpoints (0 stays 0, 1 stays 1), so the single hottest
// zone still reads as the clear peak while secondary zones stay visible
// instead of being crushed toward transparent.
const CONTRAST_GAMMA = 0.5;

const DEFAULT_COLOR = '2a78d6';

function hexToRgb(hex) {
    const clean = hex.replace('#', '');
    const n = parseInt(clean.length === 3 ? clean.split('').map((c) => c + c).join('') : clean, 16);

    if (clean.length !== 6 && clean.length !== 3) return null;

    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

function mixToward(rgb, target, t) {
    return rgb.map((c, i) => c + (target[i] - c) * t);
}

function buildRamp(baseHex) {
    const base = hexToRgb(baseHex) || hexToRgb(DEFAULT_COLOR);
    const white = [255, 255, 255];
    const black = [0, 0, 0];

    return [
        { stop: 0, rgb: mixToward(base, white, 0.82), alpha: 0 },
        { stop: 0.15, rgb: mixToward(base, white, 0.62), alpha: 0.25 },
        { stop: 0.35, rgb: mixToward(base, white, 0.32), alpha: 0.45 },
        { stop: 0.55, rgb: base, alpha: 0.65 },
        { stop: 0.75, rgb: mixToward(base, black, 0.25), alpha: 0.8 },
        { stop: 1, rgb: mixToward(base, black, 0.55), alpha: 0.92 },
    ];
}

function rampColor(ramp, t) {
    t = Math.max(0, Math.min(1, t));

    let lower = ramp[0];
    let upper = ramp[ramp.length - 1];

    for (let i = 0; i < ramp.length - 1; i++) {
        if (t >= ramp[i].stop && t <= ramp[i + 1].stop) {
            lower = ramp[i];
            upper = ramp[i + 1];
            break;
        }
    }

    const span = upper.stop - lower.stop || 1;
    const localT = (t - lower.stop) / span;

    return {
        r: lower.rgb[0] + (upper.rgb[0] - lower.rgb[0]) * localT,
        g: lower.rgb[1] + (upper.rgb[1] - lower.rgb[1]) * localT,
        b: lower.rgb[2] + (upper.rgb[2] - lower.rgb[2]) * localT,
        a: lower.alpha + (upper.alpha - lower.alpha) * localT,
    };
}

// Separable box blur (radius-tap, so radius=2 is a 5-tap kernel), applied a
// few times — approximates a gaussian blur (central limit theorem) far
// cheaper than a real gaussian kernel at this grid resolution. A wider
// kernel run a few times smooths the grid-cell edges out before the
// upscale to canvas size, so individual cells don't read as visible
// squares once blown up.
function boxBlur(grid, size, radius) {
    const horizontal = new Float32Array(grid.length);

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            let sum = 0;
            let count = 0;

            for (let dx = -radius; dx <= radius; dx++) {
                const nx = x + dx;
                if (nx < 0 || nx >= size) continue;
                sum += grid[y * size + nx];
                count++;
            }

            horizontal[y * size + x] = sum / count;
        }
    }

    const blurred = new Float32Array(grid.length);

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            let sum = 0;
            let count = 0;

            for (let dy = -radius; dy <= radius; dy++) {
                const ny = y + dy;
                if (ny < 0 || ny >= size) continue;
                sum += horizontal[ny * size + x];
                count++;
            }

            blurred[y * size + x] = sum / count;
        }
    }

    return blurred;
}

function buildDensityGrid(positions) {
    let density = new Float32Array(GRID_SIZE * GRID_SIZE);

    for (const point of positions) {
        const gx = Math.min(GRID_SIZE - 1, Math.max(0, Math.floor(point.x * GRID_SIZE)));
        const gy = Math.min(GRID_SIZE - 1, Math.max(0, Math.floor(point.y * GRID_SIZE)));
        density[gy * GRID_SIZE + gx] += 1;
    }

    for (let pass = 0; pass < 4; pass++) {
        density = boxBlur(density, GRID_SIZE, 1);
    }

    return density;
}

function render(canvas, positions, ramp) {
    const wrapper = canvas.parentElement;
    const size = wrapper.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;

    canvas.width = size.width * dpr;
    canvas.height = size.height * dpr;
    canvas.style.width = `${size.width}px`;
    canvas.style.height = `${size.height}px`;

    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (positions.length === 0) return;

    const density = buildDensityGrid(positions);

    let max = 0;
    for (let i = 0; i < density.length; i++) {
        if (density[i] > max) max = density[i];
    }
    if (max === 0) return;

    const gridCanvas = document.createElement('canvas');
    gridCanvas.width = GRID_SIZE;
    gridCanvas.height = GRID_SIZE;

    const gridCtx = gridCanvas.getContext('2d');
    const imageData = gridCtx.createImageData(GRID_SIZE, GRID_SIZE);
    const data = imageData.data;

    for (let i = 0; i < density.length; i++) {
        const rawRatio = density[i] / max;
        const o = i * 4;

        if (rawRatio <= NOISE_FLOOR) {
            data[o + 3] = 0;
            continue;
        }

        const color = rampColor(ramp, Math.pow(rawRatio, CONTRAST_GAMMA));
        data[o] = color.r;
        data[o + 1] = color.g;
        data[o + 2] = color.b;
        data[o + 3] = color.a * 255;
    }

    gridCtx.putImageData(imageData, 0, 0);

    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(gridCanvas, 0, 0, canvas.width, canvas.height);

    // A light native blur on top of the upscaled grid — cheap (GPU-composited,
    // doesn't touch the underlying pixel data), just enough to anti-alias
    // the last hard cell edges the box blur + bilinear upscale left behind.
    // Kept small and non-scaling: a size-relative blur here previously grew
    // the highlighted area well past where the data actually is.
    canvas.style.filter = 'blur(3.5px)';
}

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('heatmap-canvas');
    const dataEl = document.getElementById('heatmap-data');

    if (!canvas || !dataEl) return;

    const positions = JSON.parse(dataEl.textContent);
    const ramp = buildRamp(canvas.dataset.color || DEFAULT_COLOR);

    render(canvas, positions, ramp);
    window.addEventListener('resize', () => render(canvas, positions, ramp));
});
