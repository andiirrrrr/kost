<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\HouseRulesService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseRulesController extends Controller
{
    public function __invoke(Request $request, HouseRulesService $service): View
    {
        $tenant = $request->user()?->tenant;
        abort_unless($tenant !== null, 403);

        $contract = $tenant->contracts()
            ->where('status', 'active')
            ->latest('starts_at')
            ->first() ?? $tenant->contracts()->latest('id')->first();

        return view('tenant.rules', [
            'tenant' => $tenant->load(['room']),
            'contract' => $contract,
            'categories' => $service->get(),
            'businessName' => Setting::get('business_name', 'Kost Bahagia'),
        ]);
    }
}
