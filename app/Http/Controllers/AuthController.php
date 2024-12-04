<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;    

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validasi data permintaan
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',  // Validasi username
            'email' => 'required|string|email|unique:users,email',  // Validasi email
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:Siswa,pegawai', // Validasi role
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        // Membuat pengguna baru
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,  // Menambahkan username
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role, // Menggunakan role dari permintaan
        ]);
    
        // Mengembalikan respons sukses dengan data pengguna
        return response()->json([
            'status' => 'success',
            'message' => 'Pengguna berhasil terdaftar',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ], 201);
    }


public function login(Request $request)
{
    // Validasi data permintaan
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    // Respons jika validasi gagal
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validasi gagal.',
        ], 422);
    }

    // Cek kredensial login
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'status' => 'error',
            'message' => 'Email atau password salah.',
        ], 401);
    }

    // Ambil user yang berhasil login
    $user = Auth::user();

    // Buat token autentikasi
    $token = $user->createToken('MyApp')->plainTextToken;

    // Respons jika login berhasil
    return response()->json([
        'status' => 'success',
        'message' => 'Berhasil login.',
        'token' => $token,
    ], 200);
}
    
public function logout(Request $request)
{
    // Mengambil pengguna yang sedang login
    $user = Auth::user();

    // Mengambil token saat ini
    $currentToken = $request->user()->currentAccessToken();

    // Menghapus token yang sedang digunakan
    if ($currentToken) {
        $currentToken->delete(); // Menghapus token yang sedang digunakan
    }

    return response()->json(['message' => 'Logout successful.']);
}


}