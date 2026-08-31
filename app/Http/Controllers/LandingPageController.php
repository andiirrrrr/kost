<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Services\LandingPageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(LandingPageService $landingPage): View
    {
        $rooms = Schema::hasTable('rooms')
            ? Room::query()
                ->whereIn('status', [RoomStatus::AVAILABLE, RoomStatus::OCCUPIED])
                ->orderBy('status')
                ->orderBy('room_number')
                ->limit(6)
                ->get()
            : collect();
        $settings = Schema::hasTable('settings') ? $landingPage->get() : $landingPage->defaults();

        foreach (['hero_image', 'about_image'] as $imageKey) {
            $settings[$imageKey.'_url'] = filled($settings[$imageKey])
                ? Storage::disk('public')->url($settings[$imageKey])
                : null;
        }

        return view('welcome', compact('rooms', 'settings'));
    }
}
