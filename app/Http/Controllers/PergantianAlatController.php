<?php

namespace App\Http\Controllers;

use App\Models\PergantianAlat;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PergantianAlatExport;

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
        $data->tanda_tangan_url = $data->tanda_tangan ? asset('storage/' . $data->tanda_tangan) : null;

        return response()->json(['success' => true, 'data' => $data]);
    }

    // =========================
    // helper: simpan base64 png
    // =========================
    private function storeSignatureBase64(?string $dataUrl): ?string
    {
        if (!$dataUrl) return null;

        $dataUrl = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
        $binary = base64_decode($dataUrl);

        if ($binary === false) return null;

        $fileName = 'tanda-tangan/' . uniqid('ttd_') . '.png';
        Storage::disk('public')->put($fileName, $binary);

        return $fileName;
    }

    // helper: lempar 422 kalau saldo kurang
    private function ensureSaldoCukup(Item $item, int $nominal, string $msgPrefix = 'Saldo tidak mencukupi')
    {
        if ((int)$item->saldo < $nominal) {
            throw ValidationException::withMessages([
                'nominal' => ["{$msgPrefix}. Saldo saat ini: {$item->saldo}"],
            ]);
        }
    }

    // =========================
    // STORE (saldo berkurang)
    // =========================
    public function store(Request $request)
    {
        $request->validate([
            'item_id'             => 'required|exists:items,id',
            'tanggal'             => 'required|date',
            'nominal'             => 'required|integer|min:1',
            'pic'                 => 'required|string',
            'tanda_tangan_base64' => 'required|string',
            'foto_lama'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $fotoPath = null;
        $ttdPath  = null;

        DB::beginTransaction();
        try {
            // lock item biar saldo aman
            $item = Item::where('id', $request->item_id)->lockForUpdate()->firstOrFail();

            $nominal = (int)$request->nominal;

            // ✅ validasi saldo cukup (lempar 422)
            $this->ensureSaldoCukup($item, $nominal);

            // foto lama
            if ($request->hasFile('foto_lama')) {
                $fotoPath = $request->file('foto_lama')->store('pergantian-alat', 'public');
            }

            // tanda tangan
            $ttdPath = $this->storeSignatureBase64($request->tanda_tangan_base64);
            if (!$ttdPath) {
                throw ValidationException::withMessages([
                    'tanda_tangan_base64' => ['Tanda tangan tidak valid'],
                ]);
            }

            // ✅ kurangi saldo
            $item->saldo = (int)$item->saldo - $nominal;
            $item->save();

            // simpan pergantian
            $row = PergantianAlat::create([
                'item_id'      => $item->id,
                'nama_barang'  => $item->nama,
                'satuan'       => $item->satuan,
                'tanggal'      => $request->tanggal,
                'nominal'      => $nominal,
                'pic'          => $request->pic,
                'tanda_tangan' => $ttdPath,
                'foto_lama'    => $fotoPath,
            ]);

            DB::commit();

            $row->load('item');
            $row->item_kode = $row->item?->kode;
            $row->foto_lama_url = $row->foto_lama ? asset('storage/' . $row->foto_lama) : null;
            $row->tanda_tangan_url = $row->tanda_tangan ? asset('storage/' . $row->tanda_tangan) : null;

            return response()->json([
                'success' => true,
                'message' => 'Data pergantian alat berhasil disimpan & saldo berkurang',
                'data' => $row
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($fotoPath) Storage::disk('public')->delete($fotoPath);
            if ($ttdPath) Storage::disk('public')->delete($ttdPath);

            // biarkan ValidationException tetap 422
            if ($e instanceof ValidationException) throw $e;

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================
    // UPDATE (rollback saldo lama -> apply saldo baru)
    // =========================
    public function update(Request $request, $id)
    {
        $request->validate([
            'item_id'             => 'required|exists:items,id',
            'tanggal'             => 'required|date',
            'nominal'             => 'required|integer|min:1',
            'pic'                 => 'required|string',
            'tanda_tangan_base64' => 'required|string',
            'foto_lama'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $oldFoto = null;
        $newFoto = null;

        $oldTtd = null;
        $newTtd = null;

        DB::beginTransaction();
        try {
            $row = PergantianAlat::findOrFail($id);

            $nominalBaru = (int)$request->nominal;
            $nominalLama = (int)$row->nominal;

            // lock item lama
            $itemLama = Item::where('id', $row->item_id)->lockForUpdate()->firstOrFail();

            // ✅ rollback saldo lama
            $itemLama->saldo = (int)$itemLama->saldo + $nominalLama;
            $itemLama->save();

            // lock item baru (bisa sama / beda)
            $itemBaru = Item::where('id', $request->item_id)->lockForUpdate()->firstOrFail();

            // ✅ cek saldo cukup untuk nominal baru
            $this->ensureSaldoCukup($itemBaru, $nominalBaru, 'Saldo tidak mencukupi untuk update');

            // ✅ apply pengurangan saldo baru
            $itemBaru->saldo = (int)$itemBaru->saldo - $nominalBaru;
            $itemBaru->save();

            // FOTO
            $oldFoto = $row->foto_lama;
            $newFoto = $oldFoto;

            if ($request->hasFile('foto_lama')) {
                $newFoto = $request->file('foto_lama')->store('pergantian-alat', 'public');
            }

            // TTD
            $oldTtd = $row->tanda_tangan;
            $newTtd = $this->storeSignatureBase64($request->tanda_tangan_base64);
            if (!$newTtd) {
                throw ValidationException::withMessages([
                    'tanda_tangan_base64' => ['Tanda tangan tidak valid'],
                ]);
            }

            // update row
            $row->update([
                'item_id'      => $itemBaru->id,
                'nama_barang'  => $itemBaru->nama,
                'satuan'       => $itemBaru->satuan,
                'tanggal'      => $request->tanggal,
                'nominal'      => $nominalBaru,
                'pic'          => $request->pic,
                'tanda_tangan' => $newTtd,
                'foto_lama'    => $newFoto,
            ]);

            // hapus file lama setelah sukses
            if ($request->hasFile('foto_lama') && $oldFoto) {
                Storage::disk('public')->delete($oldFoto);
            }
            if ($oldTtd) {
                Storage::disk('public')->delete($oldTtd);
            }

            DB::commit();

            $row->load('item');
            $row->item_kode = $row->item?->kode;
            $row->foto_lama_url = $row->foto_lama ? asset('storage/' . $row->foto_lama) : null;
            $row->tanda_tangan_url = $row->tanda_tangan ? asset('storage/' . $row->tanda_tangan) : null;

            return response()->json([
                'success' => true,
                'message' => 'Data pergantian alat berhasil diupdate & saldo tersinkron',
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

            if ($e instanceof ValidationException) throw $e;

            return response()->json([
                'success' => false,
                'message' => 'Gagal update data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================
    // DESTROY (saldo dikembalikan)
    // =========================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = PergantianAlat::findOrFail($id);

            // kembalikan saldo
            $item = Item::where('id', $row->item_id)->lockForUpdate()->firstOrFail();
            $item->saldo = (int)$item->saldo + (int)$row->nominal;
            $item->save();

            // hapus file
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
                'message' => 'Data berhasil dihapus & saldo dikembalikan'
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

    // =============================
    // EXPORT EXCEL
    // =============================
    public function export()
    {
        return Excel::download(new PergantianAlatExport, 'pergantian-alat.xlsx');
    }
}