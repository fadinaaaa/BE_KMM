<?php

namespace App\Imports;

use App\Models\Item;
use Illuminate\Support\Str; // ✔️ IMPORT CLASS
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Item([
            'kode'          => trim($row['kode']),
            'nama'          => trim($row['nama']),
            'jenis'         => Str::lower(trim($row['jenis'])), // ✔️ BENAR
            'satuan'        => trim($row['satuan']),
            'saldo'         => (int) ($row['saldo'] ?? 0),
            'minimal_saldo' => (int) ($row['minimal_saldo'] ?? 0),
        ]);
    }
}
