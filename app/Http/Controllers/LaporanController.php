<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    // UANG MASUK
    public function uangMasuk(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('kas_2210003')
            ->where('jenis_2210003', 'masuk')
            ->where('iduser_2210003', $userId)
            ->orderBy('tanggal_2210003', 'desc')
            ->get();

        return response()->json($data);
    }

    // UANG KELUAR
    public function uangKeluar(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('kas_2210003')
            ->where('jenis_2210003', 'keluar')
            ->where('iduser_2210003', $userId)
            ->orderBy('tanggal_2210003', 'desc')
            ->get();

        return response()->json($data);
    }

    // KIRIM UANG
    public function kirimUang(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('kirimuang_2210003')
            ->where('dari_iduser_2210003', $userId)
            ->orderBy('tglkirim_2210003', 'desc')
            ->get();

        return response()->json($data);
    }

    // MINTA UANG
    public function mintaUang(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('mintauang_2210003')
            ->where('dari_iduser_2210003', $userId)
            ->orderBy('noref_2210003', 'desc')
            ->get();

        return response()->json($data);
    }
}
