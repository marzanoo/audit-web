<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeFine;
use App\Models\Karyawan;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{

    public function index()
    {
        $karyawan = Karyawan::all();
        return response()->json([
            'message' => 'Karyawan berhasil diambil',
            'data' => $karyawan
        ]);
    }

    public function getKaryawanPic()
    {
        $karyawan = Karyawan::where('remarks', 'LIKE', '%AUDITEE%')->get();
        return response()->json([
            'message' => 'Karyawan berhasil diambil',
            'data' => $karyawan
        ]);
    }

    public function getKaryawan($nik)
    {
        $karyawan = Karyawan::where('emp_id', $nik)->first();

        if (!$karyawan) {
            return response()->json([
                'message' => 'NIK tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'name' => $karyawan->emp_name,
        ]);
    }

    public function search(Request $request)
    {
        $keyword = $request->get('q');

        $karyawan = Karyawan::where('emp_name', 'LIKE', "%$keyword%")
            ->limit(10)
            ->get(['emp_id', 'emp_name']);

        // satukan data dengan total_due langsung biar simpel
        $result = $karyawan->map(function ($item) {
            return [
                'emp_id'    => $item->emp_id,
                'emp_name'  => $item->emp_name,
                'total_due' => EmployeeFine::getTotalDue($item->emp_id)
            ];
        });

        return response()->json($result, 200);
    }
}
