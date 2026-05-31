<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    /**
     * Menampilkan semua daftar transaksi khusus milik user yang sedang login.
     */
    public function index()
    {
        // Mengambil transaksi yang user_id-nya cocok dengan token yang dikirim Android
        $transaksi = Transaksi::where('user_id', auth()->id())->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $transaksi
        ], 200);
    }

    /**
     * Menyimpan data transaksi baru dan otomatis mengaitkannya ke user yang sedang login.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_transaksi' => 'required|string|max:255',
            'jumlah'         => 'required|integer|min:1',
            'jenis'          => 'required|in:pemasukan,pengeluaran',
            'keterangan'     => 'nullable|string'
        ]);

        try {
            // Saat proses create, otomatis sisipkan user_id dari auth()
            $transaksi = Transaksi::create([
                'user_id'        => auth()->id(),
                'nama_transaksi' => $request->nama_transaksi,
                'jumlah'         => $request->jumlah,
                'jenis'          => $request->jenis,
                'keterangan'     => $request->keterangan,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil ditambahkan',
                'data'    => $transaksi
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambah transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail SATU transaksi milik user yang sedang login.
     */
    public function show($id)
    {
        // Cari transaksi berdasarkan ID, tapi pastikan juga itu milik user yang sedang login
        $transaksi = Transaksi::where('user_id', auth()->id())->find($id);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $transaksi
        ], 200);
    }

    /**
     * Memperbarui data transaksi milik user yang sedang login.
     */
    public function update(Request $request, $id)
    {
        $transaksi = Transaksi::where('user_id', auth()->id())->find($id);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }

        $request->validate([
            'nama_transaksi' => 'sometimes|required|string|max:255',
            'jumlah'         => 'sometimes|required|integer|min:1',
            'jenis'          => 'sometimes|required|in:pemasukan,pengeluaran',
            'keterangan'     => 'nullable|string'
        ]);

        try {
            // Hanya memperbarui kolom yang dikirim dari request
            $transaksi->update($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil diperbarui',
                'data'    => $transaksi
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus transaksi milik user yang sedang login.
     */
    public function destroy($id)
    {
        $transaksi = Transaksi::where('user_id', auth()->id())->find($id);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }

        try {
            $transaksi->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}
