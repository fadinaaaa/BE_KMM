<?php

namespace App\Exports;

use App\Models\keluarmasukbarang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KeluarMasukBarangExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return keluarmasukbarang::with('item')->get()->map(function ($row) {
            return [
                'ID'          => $row->id,
                'Nama'        => $row->nama,
                'Satuan'      => $row->satuan,
                'KeluarMasuk' => $row->keluarmasuk,
                'Tanggal'     => $row->tanggal,
                'Nominal'     => $row->nominal,
                'PIC'         => $row->PIC,
                'Keterangan'  => $row->keterangan,
                'Item'        => $row->item->nama ?? '-', // relasi item
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Satuan',
            'Keluar/Masuk',
            'Tanggal',
            'Nominal',
            'PIC',
            'Keterangan',
            'Item',
        ];
    }
}
