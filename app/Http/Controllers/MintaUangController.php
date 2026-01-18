<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MintaUang;
use Carbon\Carbon;

class MintaUangController extends Controller
{
    private function createNoReferensi($idUser = null)
    {
        $randomNumber = rand(0000, 99999);
        $formatTanggal = date('dmYHis', strtotime(Carbon::now()));
        $noTransaksi = 'MU' . $formatTanggal . $randomNumber . $idUser;
        return $noTransaksi;
    }

    public function insertDataMintaUang(Request $request)
    {
        $request->validate([
            'jmluang' => 'required|numeric',
        ], [
            'jmluang.required' => 'Jumlah uang masuk wajib diisi.',
            'jmluang.numeric' => 'Jumlah uang masuk harus berupa angka.',
        ]);
        $simpanTransaksi = MintaUang::create([
            'noref_2210003' => $this->createNoReferensi($request->user()->id),
            'dari_iduser_2210003' => $request->user()->id,
            'jumlahuang_2210003' => $request->jmluang,
            'stt_2210003' => 'pending'
        ]);
        return response()->json([
            'status' => true,
            'pesan' => 'Permintaan Uang berhasil dilakukan, silahkan 
disimpan Qr Code yang telah disediakan atau di ScreenShoot.',
            'data' => $simpanTransaksi,
        ]);
    }
}
