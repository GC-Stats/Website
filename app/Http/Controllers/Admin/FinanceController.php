<?php

/**
 * GC-Stats — Admin: finance ledger
 *
 * CRUD over the public finance ledger (App\Models\FinanceEntry) shown on
 * the transparency page. A single amount + currency is entered here and
 * converted into both amount_usd/amount_eur via App\Support\ExchangeRate —
 * the public page only ever reads the stored pair, never converts live.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceEntry;
use App\Support\ExchangeRate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public const CATEGORIES = [
        'Donation',
        'Advertising',
        'Infrastructure',
        'Software & Licenses',
        'Other',
    ];

    public const CURRENCIES = ['EUR', 'USD'];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $type = $request->get('type');

        $entries = FinanceEntry::query()
            ->when($search, fn ($query) => $query->where('label', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.index', [
            'entries' => $entries,
            'search' => $search ?? '',
            'type' => $type ?? '',
            'categories' => self::CATEGORIES,
            'currencies' => self::CURRENCIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCommon($request);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in(self::CURRENCIES)],
        ]);

        $amount = (float) $validated['amount'];
        $eurToUsd = ExchangeRate::eurToUsd();

        if ($validated['currency'] === 'EUR') {
            $amountEur = $amount;
            $amountUsd = $amount * $eurToUsd;
        } else {
            $amountUsd = $amount;
            $amountEur = $amount / $eurToUsd;
        }

        $entry = FinanceEntry::create([
            ...$data,
            'amount_usd' => round($amountUsd, 2),
            'amount_eur' => round($amountEur, 2),
        ]);

        activity('administration')->causedBy($request->user())
            ->performedOn($entry)->log('finance_entry.created');

        return back()->with('status', 'finance-entry-created');
    }

    public function update(Request $request, FinanceEntry $entry): RedirectResponse
    {
        $data = $this->validateCommon($request);

        $amounts = $request->validate([
            'amount_eur' => ['required', 'numeric', 'min:0.01'],
            'amount_usd' => ['required', 'numeric', 'min:0.01'],
        ]);

        $entry->update([...$data, ...$amounts]);

        activity('administration')->causedBy($request->user())
            ->performedOn($entry)->log('finance_entry.updated');

        return back()->with('status', 'finance-entry-updated');
    }

    public function destroy(Request $request, FinanceEntry $entry): RedirectResponse
    {
        $entry->delete();

        activity('administration')->causedBy($request->user())->log('finance_entry.deleted');

        return back()->with('status', 'finance-entry-deleted');
    }

    private function validateCommon(Request $request): array
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'custom_category' => ['required_if:category,Other', 'nullable', 'string', 'max:50'],
            'label' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'source_url' => ['nullable', 'url', 'max:255'],
        ]);

        return [
            'entry_date' => $data['entry_date'],
            'type' => $data['type'],
            'category' => $data['category'] === 'Other' ? $data['custom_category'] : $data['category'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'source_url' => $data['source_url'] ?? null,
        ];
    }
}
