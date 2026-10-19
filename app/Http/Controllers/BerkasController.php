<?php

namespace App\Http\Controllers;

use App\Models\Berkas;
use App\Models\PendaftaranSpmb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BerkasController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $berkas = Berkas::where('id_user', $user->id_user)->first();

        return response()->json([
            'status' => 'success',
            'data'   => $berkas
        ], 200);
    }

    public function upload(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'file_ijazah_skl'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'file_kk'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'file_aktd_kelahiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'file_pasfoto'        => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            ]);

            $berkas = Berkas::firstOrCreate(['id_user' => $user->id_user]);

            if ($request->hasFile('file_ijazah_skl')) {
                if ($berkas->file_ijazah_skl) {
                    Storage::disk('supabase')->delete($berkas->file_ijazah_skl);
                }
                $berkas->file_ijazah_skl = $request->file('file_ijazah_skl')->store('berkas/ijazah', 'supabase');
            }

            if ($request->hasFile('file_kk')) {
                if ($berkas->file_kk) {
                    Storage::disk('supabase')->delete($berkas->file_kk);
                }
                $berkas->file_kk = $request->file('file_kk')->store('berkas/kk', 'supabase');
            }

            if ($request->hasFile('file_aktd_kelahiran')) {
                if ($berkas->file_aktd_kelahiran) {
                    Storage::disk('supabase')->delete($berkas->file_aktd_kelahiran);
                }
                $berkas->file_aktd_kelahiran = $request->file('file_aktd_kelahiran')->store('berkas/akta', 'supabase');
            }

            if ($request->hasFile('file_pasfoto')) {
                if ($berkas->file_pasfoto) {
                    Storage::disk('supabase')->delete($berkas->file_pasfoto);
                }
                $berkas->file_pasfoto = $request->file('file_pasfoto')->store('berkas/pasfoto', 'supabase');
            }

            $berkas->status_verifikasi = 'pending';
            $berkas->save();

            PendaftaranSpmb::where('id_user', $user->id_user)
                ->whereNull('id_berkas')
                ->update(['id_berkas' => $berkas->id_berkas]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Berkas berhasil diunggah ke Supabase',
                'data'    => $berkas
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifikasi(Request $request, $id)
    {
        $request->validate([
            'status_verifikasi' => 'required|in:belum_diunggah,pending,terverifikasi,ditolak',
        ]);

        $berkas = Berkas::find($id);

        if (!$berkas) {
            return response()->json([
                'status' => 'error',
                'message' => 'Berkas tidak ditemukan'
            ], 404);
        }

        $berkas->status_verifikasi = $request->status_verifikasi;
        $berkas->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status verifikasi berhasil diperbarui',
            'data' => $berkas
        ], 200);
    }

    public function konfirmasiPembayaran(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Konfirmasi pembayaran berhasil diterima'
        ], 200);
    }
}