<?php

/**
 * GC-Stats — Data Explorer documentation
 *
 * Static guide aimed at non-technical users: why an AI query costs money,
 * how to set a spending limit before creating a provider API key, and how
 * to actually create one on OpenAI/Anthropic. Linked from both the query
 * screen and the settings page.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\DataExplorer;

use App\Http\Controllers\Public\Controller;
use Illuminate\Contracts\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('data-explorer.docs');
    }
}
