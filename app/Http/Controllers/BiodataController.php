<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biodata;
use App\Models\PendaftaranSpmb;
use Illuminate\Support\Facades\DB;
use Throwable;

class BiodataController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $biodata = Biodata::where('id_user', $user->id_user)->first();

        return response()->json([
            'status' => 'success',
            'data'   => $biodata
        ], 200);
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->user();

            $validated = $request->validate([
                'nama_lengkap'  => 'required|string|max:255',
                'nisn'          => 'nullable|string|max:20',
                'nik'           => 'nullable|string|max:20',
                'jenis_kelamin' => 'nullable|in:L,P',
                'tempat_lahir'  => 'nullable|string|max:100',
                'tanggal_lahir' => 'nullable|date',
                'alamat'        => 'nullable|string',
                'no_hp_siswa'   => 'nullable|string|max:20',
                'no_hp_ortu'    => 'nullable|string|max:20',
                'asal_smp'      => 'nullable|string|max:255',
            ]);

            $biodata = Biodata::updateOrCreate(
                ['id_user' => $user->id_user],
                [
                    'nama_lengkap'  => $validated['nama_lengkap'],
                    'nisn'          => $validated['nisn'] ?? null,
                    'nik'           => $validated['nik'] ?? null,
                    'jenis_kelamin' => $validated['jenis_kelamin'] ?? 'L',
                    'tempat_lahir'  => $validated['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
                    'alamat'        => $validated['alamat'] ?? null,
                    'no_hp_siswa'   => $validated['no_hp_siswa'] ?? null,
                    'no_hp_ortu'    => $validated['no_hp_ortu'] ?? null,
                    'asal_smp'      => $validated['asal_smp'] ?? null,
                ]
            );

            $pendaftaran = PendaftaranSpmb::where('id_user', $user->id_user)->first();
            if ($pendaftaran && !$pendaftaran->id_biodata) {
                $pendaftaran->id_biodata = $biodata->id_biodata;
                $pendaftaran->save();
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Biodata berhasil diperbarui',
                'data'    => $biodata
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ], 500);
        }
    }
}