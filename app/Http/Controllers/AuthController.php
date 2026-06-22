<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Minimal session-based auth (signup / login / logout). Standard Laravel: the
 * session lives on the per-instance SQLite (SESSION_DRIVER=database), proving
 * session persistence. Signup sends a welcome email over the default SMTP
 * mailer (Velq mailpit).
 */
class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // MAIL: standard Mailable over the default SMTP mailer (Velq mailpit).
        Mail::to($user->email)->send(new WelcomeMail($user));

        return redirect()->route('guestbook')->with('status', 'Welcome, '.$user->name.'!');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('guestbook')->with('status', 'Signed in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guestbook')->with('status', 'Signed out.');
    }
}
