<?php

namespace Tests\Feature;

use App\Exports\AdminWorkbookExport;
use App\Filament\Admin\Pages\DataTools;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminDataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_export_excel_template_and_backup(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();

        $this->actingAs($owner)->get(route('admin.data.export', 'penghuni'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($owner)->get(route('admin.data.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($owner)->get(route('admin.data.backup'))->assertOk()->assertHeader('content-type', 'application/json');
    }

    public function test_tenant_cannot_export_admin_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = User::factory()->create();

        $this->actingAs($tenant)->get(route('admin.data.export', 'penghuni'))->assertForbidden();
        $this->actingAs($tenant)->get(route('admin.data.template'))->assertForbidden();
    }

    public function test_owner_can_preview_multiple_selected_datasets(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(DataTools::class)
            ->assertSee('Belum ada data yang dipilih')
            ->set('selectedExportTypes', ['penghuni', 'tagihan'])
            ->assertSee('Preview Penghuni')
            ->assertSee('Preview Tagihan')
            ->assertSee('Unduh Excel (2 sheet)');
    }

    public function test_owner_can_download_selected_datasets_as_separate_sheets_in_one_workbook(): void
    {
        $this->travelTo('2026-09-12 12:00:00');
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();
        Excel::fake();

        $this->actingAs($owner)->get(route('admin.data.export', [
            'types' => ['penghuni', 'tagihan'],
        ]))->assertOk();

        Excel::assertDownloaded('ekspor-data-2026-09-12.xlsx', function (AdminWorkbookExport $export): bool {
            $sheets = $export->sheets();

            return count($sheets) === 2
                && $sheets[0]->title() === 'Penghuni'
                && $sheets[1]->title() === 'Tagihan';
        });
    }

    public function test_export_rejects_unknown_dataset_types(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->role('owner')->firstOrFail();

        $this->actingAs($owner)
            ->from(route('filament.admin.pages.data-tools'))
            ->get(route('admin.data.export', ['types' => ['penghuni', 'rahasia']]))
            ->assertRedirect(route('filament.admin.pages.data-tools'))
            ->assertSessionHasErrors('types.1');
    }
}
