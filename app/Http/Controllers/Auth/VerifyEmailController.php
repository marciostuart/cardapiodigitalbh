<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Verify the email address proven by the signed link.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $currentUser = $request->user();

        if ($currentUser && $currentUser->is($user)) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($currentUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->to(route('login', absolute: false))
            ->with('status', 'email-verified');
    }
}
