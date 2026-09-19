<?php

namespace App\Services;

use App\Models\Setting;

class HouseRulesService
{
    /**
     * @return list<array{category: string, icon: string, rules: list<string>}>
     */
    public function defaults(): array
    {
        return [
            [
                'category' => 'Ketertiban & Keamanan',
                'icon' => 'security',
                'rules' => [
                    'Tamu berkunjung maksimal hingga pukul 22.00 untuk menjaga kenyamanan dan ketenangan seluruh penghuni.',
                    'Tamu lawan jenis dilarang menginap atau berada di dalam kamar tidur tanpa persetujuan resmi dari pengelola kost.',
                    'Wajib mengunci pintu kamar masing-masing dan menutup kembali pintu pagar/gerbang utama setelah keluar-masuk.',
                    'Penghuni bertanggung jawab penuh atas keamanan barang-barang berharga pribadi masing-masing.',
                ],
            ],
            [
                'category' => 'Kebersihan & Area Bersama',
                'icon' => 'cleaning_services',
                'rules' => [
                    'Menjaga kebersihan kamar masing-masing serta area bersama (dapur umum, tempat jemuran, lorong, dan area parkir).',
                    'Wajib membuang sampah pada tempat sampah yang telah disediakan dalam kondisi terbungkus kantong plastik rapi.',
                    'Peralatan dapur bersama wajib segera dicuci dan dikembalikan ke tempat semula setelah selesai digunakan.',
                    'Dilarang memaku dinding, menempel stiker permanen, atau mengubah struktur kamar tanpa izin pengelola.',
                ],
            ],
            [
                'category' => 'Larangan Khusus',
                'icon' => 'block',
                'rules' => [
                    'Dilarang keras merokok atau vaping di dalam kamar ber-AC dan area bebas asap rokok kost.',
                    'Dilarang membawa, menyimpan, memperjualbelikan, atau mengonsumsi minuman keras dan obat-obatan terlarang (narkoba).',
                    'Dilarang membuat kegaduhan, memutar musik dengan volume keras, atau aktivitas yang mengganggu pada jam istirahat (22.00 - 06.00).',
                    'Dilarang membawa atau memelihara hewan peliharaan di area kost tanpa persetujuan tertulis dari pengelola.',
                ],
            ],
            [
                'category' => 'Pembayaran & Ketentuan Sewa',
                'icon' => 'payments',
                'rules' => [
                    'Pembayaran sewa kamar wajib diselesaikan tepat waktu paling lambat pada tanggal jatuh tempo setiap bulannya.',
                    'Penghuni yang berencana mengakhiri masa sewa (check-out) wajib memberi tahu pengelola minimal 30 hari sebelumnya.',
                    'Uang jaminan (deposit) akan diperhitungkan dan dikembalikan setelah serah terima kunci serta pengecekan kondisi kamar.',
                    'Kerusakan fasilitas atau kelengkapan kamar yang terjadi akibat kelalaian penghuni akan diperhitungkan dari deposit sewa.',
                ],
            ],
        ];
    }

    /**
     * @return list<array{category: string, icon: string, rules: list<string>}>
     */
    public function get(): array
    {
        $raw = Setting::get('house_rules');
        if (blank($raw)) {
            return $this->defaults();
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded) || empty($decoded)) {
            return $this->defaults();
        }

        return $decoded;
    }

    /**
     * @param  list<array{category: string, icon?: string, rules: list<string>}>  $rules
     */
    public function save(array $rules): void
    {
        Setting::set('house_rules', json_encode(array_values($rules), JSON_UNESCAPED_UNICODE));
    }
}
