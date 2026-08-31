<?php

namespace App\Services;

use App\Models\Setting;

class LandingPageService
{
    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'business_name' => 'Heritage Residential',
            'hero_eyebrow' => 'Premium Boarding Living',
            'hero_title' => 'Hunian Nyaman untuk',
            'hero_emphasis' => 'Hidup yang Lebih Tenang',
            'tagline' => 'Temukan hunian yang nyaman dengan fasilitas lengkap, lokasi strategis, dan pengelolaan yang lebih praktis untuk setiap penghuni.',
            'hero_image' => null,
            'about_image' => null,
            'about_title' => 'Lebih dari Sekadar Tempat Tinggal',
            'about_description' => 'Hunian yang menggabungkan kenyamanan, keamanan, dan pengelolaan profesional untuk pengalaman tinggal yang lebih tenang.',
            'contact_phone' => '081234567890',
            'contact_email' => 'info@heritage.id',
            'address' => 'Jl. Senopati No. 12, Kebayoran Baru, Jakarta Selatan',
            'map_url' => 'https://maps.google.com',
            'operating_hours' => 'Setiap Hari: 08:00 - 20:00',
            'hero_benefits' => [
                ['icon' => 'check_circle', 'label' => 'Lingkungan nyaman'],
                ['icon' => 'check_circle', 'label' => 'Pengelolaan profesional'],
                ['icon' => 'check_circle', 'label' => 'Pembayaran lebih praktis'],
            ],
            'advantages' => [
                ['icon' => 'map', 'title' => 'Lokasi Strategis', 'description' => 'Akses mudah ke kampus, kantor, dan fasilitas umum.'],
                ['icon' => 'manage_accounts', 'title' => 'Pengelolaan Profesional', 'description' => 'Informasi, tagihan, dan komunikasi dikelola melalui sistem digital.'],
                ['icon' => 'spa', 'title' => 'Lingkungan Nyaman', 'description' => 'Area hunian yang tenang, bersih, dan nyaman untuk beristirahat.'],
            ],
            'about_points' => [
                ['title' => 'Kenyamanan Premium', 'description' => 'Setiap ruang dirancang dengan mempertimbangkan estetika dan fungsionalitas.'],
                ['title' => 'Keamanan Terjamin', 'description' => 'Lingkungan terpantau untuk memberikan ketenangan kepada setiap penghuni.'],
                ['title' => 'Manajemen Profesional', 'description' => 'Tim pengelola siap merespons kebutuhan penghuni dengan cepat.'],
            ],
            'landmarks' => [
                ['icon' => 'directions_bus', 'label' => '5 menit ke transportasi umum'],
                ['icon' => 'business', 'label' => '10 menit ke pusat bisnis'],
                ['icon' => 'restaurant', 'label' => '8 menit ke pusat kuliner'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        $defaults = $this->defaults();
        $stored = Setting::query()->whereIn('key', array_keys($defaults))->pluck('value', 'key');

        foreach (['hero_benefits', 'advantages', 'about_points', 'landmarks'] as $jsonKey) {
            if ($stored->has($jsonKey)) {
                $decoded = json_decode((string) $stored[$jsonKey], true);
                $stored[$jsonKey] = is_array($decoded) ? $decoded : $defaults[$jsonKey];
            }
        }

        return array_replace($defaults, $stored->all());
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): void
    {
        foreach (array_keys($this->defaults()) as $key) {
            $value = $data[$key] ?? null;

            if (is_array($value)) {
                $value = json_encode(array_values($value), JSON_THROW_ON_ERROR);
            }

            Setting::set($key, $value);
        }
    }
}
