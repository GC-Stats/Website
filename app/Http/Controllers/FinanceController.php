<?php

/**
 * GC-Stats — Finance controller
 *
 * Renders the public Finance page: a transparent ledger of every income
 * and expense of the GC-Stats project, stored in the database and
 * managed via the internal API.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceController extends Controller
{
    private const RECENT_MONTHS = 12;

    public function index(): View
    {
        $entries = FinanceEntry::orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $grouped = $entries->groupBy(fn (FinanceEntry $entry) => $entry->entry_date->format('Y-m'));

        $cutoff = now()->subMonths(self::RECENT_MONTHS - 1)->startOfMonth();

        $recentEntries = $grouped->filter(
            fn ($_, $month) => Carbon::createFromFormat('Y-m', $month)->greaterThanOrEqualTo($cutoff)
        );

        $olderEntries = $grouped->filter(
            fn ($_, $month) => Carbon::createFromFormat('Y-m', $month)->lessThan($cutoff)
        );

        $sumsFor = function ($collection) {
            return [
                'EUR' => [
                    'income' => (float) $collection->where('type', 'income')->sum('amount_eur'),
                    'expense' => (float) $collection->where('type', 'expense')->sum('amount_eur'),
                ],
                'USD' => [
                    'income' => (float) $collection->where('type', 'income')->sum('amount_usd'),
                    'expense' => (float) $collection->where('type', 'expense')->sum('amount_usd'),
                ],
            ];
        };

        $totals = $sumsFor($entries);
        foreach ($totals as &$currencyTotals) {
            $currencyTotals['balance'] = $currencyTotals['income'] - $currencyTotals['expense'];
        }
        unset($currencyTotals);

        $currentYearEntries = $entries->filter(
            fn (FinanceEntry $entry) => $entry->entry_date->year === now()->year
        );

        $currentYear = $sumsFor($currentYearEntries);
        foreach ($currentYear as &$currencyTotals) {
            $currencyTotals['balance'] = $currencyTotals['income'] - $currencyTotals['expense'];
        }
        unset($currencyTotals);

        $monthsCount = max($grouped->count(), 1);

        $average = [];
        foreach ($totals as $currency => $currencyTotals) {
            $average[$currency] = [
                'income' => round($currencyTotals['income'] / $monthsCount, 2),
                'expense' => round($currencyTotals['expense'] / $monthsCount, 2),
            ];
            $average[$currency]['balance'] = $average[$currency]['income'] - $average[$currency]['expense'];
        }

        return view('transparency.finance', [
            'recentEntries' => $recentEntries,
            'olderEntries' => $olderEntries,
            'totals' => $totals,
            'currentYear' => $currentYear,
            'average' => $average,
        ]);
    }
}
