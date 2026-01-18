<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KirimUang;
use Carbon\Carbon;
use App\Models\User;
use App\Models\SaldoUser;
use Illuminate\Support\Facades\DB;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;

class KirimUangController extends Controller
{
    private function createNoTransaksiKasMasuk()
    {
        $randomNumber = rand(0000, 99999);
        $formatTanggal = date('dmYHis', strtotime(Carbon::now()));
        $noTransaksi = 'KU' . $formatTanggal . $randomNumber;
        return $noTransaksi;
    }

    public function insertDataKirimUang(Request $request)
    {
        $request->validate([
            'email_penerima' => 'required|email',
            'jmluang' => 'required|numeric',
        ], [
            'email_penerima.required' => 'Email wajib diisi.',
            'email_penerima.email' => 'Alamat Email harus yang valid',
            'jmluang.required' => 'Jumlah uang masuk wajib diisi.',
            'jmluang.numeric' => 'Jumlah uang masuk harus berupa angka.',
        ]);
        $ambilDataUser = User::where('email', $request->email_penerima)->first();
        if ($ambilDataUser) {
            $idUserPenerima = $ambilDataUser->id;
            $idUserPengirim = $request->user()->id;

            $dataSaldoUser = SaldoUser::where('iduser_2210003', $idUserPengirim)->first();
            $saldoUserPengirim = $dataSaldoUser->jumlahsaldo_2210003;
            if (intval($request->jmluang) > intval($saldoUserPengirim)) {
                return response()->json([
                    'status' => false,
                    'pesan' => 'Saldo anda tidak mencukupi',
                ]);
            } else {
                DB::beginTransaction();
                try {
                    // Lakukan Pengurangan jumlah saldo user dan update data
                    $dataSaldoUser->jumlahsaldo_2210003 =
                        $dataSaldoUser->jumlahsaldo_2210003 - $request->jmluang;
                    $dataSaldoUser->save();
                    // simpan ke table kirim uang
                    $kirimUang = new KirimUang();
                    $kirimUang->noref_2210003 = $this->createNoTransaksiKasMasuk();
                    $kirimUang->dari_iduser_2210003 = $idUserPengirim;
                    $kirimUang->ke_iduser_2210003 = $idUserPenerima;
                    $kirimUang->jumlahuang_2210003 = $request->jmluang;
                    $kirimUang->save();


                    $saldoUserPenerima = SaldoUser::where('iduser_2210003', $idUserPenerima)->first();
                    $saldoUserPenerima->jumlahsaldo_2210003 =
                        $saldoUserPenerima->jumlahsaldo_2210003 + $request->jmluang;
                    $saldoUserPenerima->save();

                    $tokenFcmPenerima = $ambilDataUser->fcmtoken ?? null;

                    if (!empty($tokenFcmPenerima)) {
                        $messaging = Firebase::messaging();
                        $pesanKePenerima = 'halo ' . $ambilDataUser->name .
                            ' ada Kiriman uang dari ' . $request->user()->name .
                            ' sejumlah: ' . $request->jmluang;

                        $message = CloudMessage::withTarget('token', $tokenFcmPenerima)
                            ->withNotification([
                                'title' => 'Ada Kiriman Uang',
                                'body' => $pesanKePenerima
                            ]);

                        $messaging->send($message);
                    }

                    DB::commit();
                    return response()->json([
                        'status' => true,
                        'pesan' => 'Kirim uang berhasil di lakukan'
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['pesan' => 'Gagal Eksekusi Data ' . $e->getMessage(), 'status' => false], 500);
                }
            }
        } else {
            return response()->json([
                'status' => false,
                'pesan' => 'Email yang anda input tidak ditemukan...'
            ]);
        }
    }
}
