<?php

namespace App\Actions\Fortify;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\ProfileInformationUpdatedResponse as ProfileInformationUpdatedResponseContract;
use Laravel\Fortify\Fortify;

/**
 * Fortify's own response redirects via back(), which resolves to the
 * request's Referer header before falling back to the session's stored
 * previous URL (see UrlGenerator::previous()). A stripped/absent Referer
 * (privacy settings, some proxies) then falls back to the last GET the
 * session recorded — which, since this endpoint only accepts PUT, is never
 * itself, but can still land somewhere other than the account settings
 * page. Redirecting to the named route directly is unambiguous.
 */
class ProfileInformationUpdatedResponse implements ProfileInformationUpdatedResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 200)
            : redirect()->route('account.edit')->with('status', Fortify::PROFILE_INFORMATION_UPDATED);
    }
}
