<?php

/**
 * GC-Stats — Resend verification-email controller
 *
 * A password-registered account can't log in until its email is verified
 * (see FortifyServiceProvider::authenticateUsing). Fortify's own
 * verification.send route needs an authenticated session to reach, which is
 * exactly what an unverified password user doesn't have — this is the
 * escape hatch so a user who never clicked the link (or whose session
 * expired first) isn't permanently locked out. Also reachable while logged
 * in, for a signed-in unverified user landing here directly.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Laravel\Fortify\Fortify;

class ResendVerificationController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.resend-verification', ['email' => $request->user()?->email]);
    }

    public function store(Request $request): RedirectResponse
    {
        // A logged-in user resends for their own account regardless of what
        // the (disabled, in that case) email field holds.
        if ($request->user()) {
            $user = $request->user();
        } else {
            Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255'],
            ])->validate();

            $user = User::where('email', $request->string('email'))->first();
        }

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Same message whether the address exists or not, so this can't be
        // used to probe which emails have an account.
        return back()->with('status', Fortify::VERIFICATION_LINK_SENT);
    }
}
