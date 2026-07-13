<?php

/**
 * GC-Stats — Transparency controller
 *
 * Renders the public Transparency page, presenting how GC-Stats is
 * developed, hosted and funded, with a short finance summary linking to
 * the full Finance ledger page.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use Illuminate\View\View;

class TransparencyController extends Controller
{
    public function index(): View
    {
        $totals = [
            'EUR' => [
                'income' => (float) FinanceEntry::where('type', 'income')->sum('amount_eur'),
                'expense' => (float) FinanceEntry::where('type', 'expense')->sum('amount_eur'),
            ],
            'USD' => [
                'income' => (float) FinanceEntry::where('type', 'income')->sum('amount_usd'),
                'expense' => (float) FinanceEntry::where('type', 'expense')->sum('amount_usd'),
            ],
        ];

        foreach ($totals as &$currencyTotals) {
            $currencyTotals['balance'] = $currencyTotals['income'] - $currencyTotals['expense'];
        }
        unset($currencyTotals);

        return view('transparency.index', [
            'totals' => $totals,
            'lastEntry' => FinanceEntry::orderByDesc('entry_date')->orderByDesc('id')->first(),
        ]);
    }
}
