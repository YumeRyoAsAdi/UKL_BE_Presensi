<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;  
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
{
    return User::paginate(10); // Retrieve users in chunks of 10
}


public function show($id)
{
    // Fetch the user by ID
    $user = User::findOrFail($id);

    // Return the response in the desired format
    return response()->json([
        'status' => 'success',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
        ],
    ], 200);
}



public function store(Request $request)
{
    // Validasi input
    $request->validate([
        'name' => 'required|string',
        'username' => 'required|string|unique:users,username',
        'email' => 'required|string|email|unique:users,email',
        'password' => 'required|string|min:6',
        'role' => 'required|string|in:pegawai,Siswa', // Pastikan hanya peran tertentu yang diizinkan
    ]);

    // Membuat pengguna baru
    $user = User::create([
        'name' => $request->name,
        'username' => $request->username,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
    ]);

    // Mengembalikan respons sukses dengan data pengguna
    return response()->json([
        'status' => 'success',
        'message' => 'Pengguna berhasil ditambahkan',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
        ],
    ], 201);
}



public function logout(Request $request)
{
    // Mengambil pengguna yang sedang login
    $user = Auth::user();

    // Cek apakah pengguna sudah login (terautentikasi)
    if (!$user) {
        return response()->json(['message' => 'Pengguna tidak terautentikasi'], 401);
    }

    // Menghapus token yang sedang digunakan
    $user->tokens->each(function ($token) {
        $token->delete();
    });

    return response()->json(['message' => 'Logout berhasil.']);
}
    // Metode untuk menghapus pengguna
    public function destroy($id)
    {
        // Mendapatkan pengguna yang sedang login
        $user = Auth::user();

        // Pastikan pengguna yang menghapus adalah pegawai (role 'pegawai')
        if ($user->role !== 'pegawai') {
            return response()->json(['message' => 'Hanya pegawai yang dapat menghapus pengguna.'], 403);
        }

        // Temukan pengguna yang akan dihapus
        $userToDelete = User::findOrFail($id);

        // Pastikan pengguna yang akan dihapus bukan pegawai (hanya hapus untuk role Siswa)
        if ($userToDelete->role === 'pegawai') {
            return response()->json(['message' => 'Tidak dapat menghapus pengguna dengan role pegawai.'], 403);
        }

        // Hapus pengguna
        $userToDelete->delete();

        return response()->json(['message' => 'Pengguna berhasil dihapus.'], 200);
    }
    public function update(Request $request, $id)
{
    // Mencari pengguna berdasarkan ID
    $user = User::findOrFail($id);

    // Validasi input
    $request->validate([
        'name' => 'sometimes|required|string',
        'username' => 'sometimes|required|string|unique:users,username,' . $user->id,
        'email' => 'sometimes|required|string|email|unique:users,email,' . $user->id,
        'password' => 'sometimes|required|string|min:6',
        'role' => 'sometimes|required|string|in:pegawai,siswa', // Pastikan role valid
    ]);

    // Ambil data yang diperbolehkan untuk update
    $data = $request->only('name', 'username', 'email', 'role');

    // Jika password disertakan, hash password baru
    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    // Update pengguna dengan data baru
    $user->update($data);

    // Kembalikan response dengan data yang telah diperbarui
    return response()->json([
        'status' => 'success',
        'message' => 'User updated successfully',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
        ],
    ], 200);
}

}
