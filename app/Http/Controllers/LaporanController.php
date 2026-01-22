<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function uangMasuk(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('kas_2210003')
            ->where('jenis_2210003', 'masuk')
            ->where('iduser_2210003', $userId)
            ->when($request->from && $request->to, function ($q) use ($request) {
                $q->whereBetween('tanggal_2210003', [$request->from, $request->to]);
            })
            ->orderBy('tanggal_2210003', 'desc')
            ->get();

        return response()->json($data);
    }

    public function uangKeluar(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('kas_2210003')
            ->where('jenis_2210003', 'keluar')
            ->where('iduser_2210003', $userId)
            ->when($request->from && $request->to, function ($q) use ($request) {
                $q->whereBetween('tanggal_2210003', [$request->from, $request->to]);
            })
            ->orderBy('tanggal_2210003', 'desc')
            ->get();

        return response()->json($data);
    }

    public function kirimUang(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('kirimuang_2210003')
            ->join('users', 'users.id', '=', 'kirimuang_2210003.dari_iduser_2210003')
            ->where('kirimuang_2210003.dari_iduser_2210003', $userId)
            ->when($request->from && $request->to, function ($q) use ($request) {
                $q->whereBetween('kirimuang_2210003.tglkirim_2210003', [$request->from, $request->to]);
            })
            ->select(
                'kirimuang_2210003.*',
                'users.name as nama_pengirim',
                'users.email as email_pengirim'
            )
            ->orderBy('kirimuang_2210003.tglkirim_2210003', 'desc')
            ->get();

        return response()->json($data);
    }


    public function mintaUang(Request $request)
    {
        $userId = $request->user()->id;

        $data = DB::table('mintauang_2210003')
            ->where('dari_iduser_2210003', $userId)
            ->when($request->from && $request->to, function ($q) use ($request) {
                $q->whereBetween('noref_2210003', [$request->from, $request->to]);
                // ⚠️ Jika tidak ada kolom tanggal di mintauang, sebaiknya tambahkan kolom tanggal
            })
            ->orderBy('noref_2210003', 'desc')
            ->get();

        return response()->json($data);
    }
}
