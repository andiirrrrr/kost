<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $maintenanceRequests = $request->user()->tenant->maintenanceRequests()
            ->latest()
            ->paginate(10);

        return view('tenant.maintenance.index', compact('maintenanceRequests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        abort_if($tenant === null, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:3000'],
            'priority' => ['required', 'in:low,normal,urgent'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $validated['photo'] = $request->file('photo')?->store('maintenance', 'local');
        $validated['room_id'] = $tenant->room_id;
        $tenant->maintenanceRequests()->create($validated);

        return back()->with('success', 'Keluhan berhasil dikirim dan akan segera ditindaklanjuti.');
    }
}
