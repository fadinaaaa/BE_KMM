<?php

namespace App\Http\Controllers;

use App\Models\Skylift;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SkyliftsExport;
use App\Imports\SkyliftsImport;

class SkyliftController extends Controller
{
    // =============================
    // GET ALL DATA
    // =============================
    public function index()
    {
        return Skylift::all();
    }

    // =============================
    // CREATE DATA
    // =============================
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string',
            'quantity' => 'required|integer'
        ]);

        return Skylift::create($data);
    }

    // =============================
    // DETAIL
    // =============================
    public function show($id)
    {
        return Skylift::findOrFail($id);
    }

    // =============================
    // UPDATE
    // =============================
    public function update(Request $request, $id)
    {
        $skylift = Skylift::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string',
            'quantity' => 'required|integer'
        ]);

        $skylift->update($data);

        return $skylift;
    }

    // =============================
    // DELETE
    // =============================
    public function destroy($id)
    {
        Skylift::destroy($id);

        return response()->json([
            'message' => 'Data Skylift berhasil dihapus'
        ]);
    }

    // =============================
    // EXPORT
    // =============================
    public function export()
    {
        return Excel::download(new SkyliftsExport, 'data-skylift.xlsx');
    }

    // =============================
    // IMPORT
    // =============================
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new SkyliftsImport, $request->file('file'));

        return response()->json([
            'message' => 'Import Skylift berhasil'
        ]);
    }
}
