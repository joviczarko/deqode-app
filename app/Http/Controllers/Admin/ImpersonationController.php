<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if ($admin === null || ! $admin->is_super_admin) {
            abort(403);
        }

        if ($user->tenant_id === null || $user->is_super_admin) {
            abort(422, 'Can only impersonate tenant users.');
        }

        session([
            'impersonator_id' => $admin->id,
        ]);

        Auth::login($user);

        return redirect('/app');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if ($impersonatorId === null) {
            return redirect('/admin');
        }

        $admin = User::query()->find($impersonatorId);

        session()->forget('impersonator_id');

        if ($admin !== null) {
            Auth::login($admin);
        }

        return redirect('/admin');
    }
}
