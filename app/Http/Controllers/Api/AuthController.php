<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaldoUser;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        SaldoUser::create([
            'iduser_2210003' => $user->id,
            'jumlahsaldo_2210003' => 0,
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password yang Anda masukkan salah.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->fcmtoken = $request->token_fcm;
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function dataPengguna(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user
        ]);
    }

    public function updateUser(Request $request)
    {
        $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|unique:users,email,' . $request->user()->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        $user = $request->user();

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            if ($request->password !== $request->password_confirmation) {
                return response()->json(['message' => 'Password confirmation does not match.'], 400);
            }
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user,
        ], 200);
    }

    public function updateUserPhoto(Request $request)
    {
        $request->validate([
            'photo2210003' => 'required|file|mimes:jpeg,jpg,png|max:5120',
        ], [
            'photo2210003.required' => 'Foto wajib diisi',
            'photo2210003.file' => 'File yang di unggah harus berupa gambar.',
            'photo2210003.mimes' => 'File gambar harus berformat jpeg, jpg, atau png.',
            'photo2210003.max' => 'Ukuran file gambar tidak boleh lebih dari 5MB'
        ]);

        $user = $request->user();
        if ($request->hasFile('photo2210003')) {
            $file = $request->file('photo2210003');
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $fileNameThumb = 'thumb_' . time() . '.' . $file->getClientOriginalExtension();

            if ($user->photo2210003) {
                $oldPhotoPath = public_path('storage/photos/' . basename($user->photo2210003));
                if (File::exists($oldPhotoPath)) {
                    File::delete($oldPhotoPath);
                }
            }

            if ($user->photo_thumb2210003) {
                $oldThumbPath = public_path('storage/photos/thumbnail/' . basename($user->photo_thumb2210003));
                if (File::exists($oldThumbPath)) {
                    File::delete($oldThumbPath);
                }
            }

            $filePath = $file->storeAs('photos', $fileName, 'public');

            $destinationPathThumbnail =
                public_path('storage/photos/thumbnail/');
            if (!File::exists($destinationPathThumbnail)) {
                File::makeDirectory($destinationPathThumbnail, 0755, true);
            }
            $image = Image::read($file);
            $image->scaleDown(width: 200);
            $image->save($destinationPathThumbnail . $fileNameThumb);

            $user->photo2210003 = Storage::url($filePath);
            $user->photo_thumb2210003 = '/storage/photos/thumbnail/' .
                $fileNameThumb;
            $user->save();

            return response()->json([
                'message' => 'Foto berhasil diperbarui!',
                'photo2210003' => $user->photo2210003,
                'photo_thumb2210003' => $user->photo_thumb2210003,
            ], 200);
            return response()->json(['message' => 'Tidak ada foto yang diunggah.'], 400);
        }
    }

    public function getSaldoUser(Request $request)
    {
        $user = $request->user();
        $dataSaldo = SaldoUser::where('iduser_2210003', $user->id)->first();

        return response()->json([
            'data' => [
                'jumlahsaldo' => $dataSaldo ? $dataSaldo->jumlahsaldo_2210003 : 0
            ]
        ]);
    }
}
