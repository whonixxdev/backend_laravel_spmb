<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use Illuminate\Http\Request;

class JurusanController extends Controller
{
    public function index()
    {
        $jurusan = Jurusan::all();

        return response()->json([
            'status' => 'success',
            'data' => $jurusan
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jurusan' => 'required|string|max:150',
            'kuota' => 'required|integer',
            'gelombang' => 'required|string|max:50',
            'biaya' => 'required|numeric',
        ]);

        $jurusan = Jurusan::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Jurusan berhasil ditambahkan',
            'data' => $jurusan
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $jurusan = Jurusan::find($id);

        if (!$jurusan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jurusan tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'nama_jurusan' => 'required|string|max:150',
            'kuota' => 'required|integer',
            'gelombang' => 'required|string|max:50',
            'biaya' => 'required|numeric',
        ]);

        $jurusan->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data jurusan berhasil diperbarui',
            'data' => $jurusan
        ], 200);
    }

    public function destroy($id)
    {
        $jurusan = Jurusan::find($id);

        if (!$jurusan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jurusan tidak ditemukan'
            ], 404);
        }

        $jurusan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Jurusan berhasil dihapus'
        ], 200);
    }
}