<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <--- THIS IS THE EDIT: Needed for querying the sessions table
use Carbon\Carbon; // <--- THIS IS THE EDIT: Needed to format timestamps

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
        
        $fullName = implode(' ', array_filter($nameParts));

        User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
        ]);

        return redirect()->route('team.index')->with('success', 'New team member account created successfully!');
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
     * Display system logs (Active sessions) - ADMIN ONLY
     */
    // <--- THIS IS THE EDIT: Added the Logs method
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