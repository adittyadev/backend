<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MintaUang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SaldoUser;

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

    public function getDataDetail(Request $request, $noReferensi)
    {
        $cekData = MintaUang::join(
            'users',
            'users.id',
            '=',
            'dari_iduser'
        )
            ->select([
                'noref',
                'tglminta',
                'dari_iduser',
                DB::raw('users.name AS dari_namauser'),
                'jumlahuang'
            ])
            ->where('noref', $noReferensi)->first();
        if ($cekData) {
            if ($cekData->dari_iduser === $request->user()->id) {
                return response()->json([
                    'status' => false,
                    'pesan' => 'Tidak bisa di proses, Qr Code berasal dari permintaan anda sendiri'
                ], 404);
            } else {
                return response()->json([
                    'status' => true,
                    'result' => $cekData
                ], 200);
            }
        } else {
            return response()->json([
                'status' => false,
                'pesan' => 'Data QR tidak terdaftar di sistem'
            ], 404);
        }
    }

    public function prosesPermintaan(Request $request, $noReferensi)
    {
        $jumlahdiMinta = $request->jmluang;
        $saldoUser = SaldoUser::where('iduser', $request->user()->id)->first();
        $jumlahSaldo = $saldoUser->jumlahsaldo;
        // cek saldo user yang memberikan
        if ($jumlahSaldo < $jumlahdiMinta) {
            return response()->json([
                'status' => false,
                'pesan' => 'Jumlah saldo tidak mencukupi'
            ]);
        }
        DB::beginTransaction();
        try {

            $saldoUser->jumlahsaldo = $saldoUser->jumlahsaldo - $request->jmluang;
            $saldoUser->save();
            // update table minta uang
            $mintaUang = MintaUang::where('noref', $noReferensi)->first();
            $mintaUang->ke_iduser = $request->user()->id;
            $mintaUang->stt = 'sukses';
            $mintaUang->tglsukses = Carbon::now();
            $mintaUang->save();
            $saldoUserPenerima = SaldoUser::where('iduser', $mintaUang->dari_iduser)->first();
            $saldoUserPenerima->jumlahsaldo = $saldoUserPenerima->jumlahsaldo + $request->jmluang;
            $saldoUserPenerima->save();
            DB::commit();
            return response()->json([
                'status' => true,
                'pesan' => 'Permintaan uang berhasil di proses.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'pesan' => 'Error : ' . $e->getMessage()
            ]);
        }
    }
}
