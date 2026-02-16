<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PeminjamanBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PeminjamanBarangController extends Controller
{
    public function index()
    {
        $data = PeminjamanBarang::with('item')
            ->orderBy('tanggal_pinjam', 'desc')
            ->get()
            ->map(function ($r) {
                $r->item_kode = $r->item?->kode; // tampil di tabel
                $r->foto_barang_url = $r->foto_barang ? asset('storage/' . $r->foto_barang) : null;
                $r->tanda_tangan_url = $r->tanda_tangan ? asset('storage/' . $r->tanda_tangan) : null;
                return $r;
            });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id)
    {
        $data = PeminjamanBarang::with('item')->findOrFail($id);
        $data->item_kode = $data->item?->kode;
        $data->foto_barang_url = $data->foto_barang ? asset('storage/' . $data->foto_barang) : null;
        $data->tanda_tangan_url = $data->tanda_tangan ? asset('storage/' . $data->tanda_tangan) : null;

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_id'               => 'required|exists:items,id',
            'tanggal_pinjam'        => 'required|date',
            'tanggal_kembali'       => 'nullable|date|after_or_equal:tanggal_pinjam',
            'pic'                   => 'required|string',
            'foto_barang'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'tanda_tangan_base64'   => 'required|string',
        ]);

        DB::beginTransaction();

        $fotoPath = null;
        $ttdPath  = null;

        try {
            $item = Item::findOrFail($request->item_id);

            // foto barang
            if ($request->hasFile('foto_barang')) {
                $fotoPath = $request->file('foto_barang')->store('peminjaman-barang/foto', 'public');
            }

            // tanda tangan base64 -> simpan file png
            $ttdPath = $this->storeSignatureBase64($request->tanda_tangan_base64);

            $row = PeminjamanBarang::create([
                'item_id'         => $item->id,
                'nama_barang'     => $item->nama,
                'satuan'          => $item->satuan,
                'tanggal_pinjam'  => $request->tanggal_pinjam,
                'tanggal_kembali' => $request->tanggal_kembali,
                'pic'             => $request->pic,
                'foto_barang'     => $fotoPath,
                'tanda_tangan'    => $ttdPath,
            ]);

            DB::commit();

            $row->load('item');
            $row->item_kode = $row->item?->kode;
            $row->foto_barang_url = $row->foto_barang ? asset('storage/' . $row->foto_barang) : null;
            $row->tanda_tangan_url = $row->tanda_tangan ? asset('storage/' . $row->tanda_tangan) : null;

            return response()->json([
                'success' => true,
                'message' => 'Peminjaman barang berhasil disimpan',
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
            'tanggal_pinjam'        => 'required|date',
            'tanggal_kembali'       => 'nullable|date|after_or_equal:tanggal_pinjam',
            'pic'                   => 'required|string',
            'foto_barang'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            // ✅ update: boleh kosong kalau user tidak tanda tangan ulang
            'tanda_tangan_base64'   => 'nullable|string',
        ]);

        DB::beginTransaction();

        $oldFoto = null;
        $oldTtd  = null;
        $newFoto = null;
        $newTtd  = null;

        try {
            $row = PeminjamanBarang::findOrFail($id);
            $item = Item::findOrFail($request->item_id);

            $oldFoto = $row->foto_barang;
            $oldTtd  = $row->tanda_tangan;

            $newFoto = $oldFoto;
            $newTtd  = $oldTtd;

            // foto baru?
            if ($request->hasFile('foto_barang')) {
                $newFoto = $request->file('foto_barang')->store('peminjaman-barang/foto', 'public');
            }

            // ttd baru? (kalau user teken ulang)
            if ($request->filled('tanda_tangan_base64')) {
                $newTtd = $this->storeSignatureBase64($request->tanda_tangan_base64);
            }

            $row->update([
                'item_id'         => $item->id,
                'nama_barang'     => $item->nama,
                'satuan'          => $item->satuan,
                'tanggal_pinjam'  => $request->tanggal_pinjam,
                'tanggal_kembali' => $request->tanggal_kembali,
                'pic'             => $request->pic,
                'foto_barang'     => $newFoto,
                'tanda_tangan'    => $newTtd,
            ]);

            // hapus file lama jika ada file baru
            if ($request->hasFile('foto_barang') && $oldFoto) {
                Storage::disk('public')->delete($oldFoto);
            }
            if ($request->filled('tanda_tangan_base64') && $oldTtd) {
                Storage::disk('public')->delete($oldTtd);
            }

            DB::commit();

            $row->load('item');
            $row->item_kode = $row->item?->kode;
            $row->foto_barang_url = $row->foto_barang ? asset('storage/' . $row->foto_barang) : null;
            $row->tanda_tangan_url = $row->tanda_tangan ? asset('storage/' . $row->tanda_tangan) : null;

            return response()->json([
                'success' => true,
                'message' => 'Peminjaman barang berhasil diupdate',
                'data' => $row
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // kalau upload file baru tapi gagal, bersihkan file baru
            if ($request->hasFile('foto_barang') && $newFoto && $newFoto !== $oldFoto) {
                Storage::disk('public')->delete($newFoto);
            }
            if ($request->filled('tanda_tangan_base64') && $newTtd && $newTtd !== $oldTtd) {
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
            $row = PeminjamanBarang::findOrFail($id);

            if ($row->foto_barang) Storage::disk('public')->delete($row->foto_barang);
            if ($row->tanda_tangan) Storage::disk('public')->delete($row->tanda_tangan);

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

    // ================= HELPER =================
    private function storeSignatureBase64(string $base64): string
    {
        // format biasanya: data:image/png;base64,xxxxx
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
            $ext = strtolower($type[1]);
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $ext = 'png';
            }
        } else {
            // fallback
            $ext = 'png';
        }

        $data = base64_decode($base64);

        if ($data === false) {
            throw new \Exception("Tanda tangan base64 tidak valid");
        }

        $fileName = 'peminjaman-barang/ttd/' . uniqid('ttd_') . '.' . $ext;
        Storage::disk('public')->put($fileName, $data);

        return $fileName;
    }
}
