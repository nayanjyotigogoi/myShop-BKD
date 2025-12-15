<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index()
    {
        $users = User::orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store new user.
     */
   /**
 * Store new user.
 */
public function store(Request $request)
{
    $data = $request->validate([
        'name'           => 'required|string|max:255',
        'email'          => 'required|email|unique:users,email',
        'password'       => 'required|string|min:6|confirmed',
        'is_super_admin' => 'boolean',
    ]);

    $data['password'] = Hash::make($data['password']);
    $data['is_super_admin'] = $request->boolean('is_super_admin');

    // Create the user
    $user = User::create($data);

    // Create a one-time personal access token for this user (Sanctum)
    // This prints the plain-text token so the admin can copy it immediately.
    // $plainToken = $user->createToken('frontend-token')->plainTextToken;

    // Redirect back to users list with a flash message and the plain token
    // WARNING: the plain token is shown only once here. Store it securely if needed.
    return redirect()
        ->route('admin.users.index')
        ->with('status', 'User created successfully.')
        ->with('plain_token', $plainToken)
        ->with('plain_token_user_email', $user->email);
}


    /**
     * Show edit form.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'password'       => 'nullable|string|min:6|confirmed',
            'is_super_admin' => 'boolean',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->is_super_admin = $request->boolean('is_super_admin');

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        // optional safety: don't let super admin delete themselves
        if (auth()->id() === $user->id) {
            return back()->with('status', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }
}
