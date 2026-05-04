<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Events\Registered; // <-- ADD THIS
use App\Models\User;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('finance.loans');
        }

        return response()->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    // NEW: Render the specific Register Page
    public function registerView()
    {
        if (Auth::check()) {
            return redirect()->route('finance.loans');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'g-recaptcha-response' => 'required'
        ]);

        if (!$this->verifyRecaptcha($request->input('g-recaptcha-response'), $request->ip())) {
            return back()->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed.'])->withInput();
        }

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'is_active' => false, 
        ]);

        // TRIGGER THE VERIFICATION EMAIL
        event(new Registered($user));

        return redirect()->route('login')->with('success', 'Account created! Please check your email to verify your address. Note: An administrator must approve your account before you can log in.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        if (!$this->verifyRecaptcha($request->input('g-recaptcha-response'), $request->ip())) {
            return back()->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed.'])->withInput();
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // CHECK 1: Is Email Verified?
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                return back()->withErrors(['email' => 'You must verify your email address before logging in. Please check your inbox.'])->onlyInput('email');
            }

            // CHECK 2: Is Admin Approved? (Admins bypass this check)
            if (!$user->is_active && $user->type !== 'admin') {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is verified but is currently pending Administrator approval.'])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->route('finance.loans');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    private function verifyRecaptcha($token, $ip)
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $token,
            'remoteip' => $ip
        ]);
        return $response->json('success');
    }
}