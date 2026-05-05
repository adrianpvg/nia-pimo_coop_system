<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon; 
use Illuminate\Auth\Events\Registered; // <--- 1. ADD THIS IMPORT

class TeamController extends Controller
{
    /**
     * Display the directory of all team members.
     */
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('team.index', compact('users'));
    }

    /**
     * Show the dedicated page for creating a new account.
     */
    public function create()
    {
        return view('team.create');
    }

    /**
     * Store a newly created team member.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'suffix' => 'nullable|string|max:10',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'type' => 'required|in:admin,member',
        ]);

        $nameParts = [
            $request->first_name,
            $request->middle_initial,
            $request->last_name,
            $request->suffix
        ];
        
        $fullName = Str::title(implode(' ', array_filter($nameParts)));

        // 2. SAVE THE USER TO A VARIABLE SO WE CAN PASS IT TO THE EVENT
        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
            'is_active' => true, // Still true: Admin trusts them, so no manual approval needed later
            // REMOVED: 'email_verified_at' => now(), so they are forced to verify
        ]);

        // 3. TRIGGER THE VERIFICATION EMAIL
        event(new Registered($user));

        return redirect()->route('team.index')->with('success', 'New team member account created! An email verification link has been sent to their address.');
    }

    /**
     * Display the specific user's management profile.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('team.show', compact('user'));
    }

    /**
     * Update the specified team member in storage.
     */
    public function update(Request $request, $id)
    {
        // Extra security: Ensure only admins can trigger this via direct URL
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        // Validate the incoming data
        $request->validate([
            'name' => 'required|string|max:255',
            // Ignore the current user's email during the unique check
            'email' => 'required|email|unique:users,email,' . $user->id, 
            'type' => 'required|in:admin,member',
            'password' => 'nullable|string|min:6|confirmed', // Nullable because it's optional
        ]);

        // Update the basic info
        $user->name = $request->name;
        $user->email = $request->email;
        $user->type = $request->type;

        // Check if the user filled out the password field; only update if they did
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('team.index')->with('success', 'Team member updated successfully!');
    }

    // ==========================================
    // TOGGLE ACCOUNT ACTIVATION
    // ==========================================
    public function toggleActive($id)
    {
        // Extra security
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        // Prevent the admin from accidentally locking themselves out
        if (Auth::id() === $user->id) {
            return redirect()->back()->with('error', 'Action denied: You cannot deactivate your own account.');
        }

        // Flip the boolean status
        $user->is_active = !$user->is_active;
        $user->save();

        $statusMessage = $user->is_active ? 'approved and activated' : 'deactivated';

        return redirect()->back()->with('success', "Account for {$user->name} has been successfully {$statusMessage}.");
    }

    /**
     * Remove the specified team member from storage.
     */
    public function destroy($id)
    {
        // Extra security: Ensure only admins can trigger this via direct URL
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        // Prevent the admin from accidentally deleting themselves
        if (Auth::id() === $user->id) {
            return redirect()->route('team.index')->with('error', 'Action denied: You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('team.index')->with('success', 'Team member permanently deleted.');
    }

    /**
     * Display system logs (Active sessions) - ADMIN ONLY
     */
    public function logs()
    {
        // Extra security layer: Kick them out if they type the URL manually and aren't an admin
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Query the sessions table and join the users table so we know whose session it is
        $logs = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name', 'users.email', 'users.type')
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($log) {
                // Convert UNIX timestamp to readable date/time
                $log->formatted_activity = Carbon::createFromTimestamp($log->last_activity)->format('M d, Y - h:i A');
                $log->time_ago = Carbon::createFromTimestamp($log->last_activity)->diffForHumans();
                return $log;
            });

        return view('team.logs', compact('logs'));
    }
}