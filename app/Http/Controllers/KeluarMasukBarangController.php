<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\KeluarMasukBarang;
use Illuminate\Support\Facades\DB;
use App\Exports\KeluarMasukBarangExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KeluarMasukBarangImport;
use App\Exports\KeluarMasukBarangTemplateExport;

class KeluarMasukBarangController extends Controller
{
    /**
     * Ambil data item untuk dropdown (id, nama, satuan)
     */
    public function getItemsForDropdown()
    {
        $items = Item::select('id', 'nama', 'satuan')->orderBy('nama')->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * List data keluar masuk + relasi item
     */
    public function index()
    {
        $data = KeluarMasukBarang::with('item')->orderBy('tanggal', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Simpan data keluar / masuk barang
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_id'     => 'required|exists:items,id',
            'keluarmasuk' => 'required|in:masuk,keluar',
            'tanggal'     => 'required|date',
            'nominal'     => 'required|integer|min:1',
            'PIC'         => 'required|string',
            'keterangan'  => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $item = Item::findOrFail($request->item_id);

            // Update saldo
            if ($request->keluarmasuk === 'masuk') {
                $item->saldo += $request->nominal;
            } else {
                if ($item->saldo < $request->nominal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo tidak mencukupi untuk barang keluar'
                    ], 422);
                }
                $item->saldo -= $request->nominal;
            }

            $item->save();

            // Simpan log keluar masuk
            $log = KeluarMasukBarang::create([
                'item_id'      => $request->item_id,
                'nama'         => $item->nama,
                'satuan'       => $item->satuan,
                'keluarmasuk'  => $request->keluarmasuk,
                'tanggal'      => $request->tanggal,
                'nominal'      => $request->nominal,
                'PIC'          => $request->PIC,
                'keterangan'   => $request->keterangan,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data keluar/masuk berhasil disimpan',
                'data' => $log
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_id'     => 'required|exists:items,id',
            'keluarmasuk' => 'required|in:masuk,keluar',
            'tanggal'     => 'required|date',
            'nominal'     => 'required|integer|min:1',
            'PIC'         => 'nullable|string',
            'keterangan'  => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Data lama
            $logLama = KeluarMasukBarang::findOrFail($id);
            $itemLama = Item::findOrFail($logLama->item_id);

            // 1️⃣ Rollback saldo dari data lama
            if ($logLama->keluar_masuk === 'masuk') {
                $itemLama->saldo -= $logLama->nominal;
            } else {
                $itemLama->saldo += $logLama->nominal;
            }
            $itemLama->save();

            // 2️⃣ Apply saldo ke item baru (bisa item yang sama / beda)
            $itemBaru = Item::findOrFail($request->item_id);

            if ($request->keluarmasuk === 'masuk') {
                $itemBaru->saldo += $request->nominal;
            } else {
                if ($itemBaru->saldo < $request->nominal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo tidak mencukupi untuk barang keluar'
                    ], 422);
                }
                $itemBaru->saldo -= $request->nominal;
            }
            $itemBaru->save();

            // 3️⃣ Update data log
            $logLama->update([
                'item_id'      => $request->item_id,
                'nama'         => $itemBaru->nama,
                'satuan'       => $itemBaru->satuan,
                'keluarmasuk'  => $request->keluarmasuk,
                'tanggal'      => $request->tanggal,
                'nominal'      => $request->nominal,
                'PIC'          => $request->PIC,
                'keterangan'   => $request->keterangan,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data keluar/masuk berhasil diupdate',
                'data' => $logLama
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail satu data
     */
    public function show($id)
    {
        $data = KeluarMasukBarang::with('item')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Hapus data (rollback saldo)
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $log = KeluarMasukBarang::findOrFail($id);
            $item = Item::findOrFail($log->item_id);

            // Rollback saldo
            if ($log->keluar_masuk === 'masuk') {
                $item->saldo -= $log->nominal;
            } else {
                $item->saldo += $log->nominal;
            }

            $item->save();
            $log->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function export()
    {
        return Excel::download(new KeluarMasukBarangExport, 'keluar_masuk_barang.xlsx');
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new KeluarMasukBarangImport, $request->file('file'));

        return response()->json([
            'message' => 'Import berhasil'
        ]);
    }
    public function downloadTemplate()
    {
        return Excel::download(
            new KeluarMasukBarangTemplateExport,
            'template_import_keluar_masuk_barang.xlsx'
        );
    }
}
