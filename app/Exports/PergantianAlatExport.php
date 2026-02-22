<?php

namespace App\Exports;

use App\Models\PergantianAlat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PergantianAlatExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return PergantianAlat::with('item')
            ->get()
            ->map(function ($row) {
                return [
                    'item_kode' => $row->item?->kode,
                    'nama_barang' => $row->nama_barang,
                    'satuan' => $row->satuan,
                    'tanggal' => $row->tanggal,
                    'nominal' => $row->nominal,
                    'pic' => $row->pic,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Tanggal',
            'Nominal',
            'PIC',
        ];
    }
}
