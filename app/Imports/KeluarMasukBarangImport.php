<?php

namespace App\Imports;

use App\Models\keluarmasukbarang;
use App\Models\Item;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KeluarMasukBarangImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Cari atau buat item
        $item = Item::firstOrCreate(
            ['nama' => $row['item']],
            ['saldo' => 0]
        );

        $qty = (int) $row['nominal']; // jumlah barang

        // UPDATE SALDO
        if (strtolower($row['keluarmasuk']) == 'masuk') {
            $item->saldo += $qty;
        } elseif (strtolower($row['keluamasuk']) == 'keluar') {
            $item->saldo -= $qty;
        }

        $item->save();

        return new keluarmasukbarang([
            'nama'        => $row['nama'],
            'satuan'      => $row['satuan'],
            'keluarmasuk' => $row['keluarmasuk'],
            'tanggal'     => $row['tanggal'],
            'nominal'     => $qty,
            'PIC'         => $row['pic'],
            'keterangan'  => $row['keterangan'],
            'item_id'     => $item->id,
        ]);
    }
}
