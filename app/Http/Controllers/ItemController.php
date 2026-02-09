<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ItemsExport;
use App\Imports\ItemsImport;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    // =============================
    // GET ALL DATA
    // =============================
    public function index()
    {
        return Item::all();
    }

      public function show($id)
    {
        $item = Item::select('id', 'nama', 'satuan', 'saldo')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    // =============================
    // CREATE / TAMBAH DATA
    // =============================
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required',
            'nama' => 'required',
            'jenis' => 'required|in:barang,alat',
            'satuan' => 'required',
            'saldo' => 'required|integer',
            'minimal_saldo' => 'required|integer',
        ]);

        return Item::create($data);
    }

    // =============================
    // UPDATE / EDIT DATA
    // =============================
    public function update(Request $request)
    {
        $item = Item::where('kode', $request->kode)->firstOrFail();

        $item->update([
            'jenis' => $request->jenis,
            'nama' => $request->nama,
            'satuan' => $request->satuan,
            'saldo' => $request->saldo,
            'minimal_saldo' => $request->minimal_saldo,
        ]);

        return response()->json(['message' => 'Update berhasil']);
    }

    // =============================
    // DELETE DATA (FIXED)
    // =============================
    public function destroy(Request $request)
    {
        if (!$request->kode) {
            return response()->json([
                'message' => 'Kode tidak dikirim'
            ], 400);
        }

        $item = Item::where('kode', $request->kode)->first();

        if (!$item) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }

    // =============================
    // EXPORT EXCEL
    // =============================
    public function export()
    {
        return Excel::download(new ItemsExport, 'items.xlsx');
    }

    // =============================
    // IMPORT EXCEL
    // =============================
    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls'
    ]);

    try {
        Excel::import(new ItemsImport, $request->file('file'));

        return response()->json([
            'message' => 'Import berhasil'
        ]);
    } catch (\Exception $e) {
        Log::error($e->getMessage());

        return response()->json([
            'message' => 'Import gagal',
            'error' => $e->getMessage()
        ], 422);
    }
}
}
