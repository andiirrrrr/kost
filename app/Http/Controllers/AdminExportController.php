<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Exports\AdminDataExport;
use App\Exports\AdminWorkbookExport;
use App\Exports\OnboardingTemplateExport;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Services\AdminExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminExportController extends Controller
{
    public function export(Request $request, AdminExportService $exportService, ?string $type = null): BinaryFileResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        if ($type !== null) {
            abort_unless(array_key_exists($type, AdminExportService::TYPES), 404);

            if (! $request->has('types')) {
                $request->merge(['types' => [$type]]);
            }
        }

        $filters = $request->validate([
            'types' => ['required', 'array', 'min:1', 'max:4'],
            'types.*' => ['required', 'string', 'distinct', Rule::in(array_keys(AdminExportService::TYPES))],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', 'string', 'max:30'],
            'category' => ['nullable', Rule::enum(ExpenseCategory::class)],
        ]);

        $types = $filters['types'];
        unset($filters['types']);
        $sheets = collect($types)
            ->map(function (string $selectedType) use ($exportService, $filters): AdminDataExport {
                $dataset = $exportService->dataset($selectedType, $filters);

                return new AdminDataExport($dataset['headers'], $dataset['rows'], $dataset['label']);
            })
            ->all();
        $export = count($sheets) === 1 ? $sheets[0] : new AdminWorkbookExport($sheets);
        $filename = count($types) === 1
            ? $types[0].'-'.now()->format('Y-m-d').'.xlsx'
            : 'ekspor-data-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            $export,
            $filename,
        );
    }

    public function template(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        return Excel::download(new OnboardingTemplateExport, 'template-impor-kamar-penghuni.xlsx');
    }

    public function backup(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        return response()->streamDownload(function (): void {
            echo json_encode([
                'created_at' => now()->toIso8601String(),
                'categories' => RoomCategory::query()->get(),
                'rooms' => Room::query()->get(),
                'tenants' => Tenant::query()->get(),
                'invoices' => Invoice::withTrashed()->get(),
                'payments' => Payment::query()->get(),
                'expenses' => Expense::withTrashed()->get(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'backup-kost-'.now()->format('Y-m-d-His').'.json', ['Content-Type' => 'application/json']);
    }
}
