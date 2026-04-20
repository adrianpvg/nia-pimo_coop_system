<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; // Added to make API calls to Google
use App\Models\User;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('finance.loans');
        }
        return view('auth.login');
    }

    public function register(Request $request)
    {
        // 1. Validate the form inputs, including checking if the captcha was checked
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'g-recaptcha-response' => 'required' // Ensure they clicked the box
        ], [
            'name.required' => 'Please enter your full name.',
            'email.required' => 'An email address is required.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 6 characters.',
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.'
        ]);

        // 2. Ping Google's servers to verify the token is legitimate
        if (!$this->verifyRecaptcha($request->input('g-recaptcha-response'), $request->ip())) {
            return back()
                ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed. Please try again.'])
                ->withInput();
        }

        User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
        ]);

        return redirect()->route('login')->with('success', 'Account created successfully! Please sign in.');
    }

    public function login(Request $request)
    {
        // 1. Validate the form inputs
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => 'required' // Ensure they clicked the box
        ], [
            'email.required' => 'Email is required to login.',
            'password.required' => 'Password is required to login.',
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.'
        ]);

        // 2. Ping Google's servers to verify the token is legitimate
        if (!$this->verifyRecaptcha($request->input('g-recaptcha-response'), $request->ip())) {
            return back()
                ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification failed. Please try again.'])
                ->withInput();
        }

        // 3. Attempt login using ONLY the email and password (ignoring the recaptcha token)
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
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

    /**
     * Helper function to verify the reCAPTCHA response with Google
     */
    private function verifyRecaptcha($token, $ip)
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $token,
            'remoteip' => $ip
        ]);

        // Returns true if Google says the token is valid and not a bot
        return $response->json('success');
    }
}