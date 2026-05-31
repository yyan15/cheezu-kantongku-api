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
        $transaksi = Transaksi::where('user_id', auth()->id())
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }

    // ─── GET transaksi bulan ini ─────────────────────────────
    public function bulanIni()
    {
        $transaksi = Transaksi::where('user_id', auth()->id())
            ->whereMonth('tanggal', now()->month)
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

        $data = $request->all();
        $data['user_id'] = auth()->id();
        $transaksi = Transaksi::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditambahkan',
            'data'    => $transaksi,
        ], 201);
    }

    // ─── GET detail transaksi by ID ──────────────────────────
    public function show($id)
    {
        $transaksi = Transaksi::where('user_id', auth()->id())->find($id);

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
        $transaksi = Transaksi::where('user_id', auth()->id())->find($id);

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
        $transaksi = Transaksi::where('user_id', auth()->id())->find($id);

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

    // ─── GET statistik per kategori & 6 bulan terakhir ────────
    public function statistik()
    {
        $userId = auth()->id();
        $bulanSekarang = now()->month;
        $tahunSekarang = now()->year;

        // 1. Statistik per kategori bulan ini
        $perKategori = Transaksi::where('user_id', $userId)
            ->where('tipe', 'pengeluaran')
            ->whereMonth('tanggal', $bulanSekarang)
            ->whereYear('tanggal', $tahunSekarang)
            ->selectRaw('kategori, SUM(nominal) as total')
            ->groupBy('kategori')
            ->get();

        // 2. Data 6 bulan terakhir untuk Chart
        $enamBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $total = Transaksi::where('user_id', $userId)
                ->where('tipe', 'pengeluaran')
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->sum('nominal');

            $enamBulan[] = [
                'label' => $date->translatedFormat('M'),
                'total' => (float)$total
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'per_kategori' => $perKategori,
                'last_6_months' => $enamBulan
            ]
        ]);
    }

    // ─── GET search transaksi ────────────────────────────────
    public function search(Request $request)
    {
        $keyword   = $request->query('q', '');
        $transaksi = Transaksi::where('user_id', auth()->id())
            ->where('judul', 'like', "%{$keyword}%")
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }
}
