<?php

namespace App\Http\Controllers;

use App\Models\DetailFotoStandarVariabel;
use App\Models\VariabelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VariabelFormController extends Controller
{
    public function index($id)
    {
        $temaFormId = $id;
        $variabel = VariabelForm::with('temaForm:id,tema', 'standarFotos')->where('tema_form_id', $id)->get();
        return view('admin.konfigurasi.form.tema.variabel.index', compact('variabel', 'temaFormId'));
    }

    public function addVariabelForm($id)
    {
        $temaFormId = $id;
        return view('admin.konfigurasi.form.tema.variabel.add-variabel', compact('temaFormId'));
    }

    public function store(Request $request, $id)
    {
        $temaFormId = $id;
        $request->validate([
            'variabel' => 'required',
            'standar_variabel' => 'required',
            'standar_foto' => 'nullable|image|mimes:jpg,jpeg,png', // Pastikan format gambar benar
        ]);

        $variabel = VariabelForm::where('tema_form_id', $temaFormId)->where('variabel', $request->variabel)->exists();
        if ($variabel) {
            return redirect()->route('add-variabel-form', $temaFormId)->with(['variabel_error' => 'Variabel sudah ada']);
        }

        DB::beginTransaction();
        try {
            //simpan data variabel
            $variabel = VariabelForm::create([
                'tema_form_id' => $temaFormId,
                'variabel' => $request->variabel,
                'standar_variabel' => $request->standar_variabel,
            ]);

            if ($request->hasFile('standar_foto')) {
                foreach ($request->file('standar_foto') as $foto) {
                    $foto_name = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $foto_path = $foto->storeAs('standards', $foto_name, 'public');

                    DetailFotoStandarVariabel::create([
                        'variabel_form_id' => $variabel->id,
                        'image_path' => $foto_path
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('variabel-form', $temaFormId)->with(['variabel_success' => 'Variabel berhasil ditambahkan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('add-variabel-form', $temaFormId)->with(['variabel_error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function editVariabelForm($id)
    {
        $variabel = VariabelForm::with('standarFotos')->find($id);
        return view('admin.konfigurasi.form.tema.variabel.edit-variabel', compact('variabel'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'variabel' => 'required',
            'standar_variabel' => 'required',
            'standar_foto.*' => 'nullable|image|mimes:jpg,jpeg,png', // Pastikan format gambar benar
            'delete_photos' => 'nullable',
        ]);

        $variabel = VariabelForm::find($id);
        if (!$variabel) {
            return redirect()->route('variabel-form', $variabel->tema_form_id)->with([
                'variabel_error' => 'Variabel tidak ditemukan'
            ]);
        }

        DB::beginTransaction();
        try {
            $variabel->variabel = $request->variabel;
            $variabel->standar_variabel = $request->standar_variabel;
            $variabel->save();

            //hapus foto yang dicentang untuk hapus
            if ($request->has('delete_photos')) {
                foreach ($request->delete_photos as $photoId) {
                    $foto = DetailFotoStandarVariabel::find($photoId);
                    if ($foto) {
                        Storage::disk('public')->delete($foto->image_path);
                        $foto->delete();
                    }
                }
            }

            if ($request->hasFile('standar_foto')) {
                foreach ($request->file('standar_foto') as $foto) {
                    $foto_name = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $foto_path = $foto->storeAs('standards', $foto_name, 'public');

                    DetailFotoStandarVariabel::create([
                        'variabel_form_id' => $variabel->id,
                        'image_path' => $foto_path
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('variabel-form', $variabel->tema_form_id)->with(['variabel_success' => 'Variabel berhasil diupdate']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('edit-variabel-form', $id)->with(['variabel_error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $variabel = VariabelForm::with('standarFotos')->find($id);
        if (!$variabel) {
            return redirect()->back()->with([
                'variabel_error' => 'Variabel tidak ditemukan'
            ]);
        }

        DB::beginTransaction();
        try {
            // Hapus semua file foto terkait
            foreach ($variabel->standarFotos as $foto) {
                Storage::disk('public')->delete($foto->image_path);
            }

            // Hapus variabel (cascade delete akan menghapus foto-foto di DB)
            $variabel->delete();

            DB::commit();

            return redirect()->route('variabel-form', $variabel->tema_form_id)
                ->with(['variabel_success' => 'Variabel berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with(['variabel_error' => 'Terjadi kesalahan saat menghapus: ' . $e->getMessage()]);
        }
    }
}
