<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_email' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'user_email' => $request->user_email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $domain = Auth::user()->domain;
            Session::put('domain_uuid', $domain->domain_uuid);
            Session::put('domain_name', $domain->domain_name);
            Session::put('domain_description', !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name);
            return redirect()->intended('/dashboard');
        }

        return back()
            ->withInput($request->only('user_email', 'remember'))
            ->with('error', __('Wrong Username or Password.'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
