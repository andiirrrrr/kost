<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

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
            'maps_iframe' => null,
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

        $data = $this->withAutomaticIcons(array_replace($defaults, $stored->all()));
        $data['maps_embed_url'] = $this->extractMapsEmbedUrl($data['maps_iframe'] ?? null);

        // Prioritaskan nomor WhatsApp profil admin/owner sebagai data utama
        $ownerPhone = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'owner'))
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->value('phone');

        if ($ownerPhone) {
            $data['contact_phone'] = $ownerPhone;
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): void
    {
        $data = $this->withAutomaticIcons($data);

        foreach (array_keys($this->defaults()) as $key) {
            $value = $data[$key] ?? null;

            if (is_array($value)) {
                $value = json_encode(array_values($value), JSON_THROW_ON_ERROR);
            }

            Setting::set($key, $value);
        }

        if (array_key_exists('contact_phone', $data) && filled($data['contact_phone'])) {
            User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'owner'))
                ->update(['phone' => $data['contact_phone']]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withAutomaticIcons(array $data): array
    {
        $iconSets = [
            'hero_benefits' => ['check_circle'],
            'advantages' => ['map', 'manage_accounts', 'spa', 'shield', 'wifi', 'cleaning_services'],
        ];

        foreach ($iconSets as $key => $icons) {
            $items = is_array($data[$key] ?? null) ? array_values($data[$key]) : [];
            $data[$key] = collect($items)
                ->map(function (array $item, int $index) use ($icons): array {
                    $item['icon'] = $icons[$index % count($icons)];

                    return $item;
                })
                ->all();
        }

        $landmarks = is_array($data['landmarks'] ?? null) ? array_values($data['landmarks']) : [];
        $data['landmarks'] = collect($landmarks)
            ->map(function (array $item): array {
                $item['icon'] = $this->resolveLandmarkIcon((string) ($item['label'] ?? ''), $item['icon'] ?? null);

                return $item;
            })
            ->all();

        return $data;
    }

    /**
     * Menentukan icon Material Symbols untuk landmark berdasarkan nama/keterangan atau pilihan eksplisit.
     */
    public function resolveLandmarkIcon(string $label, ?string $explicitIcon = null): string
    {
        $detected = $this->detectLandmarkIconByKeywords($label);

        if (filled($explicitIcon) && $explicitIcon !== 'auto') {
            $legacyMismatches = [
                'location_on' => ['transportasi', 'bus', 'kereta', 'stasiun'],
                'directions_bus' => ['bisnis', 'kantor', 'perkantoran'],
                'business' => ['kuliner', 'makan', 'restoran', 'resto', 'kafe'],
                'restaurant' => ['rumah sakit', 'rs', 'klinik', 'apotek', 'puskesmas'],
            ];

            $isKnownMismatch = false;
            if (isset($legacyMismatches[$explicitIcon])) {
                $lowerLabel = strtolower($label);
                foreach ($legacyMismatches[$explicitIcon] as $kw) {
                    if (str_contains($lowerLabel, $kw)) {
                        $isKnownMismatch = true;
                        break;
                    }
                }
            }

            if (! $isKnownMismatch) {
                return $explicitIcon;
            }
        }

        return $detected ?? 'location_on';
    }

    /**
     * Deteksi icon otomatis dari kata kunci teks label landmark.
     */
    public function detectLandmarkIconByKeywords(string $label): ?string
    {
        $text = strtolower(trim($label));
        if ($text === '') {
            return null;
        }

        // 1. Kereta / MRT / LRT / KRL / Stasiun
        if (preg_match('/\b(stasiun|krl|mrt|lrt|kereta|commuter|subway|train)\b/i', $text)) {
            return 'train';
        }

        // 2. Bus / Halte / Angkot / Terminal / Transportasi Umum
        if (preg_match('/\b(halte|bus|busway|transjakarta|terminal|angkot|shuttle|transportasi|transit)\b/i', $text)) {
            return 'directions_bus';
        }

        // 3. Bandara / Airport
        if (preg_match('/\b(bandara|airport|bandar udara|soekarno-hatta|halim)\b/i', $text)) {
            return 'flight';
        }

        // 4. Jalan Tol / Akses Tol
        if (preg_match('/\b(tol|highway)\b/i', $text) || str_contains($text, 'jalan tol') || str_contains($text, 'pintu tol') || str_contains($text, 'gerbang tol')) {
            return 'add_road';
        }

        // 5. Pelabuhan / Kapal / Dermaga
        if (preg_match('/\b(pelabuhan|dermaga|ferry|kapal|port)\b/i', $text)) {
            return 'directions_boat';
        }

        // 6. Rumah Sakit / Medis / Apotek
        if (preg_match('/\b(rs|rsud|klinik|puskesmas|apotek|hospital|clinic|pharmacy|dokter|medika)\b/i', $text) || str_contains($text, 'rumah sakit')) {
            return 'local_hospital';
        }

        // 7. Kampus / Sekolah / Universitas / Pendidikan
        if (preg_match('/\b(kampus|universitas|univ|institut|poltek|politeknik|sekolah|sma|smk|smp|sd|kuliah|akademik?|campus|school|college|university|fakultas|bimbel|perpus|perpustakaan)\b/i', $text) || str_contains($text, 'perguruan tinggi')) {
            return 'school';
        }

        // 8. Kuliner / Restoran / Kafe / Makanan
        if (preg_match('/\b(kuliner|restoran|resto|kafe|cafe|coffee|kopi|warung|jajanan|dining|food|bakso|mcdonald|kfc)\b/i', $text) || str_contains($text, 'pusat kuliner') || str_contains($text, 'tempat makan')) {
            return 'restaurant';
        }

        // 9. Mall / Belanja / Supermarket / Pasar
        if (preg_match('/\b(mall|mol|plaza|shopping)\b/i', $text) || str_contains($text, 'pusat perbelanjaan') || str_contains($text, 'pusat belanja')) {
            return 'shopping_bag';
        }

        if (preg_match('/\b(pasar|supermarket|minimarket|indomaret|alfamart|swalayan|groceries|toko)\b/i', $text)) {
            return 'storefront';
        }

        // 10. Bisnis / Perkantoran / Gedung Kantor
        if (preg_match('/\b(bisnis|kantor|perkantoran|cbd|menara|tower|business|office)\b/i', $text) || str_contains($text, 'pusat bisnis') || str_contains($text, 'kawasan industri')) {
            return 'business';
        }

        // 11. Olahraga / Gym / Stadion / Fitness
        if (preg_match('/\b(gym|fitness|stadion|sport|futsal|renang|workout|gelora|gor)\b/i', $text) || str_contains($text, 'kolam renang') || str_contains($text, 'lapangan olahraga')) {
            return 'fitness_center';
        }

        // 12. Taman / Ruang Terbuka
        if (preg_match('/\b(taman|park|kebun)\b/i', $text) || str_contains($text, 'hutan kota') || str_contains($text, 'alun-alun')) {
            return 'park';
        }

        // 13. Bank / ATM
        if (preg_match('/\b(atm|bank|bca|mandiri|bri|bni)\b/i', $text)) {
            return 'local_atm';
        }

        // 14. Tempat Ibadah
        if (preg_match('/\b(masjid|musholla|mushola|gereja|katedral|pura|vihara|kelenteng|mosque|church)\b/i', $text)) {
            return 'place_of_worship';
        }

        // 15. Bioskop / Hiburan
        if (preg_match('/\b(bioskop|cinema|xxi|cgv|teater|movie)\b/i', $text)) {
            return 'movie';
        }

        // 16. Wisata / Pantai / Museum
        if (preg_match('/\b(wisata|pantai|museum|monumen|candi|attraction)\b/i', $text)) {
            return 'attractions';
        }

        return null;
    }

    /**
     * Ekstrak dan validasi URL embed Google Maps dari kode <iframe> atau URL langsung.
     */
    public function extractMapsEmbedUrl(?string $iframeOrUrl): ?string
    {
        if (blank($iframeOrUrl)) {
            return null;
        }

        $input = trim($iframeOrUrl);

        if (preg_match('/<iframe\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $input, $matches)) {
            $url = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        } else {
            $url = $input;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parsed['host'] ?? '');
        $isAllowedMapHost = (bool) preg_match('/(^|\.)(google\.[a-z.]+|goo\.gl|openstreetmap\.org)$/i', $host);

        if (! $isAllowedMapHost) {
            return null;
        }

        return $url;
    }
}
