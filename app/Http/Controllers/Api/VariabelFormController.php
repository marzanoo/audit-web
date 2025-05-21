<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailFotoStandarVariabel;
use App\Models\VariabelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VariabelFormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $variabel = VariabelForm::with('temaForm:id,tema', 'detailFotoStandarVariabels')->where('tema_form_id', $id)->get();
        $variabel->each(function ($item) {
            $item->foto_standar_urls = $item->detailFotoStandarVariabels->map(function ($photo) {
                return asset('storage/' . $photo->image_path);
            });
        });
        return response()->json([
            'message' => 'Variabel berhasil diambil',
            'data' => $variabel
        ], 200);
    }

    public function getTotalVariabel()
    {
        $variabel = VariabelForm::count();
        return response()->json([
            'message' => 'Total variabel berhasil diambil',
            'total' => $variabel
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tema_form_id' => 'required',
            'variabel' => 'required',
            'standar_variabel' => 'required',
            'standar_foto.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        if (VariabelForm::where('tema_form_id', $request->tema_form_id)
            ->where('variabel', $request->variabel)
            ->exists()
        ) {
            return response()->json([
                'message' => 'Variabel sudah ada',
            ]);
        }

        // Create the variabel without photos first
        $variabel = VariabelForm::create([
            'tema_form_id' => $request->tema_form_id,
            'variabel' => $request->variabel,
            'standar_variabel' => $request->standar_variabel,
            'standar_foto' => null, // This field will eventually be removed or kept for backward compatibility
        ]);

        $photoUrls = [];

        // Handle multiple photos
        if ($request->hasFile('standar_foto')) {
            $files = $request->file('standar_foto');

            // Handle case where standar_foto is an array or single file
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                $path = $file->store('standards', 'public');

                // Create detail photo record
                $detailPhoto = DetailFotoStandarVariabel::create([
                    'variabel_form_id' => $variabel->id,
                    'image_path' => $path,
                ]);

                $photoUrls[] = asset('storage/' . $path);
            }
        }

        return response()->json([
            'message' => 'Variabel berhasil ditambahkan',
            'data' => $variabel,
            'photo_urls' => $photoUrls
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $variabel = VariabelForm::with('detailFotoStandarVariabels')->find($id);

        if (!$variabel) {
            return response()->json([
                'message' => 'Variabel tidak ditemukan',
            ], 404);
        }

        $photoUrls = $variabel->detailFotoStandarVariabels->map(function ($photo) {
            return asset('storage/' . $photo->image_path);
        });

        return response()->json([
            'message' => 'Variabel berhasil diambil',
            'data' => $variabel,
            'photo_urls' => $photoUrls
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     $request->validate([
    //         'tema_form_id' => 'required',
    //         'variabel' => 'required',
    //         'standar_variabel' => 'required',
    //         'standar_foto' => $request->hasFile('standar_foto') ? 'required|image|mimes:jpeg,png,jpg,gif,svg' : '',
    //     ]);

    //     dd(request()->all());

    //     if (VariabelForm::where('tema_form_id', $request->tema_form_id)->where('variabel', $request->variabel)->where('id', '!=', $id)->exists()) {
    //         return response()->json([
    //             'message' => 'Variabel sudah ada',
    //             'data' => null,
    //             'photo_url' => null
    //         ]);
    //     }

    //     $variabel = VariabelForm::find($id);
    //     if (!$variabel) {
    //         return response()->json([
    //             'message' => 'Variabel tidak ditemukan',
    //             'data' => null,
    //             'photo_url' => null
    //         ], 404);
    //     }

    //     $variabel->tema_form_id = $request->tema_form_id;
    //     $variabel->variabel = $request->variabel;
    //     $variabel->standar_variabel = $request->standar_variabel;

    //     // Only update photo if a new one is uploaded
    //     if ($request->hasFile('standar_foto')) {
    //         $path = $request->file('standar_foto')->store('standards', 'public');
    //         $variabel->standar_foto = $path;
    //     }

    //     $variabel->save();

    //     return response()->json([
    //         'message' => 'Variabel berhasil diubah',
    //         'data' => $variabel,
    //         'photo_url' => $variabel->standar_foto ? asset('storage/' . $variabel->standar_foto) : null
    //     ], 200);
    // }
    public function update(Request $request, $id)
    {
        // Gunakan '_method' agar PUT bisa diterima saat dikirim sebagai POST
        if ($request->has('_method') && $request->_method === 'PUT') {
            $request->merge(['_method' => 'PUT']);
        }

        // Validasi input
        $request->validate([
            'tema_form_id' => 'required|integer',
            'variabel' => 'required|string',
            'standar_variabel' => 'required|string',
            'standar_foto.*' => 'nullable|image|mimes:jpg,jpeg,png',
            'delete_photos' => 'nullable|array',
            'delete_photos.*' => 'nullable|integer',
        ]);

        // Cari data berdasarkan ID
        $variabel = VariabelForm::findOrFail($id);

        // Update data dasar
        $variabel->tema_form_id = $request->tema_form_id;
        $variabel->variabel = $request->variabel;
        $variabel->standar_variabel = $request->standar_variabel;

        // Hapus foto yang diminta untuk dihapus
        if ($request->has('delete_photos') && is_array($request->delete_photos)) {
            foreach ($request->delete_photos as $photoId) {
                $photo = DetailFotoStandarVariabel::find($photoId);
                if ($photo && $photo->variabel_form_id == $id) {
                    Storage::disk('public')->delete($photo->image_path);
                    $photo->delete();
                }
            }
        }

        // Tambah foto baru jika ada
        if ($request->hasFile('standar_foto')) {
            $files = $request->file('standar_foto');

            // Handle case where standar_foto is an array or single file
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                $path = $file->store('standards', 'public');

                // Create detail photo record
                DetailFotoStandarVariabel::create([
                    'variabel_form_id' => $variabel->id,
                    'image_path' => $path,
                ]);
            }
        }

        // Simpan perubahan untuk data dasar
        $variabel->save();

        // Get all photos for response
        $photoUrls = $variabel->detailFotoStandarVariabels()->get()->map(function ($photo) {
            return [
                'id' => $photo->id,
                'url' => asset('storage/' . $photo->image_path)
            ];
        });

        return response()->json([
            'message' => 'Data berhasil diperbarui!',
            'data' => $variabel,
            'photo_urls' => $photoUrls
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $variabel = VariabelForm::with('detailFotoStandarVariabels')->find($id);

        if (!$variabel) {
            return response()->json([
                'message' => 'Variabel tidak ditemukan',
            ], 404);
        }

        // Delete all associated photos
        foreach ($variabel->detailFotoStandarVariabels as $photo) {
            Storage::disk('public')->delete($photo->image_path);
            $photo->delete();
        }

        // Delete the old single photo if it exists (legacy data)
        if ($variabel->standar_foto) {
            Storage::disk('public')->delete($variabel->standar_foto);
        }

        $variabel->delete();

        return response()->json([
            'message' => 'Variabel berhasil dihapus',
        ]);
    }
}
