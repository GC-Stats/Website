<?php

/**
 * GC-Stats — Social account management controller
 *
 * Lets an authenticated user unlink one of their connected providers, as
 * long as at least one auth method remains — see AccountSecurityService.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Exceptions\LastAuthMethodException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\AccountSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function destroy(
        Request $request,
        SocialAccount $socialAccount,
        AccountSecurityService $accountSecurity,
    ): RedirectResponse {
        abort_unless($socialAccount->user_id === $request->user()->id, 403);

        try {
            $accountSecurity->unlinkProvider($request->user(), $socialAccount);
        } catch (LastAuthMethodException $e) {
            return back()->withErrors(['social' => $e->getMessage()]);
        }

        return back()->with('status', 'provider-unlinked');
    }
}
