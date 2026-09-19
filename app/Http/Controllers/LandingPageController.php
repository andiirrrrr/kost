<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\RoomCategory;
use App\Services\LandingPageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(LandingPageService $landingPage): View
    {
        $listedRoomStatuses = [RoomStatus::AVAILABLE, RoomStatus::OCCUPIED];
        $roomCategories = Schema::hasTable('room_categories')
            ? RoomCategory::query()
                ->where('is_active', true)
                ->whereHas('rooms', fn ($query) => $query->whereIn('status', $listedRoomStatuses))
                ->with(['rooms' => fn ($query) => $query
                    ->select(['id', 'room_category_id', 'monthly_price', 'status'])
                    ->whereIn('status', $listedRoomStatuses)
                    ->orderBy('room_number')])
                ->withCount([
                    'rooms as available_rooms_count' => fn ($query) => $query->where('status', RoomStatus::AVAILABLE),
                    'rooms as listed_rooms_count' => fn ($query) => $query->whereIn('status', $listedRoomStatuses),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(6)
                ->get()
            : collect();
        $settings = Schema::hasTable('settings') ? $landingPage->get() : $landingPage->defaults();

        foreach (['hero_image', 'about_image'] as $imageKey) {
            $settings[$imageKey.'_url'] = filled($settings[$imageKey])
                ? Storage::disk('public')->url($settings[$imageKey])
                : null;
        }

        $settings['maps_embed_url'] = $settings['maps_embed_url'] ?? null;

        return view('welcome', compact('roomCategories', 'settings'));
    }
}
