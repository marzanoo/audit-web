<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\PicArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PicAreaController extends Controller
{
    public function index()
    {
        $picArea = PicArea::with('karyawan:emp_id,emp_name,dept')->get();
        return response()->json([
            'message' => 'PIC area berhasil diambil',
            'data' => $picArea
        ], 200);
    }

    //Buat ambil calon PIC
    public function candidatePic()
    {
        $karyawan = Karyawan::select('emp_id', 'emp_name', 'dept')->where('remarks', 'LIKE', '%AUDITEE%')->orderBy('dept', 'ASC')->get();
        return response()->json([
            'message' => 'PIC area berhasil diambil',
            'data' => $karyawan
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pic_id' => 'required',
        ]);

        $picArea = PicArea::where('pic_id', $request->pic_id)->first();
        if ($picArea) {
            return response()->json([
                'message' => 'PIC area sudah ada',
            ], 400);
        }

        $picArea = PicArea::create([
            'pic_id' => $request->pic_id,
        ]);

        return response()->json([
            'message' => 'PIC area berhasil ditambahkan',
        ], 201);
    }

    public function destroy($id)
    {
        $picArea = PicArea::find($id);
        if (!$picArea) {
            return response()->json([
                'message' => 'PIC area tidak ditemukan',
            ], 404);
        }
        $picArea->delete();
        return response()->json([
            'message' => 'PIC area berhasil dihapus',
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pic_id' => 'required',
        ]);

        $picArea = PicArea::find($id);
        if (!$picArea) {
            return response()->json([
                'message' => 'PIC area tidak ditemukan',
            ], 404);
        }
        $picArea->pic_id = $request->pic_id;
        $picArea->save();

        return response()->json([
            'message' => 'PIC area berhasil diupdate',
        ], 200);
    }

    public function rollingPic()
    {
        try {
            Artisan::call('rolling:pic');
            return response()->json([
                'message' => 'PIC area berhasil diupdate',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
