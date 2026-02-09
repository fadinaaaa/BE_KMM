<?php

namespace App\Exports;

use App\Models\Skylift;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SkyliftsExport implements FromCollection, WithHeadings
{
    // DATA YANG DIEXPORT
    public function collection()
    {
        return Skylift::select('nama', 'quantity')->get();
    }

    // HEADER EXCEL
    public function headings(): array
    {
        return [
            'Nama',
            'Quantity'
        ];
    }
}
