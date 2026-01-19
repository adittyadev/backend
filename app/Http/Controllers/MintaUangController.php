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
            'pesan' => 'Permintaan Uang berhasil dilakukan, silahkan disimpan Qr Code yang telah disediakan atau di ScreenShoot.',
            'data' => $simpanTransaksi,
        ]);
    }

    public function getDataDetail(Request $request, $noReferensi)
    {
        // PERBAIKAN: Gunakan nama kolom yang benar dengan suffix _2210003
        $cekData = MintaUang::join(
            'users',
            'users.id',
            '=',
            'minta_uangs.dari_iduser_2210003' // Perbaiki join column
        )
            ->select([
                'minta_uangs.noref_2210003 as noref',
                'minta_uangs.tglminta_2210003 as tglminta',
                'minta_uangs.dari_iduser_2210003 as dari_iduser',
                'users.name AS dari_namauser',
                'minta_uangs.jumlahuang_2210003 as jumlahuang',
                'minta_uangs.stt_2210003 as stt'
            ])
            ->where('minta_uangs.noref_2210003', $noReferensi)
            ->first();

        if ($cekData) {
            // Cek apakah status sudah diproses
            if ($cekData->stt !== 'pending') {
                return response()->json([
                    'status' => false,
                    'pesan' => 'QR Code ini sudah pernah diproses sebelumnya'
                ], 400);
            }

            // Cek apakah user scan QR sendiri
            if ($cekData->dari_iduser === $request->user()->id) {
                return response()->json([
                    'status' => false,
                    'pesan' => 'Tidak bisa di proses, Qr Code berasal dari permintaan anda sendiri'
                ], 400);
            }

            return response()->json([
                'status' => true,
                'result' => $cekData
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'pesan' => 'Data QR tidak terdaftar di sistem'
            ], 404);
        }
    }

    public function prosesPermintaan(Request $request, $noReferensi)
    {
        $request->validate([
            'jmluang' => 'required|numeric|min:1',
        ]);

        $jumlahdiMinta = $request->jmluang;

        // Cek saldo user yang memberikan (pemberi)
        $saldoUser = SaldoUser::where('iduser_2210003', $request->user()->id)->first();

        if (!$saldoUser) {
            return response()->json([
                'status' => false,
                'pesan' => 'Data saldo tidak ditemukan'
            ], 404);
        }

        $jumlahSaldo = $saldoUser->jumlahsaldo_2210003;

        // Validasi saldo mencukupi
        if ($jumlahSaldo < $jumlahdiMinta) {
            return response()->json([
                'status' => false,
                'pesan' => 'Jumlah saldo tidak mencukupi'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Ambil data permintaan
            $mintaUang = MintaUang::where('noref_2210003', $noReferensi)->first();

            if (!$mintaUang) {
                throw new \Exception('Data permintaan tidak ditemukan');
            }

            // Cek status masih pending
            if ($mintaUang->stt_2210003 !== 'pending') {
                throw new \Exception('Permintaan ini sudah pernah diproses');
            }

            // Validasi jumlah yang diminta sesuai
            if ($mintaUang->jumlahuang_2210003 != $jumlahdiMinta) {
                throw new \Exception('Jumlah uang tidak sesuai dengan permintaan');
            }

            // Kurangi saldo pemberi
            $saldoUser->jumlahsaldo_2210003 = $saldoUser->jumlahsaldo_2210003 - $jumlahdiMinta;
            $saldoUser->save();

            // Update table minta uang
            $mintaUang->ke_iduser_2210003 = $request->user()->id;
            $mintaUang->stt_2210003 = 'sukses';
            $mintaUang->tglsukses_2210003 = Carbon::now();
            $mintaUang->save();

            // Tambah saldo penerima (yang minta)
            $saldoUserPenerima = SaldoUser::where('iduser_2210003', $mintaUang->dari_iduser_2210003)->first();

            if (!$saldoUserPenerima) {
                throw new \Exception('Data saldo penerima tidak ditemukan');
            }

            $saldoUserPenerima->jumlahsaldo_2210003 = $saldoUserPenerima->jumlahsaldo_2210003 + $jumlahdiMinta;
            $saldoUserPenerima->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'pesan' => 'Permintaan uang berhasil di proses.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'pesan' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
