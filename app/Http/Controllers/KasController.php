<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kas;
use App\Models\SaldoUser;
use Illuminate\Support\Facades\DB;

class KasController extends Controller
{
    public function getDataKasMasuk(Request $request)
    {
        $user = $request->user();
        $dataKasMasuk = Kas::where('jenis_2210003', 'masuk')->where('iduser_2210003', $user->id)->get();
        return response()->json([
            'data' => $dataKasMasuk
        ]);
    }

    private function createNoTransaksiKasMasuk($tanggal)
    {
        $randomNumber = rand(0000, 99999);
        $formatTanggal = date('dmY', strtotime($tanggal));
        $noTransaksi = 'M' . $formatTanggal . $randomNumber;
        return $noTransaksi;
    }

    public function insertDataKasMasuk(Request $request)
    {
        $request->validate([
            'tgl' => 'required|date',
            'jmluang' => 'required|numeric',
            'ket' => 'required|string|max:255',
        ], [
            'tgl.required' => 'Tanggal wajib diisi.',
            'tgl.date' => 'Tanggal harus berupa format yang valid.',
            'jmluang.required' => 'Jumlah uang masuk wajib diisi.',
            'jmluang.numeric' => 'Jumlah uang masuk harus berupa angka.',
            'ket.required' => 'Keterangan wajib diisi.',
        ]);
        DB::beginTransaction();
        try {
            $noTransaksi = $this->createNoTransaksiKasMasuk($request->tgl);
            $user = $request->user();
            $kas = Kas::create([
                'notrans_2210003' => $noTransaksi,
                'tanggal_2210003' => $request->tgl,
                'jumlahuang_2210003' => $request->jmluang,
                'keterangan_2210003' => $request->ket,
                'iduser_2210003' => $user->id,
                'jenis_2210003' => 'masuk',
            ]);
            // update saldo
            $dataSaldoUser = SaldoUser::where('iduser_2210003', $user->id)->first();
            $dataSaldoUser->jumlahsaldo_2210003 = $dataSaldoUser->jumlahsaldo_2210003 +
                $request->jmluang;
            $dataSaldoUser->save();
            DB::commit();

            return response()->json([
                'message' => 'Data kas masuk berhasil ditambahkan!',
                'data' => $kas
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'msg' => 'Error : ' . $e->getMessage()
            ]);
        }
    }

    public function getDetailKasMasuk(Request $request, $notrans)
    {
        $user = $request->user();
        $dataKasMasuk = Kas::where('notrans_2210003', $notrans)
            ->where('iduser_2210003', $user->id)
            ->first();

        if (!$dataKasMasuk) {
            return response()->json([
                'status' => false,
                'message' => 'Data kas masuk tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $dataKasMasuk
        ]);
    }

    public function deleteDataKasMasuk(Request $request, $notrans)
    {
        DB::beginTransaction();

        try {
            $kas = Kas::where('notrans_2210003', $notrans)->firstOrFail();

            $saldo = SaldoUser::where(
                'iduser_2210003',
                $kas->iduser_2210003
            )->firstOrFail();

            $saldo->jumlahsaldo_2210003 -= $kas->jumlahuang_2210003;
            $saldo->save();

            $kas->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data kas masuk berhasil dihapus!'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDataKasMasuk(Request $request, $notrans)
    {
        $request->validate([
            'tgl' => 'required|date',
            'jmluang' => 'required|numeric',
            'ket' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // AMBIL DATA KAS
            $kas = Kas::where('notrans_2210003', $notrans)->firstOrFail();

            // SIMPAN JUMLAH LAMA
            $jumlahLama = $kas->jumlahuang_2210003;

            // AMBIL SALDO USER
            $saldo = SaldoUser::where(
                'iduser_2210003',
                $kas->iduser_2210003
            )->first();

            // 🔴 JIKA SALDO BELUM ADA
            if (!$saldo) {
                throw new \Exception('Saldo user belum tersedia');
            }

            // HITUNG ULANG SALDO
            $saldo->jumlahsaldo_2210003 =
                ($saldo->jumlahsaldo_2210003 - $jumlahLama) + $request->jmluang;

            $saldo->save();

            // UPDATE DATA KAS
            $kas->update([
                'tanggal_2210003' => $request->tgl,
                'jumlahuang_2210003' => $request->jmluang,
                'keterangan_2210003' => $request->ket,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data kas masuk berhasil diperbarui',
                'data' => $kas,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }


    public function getDataKasKeluar(Request $request)
    {
        $user = $request->user();

        $data = Kas::where('jenis_2210003', 'keluar')
            ->where('iduser_2210003', $user->id)
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }

    public function insertDataKasKeluar(Request $request)
    {
        $request->validate([
            'tgl' => 'required|date',
            'jmluang' => 'required|numeric|min:1',
            'ket' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $user = $request->user();
            $noTransaksi = 'K' . date('dmY', strtotime($request->tgl)) . rand(1000, 9999);

            $saldo = SaldoUser::where('iduser_2210003', $user->id)->firstOrFail();

            // ❗ CEK SALDO
            if ($saldo->jumlahsaldo_2210003 < $request->jmluang) {
                throw new \Exception('Saldo tidak mencukupi');
            }

            $kas = Kas::create([
                'notrans_2210003' => $noTransaksi,
                'tanggal_2210003' => $request->tgl,
                'jumlahuang_2210003' => $request->jmluang,
                'keterangan_2210003' => $request->ket,
                'jenis_2210003' => 'keluar',
                'iduser_2210003' => $user->id,
            ]);

            // 🔥 SALDO BERKURANG
            $saldo->jumlahsaldo_2210003 -= $request->jmluang;
            $saldo->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Kas keluar berhasil disimpan',
                'data' => $kas
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDataKasKeluar(Request $request, $notrans)
    {
        $request->validate([
            'tgl' => 'required|date',
            'jmluang' => 'required|numeric|min:1',
            'ket' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $kas = Kas::where('notrans_2210003', $notrans)
                ->where('jenis_2210003', 'keluar')
                ->firstOrFail();

            $saldo = SaldoUser::where(
                'iduser_2210003',
                $kas->iduser_2210003
            )->firstOrFail();

            $jumlahLama = $kas->jumlahuang_2210003;

            // kembalikan saldo lama, lalu kurangi yang baru
            $saldo->jumlahsaldo_2210003 =
                ($saldo->jumlahsaldo_2210003 + $jumlahLama) - $request->jmluang;

            if ($saldo->jumlahsaldo_2210003 < 0) {
                throw new \Exception('Saldo tidak mencukupi');
            }

            $saldo->save();

            $kas->update([
                'tanggal_2210003' => $request->tgl,
                'jumlahuang_2210003' => $request->jmluang,
                'keterangan_2210003' => $request->ket,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Kas keluar berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteDataKasKeluar(Request $request, $notrans)
    {
        DB::beginTransaction();

        try {
            $kas = Kas::where('notrans_2210003', $notrans)
                ->where('jenis_2210003', 'keluar')
                ->firstOrFail();

            $saldo = SaldoUser::where(
                'iduser_2210003',
                $kas->iduser_2210003
            )->firstOrFail();

            // 🔁 SALDO DIKEMBALIKAN
            $saldo->jumlahsaldo_2210003 += $kas->jumlahuang_2210003;
            $saldo->save();

            $kas->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Kas keluar berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function getDetailKasKeluar(Request $request, $notrans)
    {
        $user = $request->user();

        $data = Kas::where('notrans_2210003', $notrans)
            ->where('jenis_2210003', 'keluar')
            ->where('iduser_2210003', $user->id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data kas keluar tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}


