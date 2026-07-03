<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForcePasswordChangeController extends Controller
{
    public function show()
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();

        $user->password                   = Hash::make($request->password);
        $user->temp_password              = null;
        $user->temp_password_expires_at   = null;
        $user->must_change_password       = false;
        $user->save();

        ActivityLogger::auth('PASSWORD_CHANGED', 'Mot de passe provisoire remplacé avec succès');

        return redirect()->intended(
            $user->isAdmin() ? route('dashboard') : route('home')
        )->with('success', __('messages.password_changed_success'));
    }
}
