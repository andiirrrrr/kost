<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminWorkbookExport implements Export, WithMultipleSheets
{
    /** @param list<AdminDataExport> $sheets */
    public function __construct(private readonly array $sheets) {}

    /** @return list<AdminDataExport> */
    public function sheets(): array
    {
        return $this->sheets;
    }
}
