<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Item::select(
            'kode',
            'nama',
            'jenis',
            'satuan',
            'saldo',
            'minimal_saldo'
        )->get();
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Jenis',
            'Satuan',
            'Saldo',
            'Minimal Saldo'
        ];
    }
}
