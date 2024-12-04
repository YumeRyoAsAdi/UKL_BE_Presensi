<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceControl\AttendanceController;

Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/aXendance', [AttendanceController::class, 'recordAttendance']);


Route::group(['middleware' => ['auth:api', 'role:pegawai']], function () {
    // Pengelolaan Data Pengguna
    Route::post('/users', [UserController::class, 'store']); // Menambah pengguna
    Route::put('/users/{id}', [UserController::class, 'update']); // Mengubah data pengguna
    Route::get('/users/{id}', [UserController::class, 'show']); // Mengambil data pengguna
    Route::middleware('auth:sanctum')->delete('users/{id}', [UserController::class, 'destroy']);

    // Pencatatan Presensi
    Route::post('/aXendance', [AttendanceController::class, 'recordAttendance']); // Melakukan presensi
    Route::get('/aXendance/history/{user_id}', [AttendanceController::class, 'history']); // Melihat riwayat presensi pengguna
    Route::get('/aXendance/summary/{user_id}', [AttendanceController::class, 'monthlySummary']); // Melihat rekap kehadiran bulanan
    // Analisis Kehadiran
    Route::post('/aXendance/analysis', [AttendanceController::class, 'analyzeAttendance']); // Analisis tingkat kehadiran
});



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});