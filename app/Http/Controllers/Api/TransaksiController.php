<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    // ─── GET semua transaksi ─────────────────────────────────
    public function index()
    {
        $transaksi = Transaksi::orderBy('tanggal', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    // ─── GET transaksi bulan ini ─────────────────────────────
    public function bulanIni()
    {
        $transaksi = Transaksi::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->orderBy('tanggal', 'desc')
            ->get();

        $totalPengeluaran = $transaksi->where('tipe', 'pengeluaran')->sum('nominal');
        $totalPemasukan   = $transaksi->where('tipe', 'pemasukan')->sum('nominal');

        return response()->json([
            'success'           => true,
            'data'              => $transaksi,
            'total_pengeluaran' => $totalPengeluaran,
            'total_pemasukan'   => $totalPemasukan,
            'saldo'             => $totalPemasukan - $totalPengeluaran,
        ]);
    }

    // ─── POST tambah transaksi ───────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'judul'    => 'required|string|max:255',
            'kategori' => 'required|string',
            'tipe'     => 'required|in:pengeluaran,pemasukan',
            'nominal'  => 'required|numeric|min:0',
            'tanggal'  => 'required|date',
            'catatan'  => 'nullable|string',
        ]);

        $transaksi = Transaksi::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditambahkan',
            'data'    => $transaksi,
        ], 201);
    }

    // ─── GET detail transaksi by ID ──────────────────────────
    public function show($id)
    {
        $transaksi = Transaksi::find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    // ─── PUT update transaksi ────────────────────────────────
    public function update(Request $request, $id)
    {
        $transaksi = Transaksi::find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'judul'    => 'sometimes|string|max:255',
            'kategori' => 'sometimes|string',
            'tipe'     => 'sometimes|in:pengeluaran,pemasukan',
            'nominal'  => 'sometimes|numeric|min:0',
            'tanggal'  => 'sometimes|date',
            'catatan'  => 'nullable|string',
        ]);

        $transaksi->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diupdate',
            'data'    => $transaksi,
        ]);
    }

    // ─── DELETE transaksi ────────────────────────────────────
    public function destroy($id)
    {
        $transaksi = Transaksi::find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        $transaksi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus',
        ]);
    }

    // ─── GET statistik per kategori ──────────────────────────
    public function statistik()
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $kategori = Transaksi::where('tipe', 'pengeluaran')
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('kategori, SUM(nominal) as total')
            ->groupBy('kategori')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $kategori,
        ]);
    }

    // ─── GET search transaksi ────────────────────────────────
    public function search(Request $request)
    {
        $keyword   = $request->query('q', '');
        $transaksi = Transaksi::where('judul', 'like', "%{$keyword}%")
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }
}
