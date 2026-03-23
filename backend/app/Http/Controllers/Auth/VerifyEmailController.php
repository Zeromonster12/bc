<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\User;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified via signed email link.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/') . '/email-verification';

        if (! URL::hasValidSignature($request)) {
            return redirect()->to($frontendUrl . '?status=error&message=Invalid%20or%20expired%20verification%20link.');
        }

        $user = User::query()->find($id);

        if (! $user || ! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->to($frontendUrl . '?status=error&message=Invalid%20verification%20payload.');
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->to($frontendUrl . '?status=success');
    }
}
