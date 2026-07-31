<?php

/**
 * GC-Stats — Valorant minimap calibration
 *
 * Per-map coefficients for converting Riot's raw in-game x/y position
 * coordinates (as stored on game_map_round_player_positions) into a
 * normalized 0-1 position on the map's square tactical minimap image.
 * Sourced from valorant-api.com's /v1/maps endpoint (xMultiplier,
 * yMultiplier, xScalarToAdd, yScalarToAdd) — the same coefficients Riot's
 * own tooling and every community stats tracker use for this conversion.
 *
 * Formula (note the raw axes are cross-wired — verified empirically against
 * known bomb site plant coordinates, see App\Services\HeatmapService):
 *   xMap = x_multiplier * riotY + x_scalar
 *   yMap = y_multiplier * riotX + y_scalar
 *   pixelX = xMap * imageWidth
 *   pixelY = yMap * imageHeight
 *
 * `image` is relative to public/storage, matching how the existing
 * public/storage/maps/*.webp banners are referenced elsewhere in the app.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [
    'ascent' => ['x_multiplier' => 0.00007, 'y_multiplier' => -0.00007, 'x_scalar' => 0.813895, 'y_scalar' => 0.573242, 'image' => 'maps/tactical/ascent.png'],
    'split' => ['x_multiplier' => 0.000078, 'y_multiplier' => -0.000078, 'x_scalar' => 0.842188, 'y_scalar' => 0.697578, 'image' => 'maps/tactical/split.png'],
    'fracture' => ['x_multiplier' => 0.000078, 'y_multiplier' => -0.000078, 'x_scalar' => 0.556952, 'y_scalar' => 1.155886, 'image' => 'maps/tactical/fracture.png'],
    'bind' => ['x_multiplier' => 0.000059, 'y_multiplier' => -0.000059, 'x_scalar' => 0.576941, 'y_scalar' => 0.967566, 'image' => 'maps/tactical/bind.png'],
    'breeze' => ['x_multiplier' => 0.00007, 'y_multiplier' => -0.00007, 'x_scalar' => 0.465123, 'y_scalar' => 0.833078, 'image' => 'maps/tactical/breeze.png'],
    'abyss' => ['x_multiplier' => 0.000081, 'y_multiplier' => -0.000081, 'x_scalar' => 0.5, 'y_scalar' => 0.5, 'image' => 'maps/tactical/abyss.png'],
    'lotus' => ['x_multiplier' => 0.000072, 'y_multiplier' => -0.000072, 'x_scalar' => 0.454789, 'y_scalar' => 0.917752, 'image' => 'maps/tactical/lotus.png'],
    'sunset' => ['x_multiplier' => 0.000078, 'y_multiplier' => -0.000078, 'x_scalar' => 0.5, 'y_scalar' => 0.515625, 'image' => 'maps/tactical/sunset.png'],
    'pearl' => ['x_multiplier' => 0.000078, 'y_multiplier' => -0.000078, 'x_scalar' => 0.480469, 'y_scalar' => 0.916016, 'image' => 'maps/tactical/pearl.png'],
    'summit' => ['x_multiplier' => 0.000075, 'y_multiplier' => -0.000075, 'x_scalar' => 0.047401, 'y_scalar' => 0.978891, 'image' => 'maps/tactical/summit.png'],
    'icebox' => ['x_multiplier' => 0.000072, 'y_multiplier' => -0.000072, 'x_scalar' => 0.460214, 'y_scalar' => 0.304687, 'image' => 'maps/tactical/icebox.png'],
    'corrode' => ['x_multiplier' => 0.00007, 'y_multiplier' => -0.00007, 'x_scalar' => 0.526158, 'y_scalar' => 0.5, 'image' => 'maps/tactical/corrode.png'],
    'haven' => ['x_multiplier' => 0.000075, 'y_multiplier' => -0.000075, 'x_scalar' => 1.09345, 'y_scalar' => 0.642728, 'image' => 'maps/tactical/haven.png'],
];
