<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KirimUang;
use Carbon\Carbon;
use App\Models\User;
use App\Models\SaldoUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info('=== KIRIM UANG START ===');
        Log::info('Email Penerima: ' . $request->email_penerima);
        Log::info('Jumlah: ' . $request->jmluang);

        $request->validate([
            'email_penerima' => 'required|email',
            'jmluang' => 'required|numeric|min:1',
        ], [
            'email_penerima.required' => 'Email wajib diisi.',
            'email_penerima.email' => 'Alamat Email harus yang valid',
            'jmluang.required' => 'Jumlah uang masuk wajib diisi.',
            'jmluang.numeric' => 'Jumlah uang masuk harus berupa angka.',
            'jmluang.min' => 'Jumlah uang minimal Rp 1.',
        ]);

        // Cek apakah user penerima ada
        $ambilDataUser = User::where('email', $request->email_penerima)->first();

        if (!$ambilDataUser) {
            return response()->json([
                'status' => false,
                'pesan' => 'Email yang anda input tidak ditemukan...'
            ], 404);
        }

        $idUserPenerima = $ambilDataUser->id;
        $idUserPengirim = $request->user()->id;

        // Cek apakah kirim ke diri sendiri
        if ($idUserPenerima === $idUserPengirim) {
            return response()->json([
                'status' => false,
                'pesan' => 'Tidak bisa mengirim uang ke diri sendiri'
            ], 400);
        }

        // Cek saldo pengirim
        $dataSaldoUser = SaldoUser::where('iduser_2210003', $idUserPengirim)->first();

        if (!$dataSaldoUser) {
            return response()->json([
                'status' => false,
                'pesan' => 'Data saldo tidak ditemukan'
            ], 404);
        }

        $saldoUserPengirim = $dataSaldoUser->jumlahsaldo_2210003;

        if (intval($request->jmluang) > intval($saldoUserPengirim)) {
            return response()->json([
                'status' => false,
                'pesan' => 'Saldo anda tidak mencukupi'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Kurangi saldo pengirim
            $dataSaldoUser->jumlahsaldo_2210003 = $dataSaldoUser->jumlahsaldo_2210003 - $request->jmluang;
            $dataSaldoUser->save();

            Log::info('Saldo pengirim dikurangi');

            // Simpan ke table kirim uang
            $kirimUang = new KirimUang();
            $kirimUang->noref_2210003 = $this->createNoTransaksiKasMasuk();
            $kirimUang->dari_iduser_2210003 = $idUserPengirim;
            $kirimUang->ke_iduser_2210003 = $idUserPenerima;
            $kirimUang->jumlahuang_2210003 = $request->jmluang;
            $kirimUang->save();

            Log::info('Data kirim uang disimpan');

            // Tambah saldo penerima
            $saldoUserPenerima = SaldoUser::where('iduser_2210003', $idUserPenerima)->first();

            if (!$saldoUserPenerima) {
                throw new \Exception('Data saldo penerima tidak ditemukan');
            }

            $saldoUserPenerima->jumlahsaldo_2210003 = $saldoUserPenerima->jumlahsaldo_2210003 + $request->jmluang;
            $saldoUserPenerima->save();

            Log::info('Saldo penerima ditambah');

            // Kirim notifikasi Firebase (Optional - tidak wajib berhasil)
            try {
                $this->sendFirebaseNotification($ambilDataUser, $request);
            } catch (\Exception $e) {
                // Log error tapi tidak rollback transaksi
                Log::warning('Firebase notification failed: ' . $e->getMessage());
            }

            DB::commit();

            Log::info('=== KIRIM UANG SUCCESS ===');

            return response()->json([
                'status' => true,
                'pesan' => 'Kirim uang berhasil di lakukan'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kirim uang error: ' . $e->getMessage());

            return response()->json([
                'pesan' => 'Gagal melakukan transaksi: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }

    private function sendFirebaseNotification($penerima, $request)
    {
        // Cek apakah Firebase sudah dikonfigurasi
        if (!config('firebase.credentials.file')) {
            Log::info('Firebase not configured, skipping notification');
            return;
        }

        $tokenFcmPenerima = $penerima->fcmtoken ?? null;

        if (empty($tokenFcmPenerima)) {
            Log::info('FCM token not found for user: ' . $penerima->id);
            return;
        }

        try {
            $messaging = \Kreait\Laravel\Firebase\Facades\Firebase::messaging();
            $pesanKePenerima = 'Halo ' . $penerima->name .
                ' ada kiriman uang dari ' . $request->user()->name .
                ' sejumlah: Rp ' . number_format($request->jmluang, 0, ',', '.');

            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $tokenFcmPenerima)
                ->withNotification([
                    'title' => 'Ada Kiriman Uang',
                    'body' => $pesanKePenerima
                ]);

            $messaging->send($message);
            Log::info('Firebase notification sent successfully');
        } catch (\Exception $e) {
            Log::error('Firebase error: ' . $e->getMessage());
            throw $e;
        }
    }
}
