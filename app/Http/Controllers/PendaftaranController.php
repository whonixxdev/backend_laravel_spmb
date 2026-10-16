<?php

namespace App\Http\Controllers;

use App\Models\PendaftaranSpmb;
use Illuminate\Http\Request;

class PendaftaranController extends Controller
{
    public function index(Request $request)
    {
        $pendaftar = PendaftaranSpmb::with(['biodata', 'berkas', 'jurusanPilihan1'])->get();
        $supabasePublicUrl = "https://syjsjyslysaufahuqhkj.supabase.co/storage/v1/object/public/spmb-berkas";

        $result = $pendaftar->map(function ($item) use ($supabasePublicUrl) {
            $berkas = $item->berkas;
            
            return [
                'id_pendaftar'   => $item->id_pendaftaran,
                'no_pendaftaran' => $item->no_pendaftaran,
                'nama_lengkap'   => optional($item->biodata)->nama_lengkap ?? 'Belum isi biodata',
                'jurusan'        => optional($item->jurusanPilihan1)->nama_jurusan ?? 'Belum pilih jurusan',
                'status_berkas'  => optional($berkas)->status_verifikasi ?? 'belum_diunggah',
                'file_berkas' => [
                    'ijazah' => $berkas && $berkas->file_ijazah_skl ? $supabasePublicUrl . '/' . $berkas->file_ijazah_skl : null,
                    'kk'     => $berkas && $berkas->file_kk ? $supabasePublicUrl . '/' . $berkas->file_kk : null,
                    'akta'   => $berkas && $berkas->file_aktd_kelahiran ? $supabasePublicUrl . '/' . $berkas->file_aktd_kelahiran : null,
                    'pasfoto'=> $berkas && $berkas->file_pasfoto ? $supabasePublicUrl . '/' . $berkas->file_pasfoto : null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $result
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $pendaftaran = PendaftaranSpmb::with(['biodata', 'berkas', 'jurusanPilihan1', 'jurusanPilihan2'])->find($id);

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pendaftaran tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pendaftaran
        ], 200);
    }

    public function statusSiswa(Request $request)
    {
        $user = $request->user();
        $pendaftaran = PendaftaranSpmb::with(['biodata', 'berkas', 'jurusanPilihan1', 'jurusanPilihan2'])
            ->where('id_user', $user->id_user)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $pendaftaran
        ], 200);
    }

    public function pilihJurusan(Request $request)
    {
        $request->validate([
            'id_jurusan_1' => 'required|exists:tb_jurusan,id_jurusan',
            'id_jurusan_2' => 'nullable|exists:tb_jurusan,id_jurusan',
        ]);

        $user = $request->user();
        $pendaftaran = PendaftaranSpmb::where('id_user', $user->id_user)->first();

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pendaftaran belum tersedia'
            ], 404);
        }

        $pendaftaran->id_jurusan_1 = $request->id_jurusan_1;
        $pendaftaran->id_jurusan_2 = $request->id_jurusan_2 ?? null;
        $pendaftaran->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Pilihan jurusan berhasil disimpan',
            'data' => $pendaftaran
        ], 200);
    }

    public function dashboardStats(Request $request)
    {
        $totalPendaftar = PendaftaranSpmb::count();
        $totalProses = PendaftaranSpmb::where('status_kelulusan', 'proses')->count();
        $totalLulus = PendaftaranSpmb::where('status_kelulusan', 'lulus')->count();
        $totalTidakLulus = PendaftaranSpmb::where('status_kelulusan', 'tidak_lulus')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_pendaftar' => $totalPendaftar,
                'total_proses' => $totalProses,
                'total_lulus' => $totalLulus,
                'total_tidak_lulus' => $totalTidakLulus,
            ]
        ], 200);
    }

    public function updateKelulusan(Request $request, $id)
    {
        $request->validate([
            'status_kelulusan' => 'required|in:proses,lulus,tidak_lulus',
        ]);

        $pendaftaran = PendaftaranSpmb::find($id);

        if (!$pendaftaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pendaftaran tidak ditemukan'
            ], 404);
        }

        $pendaftaran->status_kelulusan = $request->status_kelulusan;
        $pendaftaran->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status kelulusan berhasil diperbarui',
            'data' => $pendaftaran
        ], 200);
    }

    public function exportExcel(Request $request)
    {
        $filename = 'data-siswa-terdaftar-' . date('Y-m-d') . '.csv';
        
        $pendaftar = PendaftaranSpmb::with(['biodata', 'jurusan1', 'berkas'])
            ->where('status_kelulusan', 'lulus')
            ->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($pendaftar) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'No Pendaftaran', 
                'Nama Lengkap', 
                'NISN', 
                'Jenis Kelamin', 
                'Asal SMP', 
                'Jurusan Diterima', 
                'Status Verifikasi Berkas'
            ]);

            foreach ($pendaftar as $item) {
                fputcsv($file, [
                    $item->no_pendaftaran,
                    optional($item->biodata)->nama_lengkap ?? '-',
                    optional($item->biodata)->nisn ?? '-',
                    optional($item->biodata)->jenis_kelamin ?? '-',
                    optional($item->biodata)->asal_smp ?? '-',
                    optional($item->jurusan1)->nama_jurusan ?? '-',
                    optional($item->berkas)->status_verifikasi ?? 'belum_diunggah',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}