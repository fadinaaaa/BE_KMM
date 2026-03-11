<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KeluarMasukBarangTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'nama' => '',
                'kode' => '',
                'satuan' => '',
                'keluarmasuk' => '',
                'tanggal' => '',
                'nominal' => '',
                'PIC' => '',
                'keterangan' => '',
                'item' => '',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'nama',
            'kode',
            'satuan',
            'keluarmasuk',
            'tanggal',
            'nominal',
            'PIC',
            'keterangan',
            'item'
        ];
    }
}
