<?php
namespace Buni\Cms\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController
{
    public function index(Request $request)
    {
        $users = User::with('roles')->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::pluck('name');

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'auth' => Auth::user(),
        ]);
    }

    public function create()
    {
        $roles = Role::pluck('name');
        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
            'auth' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return redirect()->route('cms.admin.users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        $roles = Role::pluck('name');
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => $roles,
            'auth' => Auth::user(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|string',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        } else {
            $user->syncRoles([]);
        }

        return redirect()->route('cms.admin.users.edit', $user->id)->with('success', 'User updated');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('cms.admin.users.index')->with('success', 'User deleted');
    }

    public function sendReset(User $user)
    {
        Password::sendResetLink(['email' => $user->email]);
        return redirect()->back()->with('success', 'Password reset email sent');
    }
}
