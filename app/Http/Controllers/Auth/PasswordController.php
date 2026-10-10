<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DoubleAuthentification as A2F;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Double authentification : un code, même depuis un appareil de confiance
        if (! A2F::confirmeRecemment($request)) {
            return A2F::exigerConfirmation($request, route('profile.edit'));
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        A2F::oublierAppareils($request->user());

        return back()->with('status', 'password-updated');
    }
}
