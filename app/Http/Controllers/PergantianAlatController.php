<?php

namespace App\Http\Controllers;

use App\Models\PergantianAlat;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PergantianAlatController extends Controller
{
    public function index()
    {
        $data = PergantianAlat::with('item')
            ->orderBy('tanggal', 'desc')
            ->get()
            ->map(function ($r) {
                $r->item_kode = $r->item?->kode;
                $r->foto_lama_url = $r->foto_lama ? asset('storage/' . $r->foto_lama) : null;

                // ✅ url tanda tangan (png)
                $r->tanda_tangan_url = $r->tanda_tangan ? asset('storage/' . $r->tanda_tangan) : null;

                return $r;
            });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id)
    {
        $data = PergantianAlat::with('item')->findOrFail($id);
        $data->item_kode = $data->item?->kode;
        $data->foto_lama_url = $data->foto_lama ? asset('storage/' . $data->foto_lama) : null;

        // ✅ url tanda tangan (png)
        $data->tanda_tangan_url = $data->tanda_tangan ? asset('storage/' . $data->tanda_tangan) : null;

        return response()->json(['success' => true, 'data' => $data]);
    }

    // =========================
    // helper: simpan base64 png
    // =========================
    private function storeSignatureBase64(?string $dataUrl): ?string
    {
        if (!$dataUrl) return null;

        // data:image/png;base64,xxxx
        $dataUrl = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
        $binary = base64_decode($dataUrl);

        if ($binary === false) return null;

        $fileName = 'tanda-tangan/' . uniqid('ttd_') . '.png';
        Storage::disk('public')->put($fileName, $binary);

        return $fileName; // path di public disk
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id'               => 'required|exists:items,id',
            'tanggal'               => 'required|date',
            'nominal'               => 'required|integer|min:1',
            'pic'                   => 'required|string',
            'tanda_tangan_base64'   => 'required|string', // ✅ dari canvas
            'foto_lama'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();
        $fotoPath = null;
        $ttdPath = null;

        try {
            $item = Item::findOrFail($request->item_id);

            // foto lama
            if ($request->hasFile('foto_lama')) {
                $fotoPath = $request->file('foto_lama')->store('pergantian-alat', 'public');
            }

            // tanda tangan (base64 → file)
            $ttdPath = $this->storeSignatureBase64($request->tanda_tangan_base64);
            if (!$ttdPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanda tangan tidak valid'
                ], 422);
            }

            $row = PergantianAlat::create([
                'item_id'      => $item->id,
                'nama_barang'  => $item->nama,
                'satuan'       => $item->satuan,
                'tanggal'      => $request->tanggal,
                'nominal'      => $request->nominal,
                'pic'          => $request->pic,
                'tanda_tangan' => $ttdPath,     // ✅ simpan PATH PNG
                'foto_lama'    => $fotoPath,
            ]);

            DB::commit();

            $row->item_kode = $row->item?->kode;
            $row->foto_lama_url = $row->foto_lama ? asset('storage/' . $row->foto_lama) : null;
            $row->tanda_tangan_url = $row->tanda_tangan ? asset('storage/' . $row->tanda_tangan) : null;

            return response()->json([
                'success' => true,
                'message' => 'Data pergantian alat berhasil disimpan',
                'data' => $row
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($fotoPath) Storage::disk('public')->delete($fotoPath);
            if ($ttdPath) Storage::disk('public')->delete($ttdPath);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_id'               => 'required|exists:items,id',
            'tanggal'               => 'required|date',
            'nominal'               => 'required|integer|min:1',
            'pic'                   => 'required|string',
            'tanda_tangan_base64'   => 'required|string', // ✅ wajib tanda tangan ulang
            'foto_lama'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();

        $oldFoto = null;
        $newFoto = null;

        $oldTtd = null;
        $newTtd = null;

        try {
            $row  = PergantianAlat::findOrFail($id);
            $item = Item::findOrFail($request->item_id);

            // ====== FOTO ======
            $oldFoto = $row->foto_lama;
            $newFoto = $oldFoto;

            if ($request->hasFile('foto_lama')) {
                $newFoto = $request->file('foto_lama')->store('pergantian-alat', 'public');
            }

            // ====== TTD ======
            $oldTtd = $row->tanda_tangan;
            $newTtd = $this->storeSignatureBase64($request->tanda_tangan_base64);
            if (!$newTtd) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanda tangan tidak valid'
                ], 422);
            }

            $row->update([
                'item_id'      => $item->id,
                'nama_barang'  => $item->nama,
                'satuan'       => $item->satuan,
                'tanggal'      => $request->tanggal,
                'nominal'      => $request->nominal,
                'pic'          => $request->pic,
                'tanda_tangan' => $newTtd, // ✅ pakai ttd baru
                'foto_lama'    => $newFoto,
            ]);

            // hapus file lama setelah update berhasil
            if ($request->hasFile('foto_lama') && $oldFoto) {
                Storage::disk('public')->delete($oldFoto);
            }
            if ($oldTtd) {
                Storage::disk('public')->delete($oldTtd);
            }

            DB::commit();

            $row->item_kode = $row->item?->kode;
            $row->foto_lama_url = $row->foto_lama ? asset('storage/' . $row->foto_lama) : null;
            $row->tanda_tangan_url = $row->tanda_tangan ? asset('storage/' . $row->tanda_tangan) : null;

            return response()->json([
                'success' => true,
                'message' => 'Data pergantian alat berhasil diupdate',
                'data' => $row
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // kalau foto baru terupload tapi rollback
            if ($request->hasFile('foto_lama') && $newFoto && $newFoto !== $oldFoto) {
                Storage::disk('public')->delete($newFoto);
            }
            // kalau ttd baru tersimpan tapi rollback
            if ($newTtd) {
                Storage::disk('public')->delete($newTtd);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal update data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $row = PergantianAlat::findOrFail($id);

            if ($row->foto_lama) {
                Storage::disk('public')->delete($row->foto_lama);
            }
            if ($row->tanda_tangan) {
                Storage::disk('public')->delete($row->tanda_tangan);
            }

            $row->delete();
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
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
