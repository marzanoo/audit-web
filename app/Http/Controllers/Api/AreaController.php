<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::with('lantai:id,lantai')->get();
        return response()->json([
            'message' => 'Area berhasil diambil',
            'data' => $areas
        ]);
    }

    public function getTotalArea()
    {
        $total = Area::count();
        return response()->json([
            'message' => 'Total area berhasil diambil',
            'total' => $total
        ]);
    }

    public function show($id)
    {
        $area = Area::with('lantai:id,lantai')->find($id);
        return response()->json([
            'message' => 'Area berhasil diambil',
            'data' => $area
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            // 'lantai_id' => 'required',
            'area' => 'required',
            // 'pic_area' => 'required',
        ]);

        $areas = Area::where('area', $request->area)->first();
        if ($areas) {
            return response()->json([
                'message' => "Area '$request->area' sudah terdaftar"
            ]);
        }

        // $pic_area = Area::where('pic_area', $request->pic_area)->first();
        // if ($pic_area) {
        //     return response()->json([
        //         'message' => 'PIC area sudah ada di area lain'
        //     ]);
        // }

        // $existingArea = Area::where('area', $request->area)->first();
        // if ($existingArea) {
        //     return response()->json([
        //         'message' => "Area '$request->area' sudah terdaftar di lantai lain atau lantai yang sama"
        //     ]);
        // }

        $area = Area::create([
            'lantai_id' => 4,
            'area' => $request->area,
            // 'pic_area' => $request->pic_area,
        ]);

        return response()->json([
            'message' => 'Area berhasil ditambahkan',
            'data' => $area
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            // 'lantai_id' => 'required',
            'area' => 'required',
            // 'pic_area' => 'required',
        ]);
        // $pic = Area::where('pic_area', $request->pic_area)->where('id', '!=', $id)->first();
        // if ($pic) {
        //     return response()->json([
        //         'message' => 'PIC area sudah ada di area lain'
        //     ]);
        // }
        $areas = Area::where('area', $request->area)->where('id', '!=', $id)->first();
        if ($areas) {
            return response()->json([
                'message' => "Area '$request->area' sudah terdaftar di lantai lain atau lantai yang sama"
            ]);
        }
        $area = Area::find($id);
        // $area->lantai_id = $request->lantai_id;
        $area->area = $request->area;
        // $area->pic_area = $request->pic_area;
        $area->save();

        return response()->json([
            'message' => 'Area berhasil diupdate',
            'data' => $area
        ]);
    }

    public function destroy($id)
    {
        $area = Area::find($id);
        $area->delete();
        return response()->json([
            'message' => 'Area berhasil dihapus'
        ]);
    }
}
