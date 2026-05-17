<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CommitteeSignatory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon; 
use Illuminate\Auth\Events\Registered; 

class TeamController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('team.index', compact('users'));
    }

    public function create()
    {
        return view('team.create');
    }

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

        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
            'is_active' => true, 
        ]);

        return redirect()->route('team.index')->with('success', 'New team member account created successfully!');
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('team.show', compact('user'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id, 
            'type' => 'required|in:admin,member',
            'password' => 'nullable|string|min:6|confirmed', 
        ]);

        // Update the basic info
        $user->name = $request->name;
        $user->email = $request->email;
        $user->type = $request->type;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('team.index')->with('success', 'Team member updated successfully!');
    }

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

        $user->is_active = !$user->is_active;
        $user->save();

        $statusMessage = $user->is_active ? 'approved and activated' : 'deactivated';

        return redirect()->back()->with('success', "Account for {$user->name} has been successfully {$statusMessage}.");
    }

    public function destroy($id)
    {
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);

        if (Auth::id() === $user->id) {
            return redirect()->route('team.index')->with('error', 'Action denied: You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('team.index')->with('success', 'Team member permanently deleted.');
    }

    public function logs()
    {
        if (Auth::user()->type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $logs = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name', 'users.email', 'users.type')
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($log) {
                $log->formatted_activity = Carbon::createFromTimestamp($log->last_activity)
                            ->timezone(config('app.timezone'))
                            ->format('M d, Y - h:i A');
                            
                $log->time_ago = Carbon::createFromTimestamp($log->last_activity)
                                    ->timezone(config('app.timezone'))
                                    ->diffForHumans();
                return $log;
            });

        return view('team.logs', compact('logs'));
    }

    public function updateCommittee(Request $request)
{
    $request->validate([
        'credit_committee_name' => 'required|string|max:255',
        'chair_person_name' => 'required|string|max:255',
    ]);

    $committee = CommitteeSignatory::first();
    
    if (!$committee) {
        CommitteeSignatory::create($request->all());
    } else {
        $committee->update($request->all());
    }

    return redirect()->back()->with('success', 'Committee names updated successfully.');
}
}