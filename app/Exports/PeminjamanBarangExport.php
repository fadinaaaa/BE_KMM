<?php

namespace App\Exports;

use App\Models\PeminjamanBarang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PeminjamanBarangExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return PeminjamanBarang::with('item')
            ->get()
            ->map(function ($row) {
                return [
                    'item_kode' => $row->item?->kode,
                    'nama_barang' => $row->nama_barang,
                    'satuan' => $row->satuan,
                    'tanggal_pinjam' => $row->tanggal_pinjam,
                    'tanggal_kembali' => $row->tanggal_kembali,
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
            'Tanggal Pinjam',
            'Tanggal Kembali',
            'PIC',
        ];
    }
}
