<?php

namespace App\Http\Controllers\AttendanceControl;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use DateTime; // Pastikan untuk mengimpor DateTime
use Illuminate\Support\Facades\DB;
class AttendanceController extends Controller
{
    // Method untuk mencatat presensi
    public function recordAttendance(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'date' => 'required|date',
        'status' => 'required|string|in:Hadir,Alpha,Izin,Sakit',
    ]);

    // Ambil waktu saat ini
    $time = now()->format('H:i:s');

    // Mencatat presensi
    $attendance = Attendance::create([
        'user_id' => $request->user_id,
        'date' => $request->date,
        'status' => $request->status,
        'time' => $time, // Menyimpan waktu
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Presensi berhasil dicatat.',
        'data' => [
            'attendance_id' => $attendance->id, // Mengganti id dengan attendance_id
            'user_id' => $attendance->user_id,
            'date' => $attendance->date,
            'time' => $attendance->time,
            'status' => $attendance->status,
        ],
    ], 201);
}

    
    // Method untuk melihat riwayat presensi
    public function history($user_id)
    {
        // Ambil data presensi berdasarkan user_id
        $attendanceHistory = Attendance::where('user_id', $user_id)->get();
    
        // Format response sesuai dengan yang diminta
        $data = $attendanceHistory->map(function ($attendance) {
            return [
                'attendance_id' => $attendance->id,  // Mengambil ID dari attendance
                'date' => $attendance->date,  // Menampilkan tanggal presensi
                'time' => $attendance->time,  // Menampilkan waktu presensi
                'status' => $attendance->status,  // Menampilkan status presensi (Hadir, Alpha, Izin, Sakit)
            ];
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $data,  // Menampilkan data history presensi
        ]);
    }
    

    // Method untuk mendapatkan rekap kehadiran bulanan
    public function monthlySummary($user_id)
{
    try {
        // Mendapatkan bulan dan tahun saat ini
        $date = new DateTime();
        $month = $date->format('m');
        $year = $date->format('Y');

        // Mengambil rekap kehadiran bulanan berdasarkan user_id, tahun, dan bulan
        $summary = Attendance::where('user_id', $user_id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->select(DB::raw('sum(case when status = "Hadir" then 1 else 0 end) as hadir'),
                     DB::raw('sum(case when status = "Izin" then 1 else 0 end) as izin'),
                     DB::raw('sum(case when status = "Sakit" then 1 else 0 end) as sakit'),
                     DB::raw('sum(case when status = "Alpha" then 1 else 0 end) as alpa'))
            ->first();

        // Format respons
        $data = [
            'user_id' => $user_id,
            'month' => $month . '-' . $year,  // Format bulan-tahun
            'attendance_summary' => [
                'hadir' => $summary->hadir,
                'izin' => $summary->izin,
                'sakit' => $summary->sakit,
                'alpa' => $summary->alpa,
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function analyzeAttendance(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'group_by' => 'required|string|in:kelas,jabatan', // Bisa dikelompokkan berdasarkan kelas atau jabatan
    ]);

    // Ambil data kehadiran berdasarkan periode yang ditentukan
    $attendanceData = Attendance::whereBetween('date', [$request->start_date, $request->end_date])
        ->with('user') // Pastikan Anda memiliki relasi user di model Attendance
        ->get();

    // Analisis data kehadiran untuk satu grup berdasarkan group_by
    $groupedAnalysis = [];
    $totalHadir = 0;
    $totalIzin = 0;
    $totalSakit = 0;        
    $totalAlpha = 0;
    $totalUsers = 0;

    foreach ($attendanceData as $attendance) {
        $groupKey = $attendance->user->{$request->group_by}; // Ambil kelas atau jabatan sesuai group_by

        if (!isset($groupedAnalysis[$groupKey])) {
            $groupedAnalysis[$groupKey] = [
                'total_users' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'total_days' => 0,
            ];
        }

        // Hitung kehadiran berdasarkan status
        $groupedAnalysis[$groupKey]['total_users']++;
        $groupedAnalysis[$groupKey]['total_days']++;

        switch ($attendance->status) {
            case 'Hadir':
                $groupedAnalysis[$groupKey]['hadir']++;
                $totalHadir++;
                break;
            case 'Izin':
                $groupedAnalysis[$groupKey]['izin']++;
                $totalIzin++;
                break;
            case 'Sakit':
                $groupedAnalysis[$groupKey]['sakit']++;
                $totalSakit++;
                break;
            case 'Alpha':
                $groupedAnalysis[$groupKey]['alpha']++;
                $totalAlpha++;
                break;
        }
    }

    // Menghitung persentase kehadiran untuk grup yang dipilih
    $groupKey = key($groupedAnalysis); // Ambil grup pertama karena hanya ada satu grup yang dipilih
    $data = $groupedAnalysis[$groupKey];

    $attendanceRate = ($data['hadir'] / $data['total_days']) * 100;
    $hadirPercentage = ($data['hadir'] / $data['total_days']) * 100;
    $izinPercentage = ($data['izin'] / $data['total_days']) * 100;
    $sakitPercentage = ($data['sakit'] / $data['total_days']) * 100;
    $alphaPercentage = ($data['alpha'] / $data['total_days']) * 100;

    // Format respons
    $response = [
        'status' => 'success',
        'data' => [
            'analysis_period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'grouped_analysis' => [
                [
                    'group' => $groupKey,
                    'total_users' => $data['total_users'],
                    'attendance_rate' => $attendanceRate,
                    'hadir_percentage' => $hadirPercentage,
                    'izin_percentage' => $izinPercentage,
                    'sakit_percentage' => $sakitPercentage,
                    'alpha_percentage' => $alphaPercentage,
                    'total_attendance' => [
                        'hadir' => $data['hadir'],
                        'izin' => $data['izin'],
                        'sakit' => $data['sakit'],
                        'alpha' => $data['alpha'],
                    ]
                ]
            ],
            'total_attendance' => [
                'hadir' => $totalHadir,
                'izin' => $totalIzin,
                'sakit' => $totalSakit,
                'alpha' => $totalAlpha,
            ]
        ]
    ];

    return response()->json($response);
}

}