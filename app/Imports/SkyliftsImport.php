<?php

namespace App\Imports;

use App\Models\Skylift;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SkyliftsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Skylift([
            'nama' => $row['nama'],
            'quantity' => $row['quantity'],
        ]);
    }
}
