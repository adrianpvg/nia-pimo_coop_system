<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        // Server-side validation still exists, but we removed HTML 'required'
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6'
        ], [
            // Custom error messages (optional)
            'name.required' => 'Please enter your full name.',
            'email.required' => 'An email address is required.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 6 characters.'
        ]);

        User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
        ]);

        // CHANGED: Do not auto-login. Redirect to Login page with message.
        return redirect()->route('login')->with('success', 'Account created successfully! Please sign in.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'Email is required to login.',
            'password.required' => 'Password is required to login.'
        ]);

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
}