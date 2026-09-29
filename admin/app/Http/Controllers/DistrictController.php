<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(): View
    {
        $districts = District::query()
            ->where('admin_id', Auth::id())
            ->withCount(['customers', 'managers'])
            ->orderBy('name')
            ->get();

        return view('districts.index', compact('districts'));
    }

    public function create(): View
    {
        return view('districts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        District::create([
            ...$data,
            'admin_id' => Auth::id(),
        ]);

        return redirect()->route('districts.index')->with('status', 'უბანი დაემატა');
    }

    public function show(District $district): View
    {
        $this->authorizeAccess($district);
        $district->load(['customers', 'managers']);

        return view('districts.show', compact('district'));
    }

    public function edit(District $district): View
    {
        $this->authorizeAccess($district);

        return view('districts.edit', compact('district'));
    }

    public function update(Request $request, District $district): RedirectResponse
    {
        $this->authorizeAccess($district);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $district->update($data);

        return redirect()->route('districts.index')->with('status', 'უბანი განახლდა');
    }

    public function destroy(District $district): RedirectResponse
    {
        $this->authorizeAccess($district);
        $district->delete();

        return redirect()->route('districts.index')->with('status', 'უბანი წაიშალა');
    }

    private function authorizeAccess(District $district): void
    {
        abort_unless($district->admin_id === Auth::id(), 403);
    }
}
