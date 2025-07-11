<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\PicArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PicAreaController extends Controller
{
    public function index()
    {
        $picArea = PicArea::with('karyawan:emp_id,emp_name,dept')->get();
        return view('admin.konfigurasi.pic-area.index', compact('picArea'));
    }

    public function addPicArea()
    {
        $picArea = Karyawan::where('remarks', 'LIKE', '%AUDITEE%')->get();
        return view('admin.konfigurasi.pic-area.add-pic-area', compact('picArea'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pic_id' => 'required',
        ]);

        $picArea = PicArea::where('pic_id', $request->pic_id)->first();
        if ($picArea) {
            return back()->with(['pic_area_error' => 'PIC area sudah ada']);
        }

        $picArea = PicArea::create([
            'pic_id' => $request->pic_id,
        ]);

        return redirect()->route('pic-area')->with('pic_area_success', 'PIC area berhasil ditambahkan');
    }

    public function rollingPic()
    {
        try {
            Artisan::call('rolling:pic');
            return redirect()->back()->with('pic_area_success', 'PIC area berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with(['pic_area_error' => $e->getMessage()]);
        }
    }

    public function editPicArea($id)
    {
        $picArea = PicArea::with('karyawan:emp_id,emp_name,dept')->find($id);
        if (!$picArea) {
            return back()->with(['pic_area_error' => 'PIC area tidak ditemukan']);
        }

        $karyawan = Karyawan::select('emp_id', 'emp_name', 'dept')->where('remarks', 'LIKE', '%AUDITEE%')->orderBy('dept', 'ASC')->get();
        return view('admin.konfigurasi.pic-area.edit-pic-area', compact('picArea', 'karyawan'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'pic_id' => 'required',
        ]);

        $picArea = PicArea::find($id);
        if (!$picArea) {
            return back()->with(['pic_area_error' => 'PIC area tidak ditemukan']);
        }
        $picArea->pic_id = $request->pic_id;
        $picArea->save();

        return redirect()->route('pic-area')->with('pic_area_success', 'PIC area berhasil diupdate');
    }

    public function destroy($id)
    {
        $picArea = PicArea::find($id);
        if (!$picArea) {
            return back()->with(['pic_area_error' => 'PIC area tidak ditemukan']);
        }
        $picArea->delete();
        return back()->with(['pic_area_success' => 'PIC area berhasil dihapus']);
    }
}
