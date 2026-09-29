<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function index(): View
    {
        $managers = User::query()
            ->where('role', User::ROLE_MANAGER)
            ->where('created_by_id', Auth::id())
            ->with('district')
            ->orderBy('name')
            ->get();

        return view('managers.index', compact('managers'));
    }

    public function create(): View
    {
        $districts = District::where('admin_id', Auth::id())->orderBy('name')->get();

        return view('managers.create', compact('districts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'district_id' => ['nullable', Rule::exists('districts', 'id')->where('admin_id', Auth::id())],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_MANAGER,
            'district_id' => $data['district_id'] ?? null,
            'created_by_id' => Auth::id(),
        ]);

        return redirect()->route('managers.index')->with('status', 'მენეჯერი დაემატა');
    }

    public function edit(User $manager): View
    {
        $this->authorizeAccess($manager);
        $districts = District::where('admin_id', Auth::id())->orderBy('name')->get();

        return view('managers.edit', compact('manager', 'districts'));
    }

    public function update(Request $request, User $manager): RedirectResponse
    {
        $this->authorizeAccess($manager);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($manager->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'district_id' => ['nullable', Rule::exists('districts', 'id')->where('admin_id', Auth::id())],
        ]);

        $manager->name = $data['name'];
        $manager->email = $data['email'];
        $manager->district_id = $data['district_id'] ?? null;
        if (! empty($data['password'])) {
            $manager->password = Hash::make($data['password']);
        }
        $manager->save();

        return redirect()->route('managers.index')->with('status', 'მენეჯერი განახლდა');
    }

    public function destroy(User $manager): RedirectResponse
    {
        $this->authorizeAccess($manager);
        $manager->delete();

        return redirect()->route('managers.index')->with('status', 'მენეჯერი წაიშალა');
    }

    private function authorizeAccess(User $manager): void
    {
        abort_unless($manager->role === User::ROLE_MANAGER && $manager->created_by_id === Auth::id(), 403);
    }
}
