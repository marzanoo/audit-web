@extends('layouts.admin')

@section('title', 'Ubah Variabel - Audit App')

@section('content')
<div class="container mx-auto p-4 min-h-screen">
    <a href="{{ route('variabel-form', $variabel->tema_form_id) }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>

    @if (session('variabel_error'))
    <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
        {{ session('variabel_error') }}
    </div>
    @endif
    <h1 class="text-xl font-bold mb-4">Ubah Variabel</h1>

    <form action="{{ route('edit-variabel-form', $variabel->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label for="variabel" class="block text-gray-700 font-semibold mb-2">Variabel</label>
            <input type="text" name="variabel" id="variabel" value="{{ $variabel->variabel }}" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Masukkan variabel">
        </div>  
        <div class="mb-4">
            <label for="standar_variabel" class="block text-gray-700 font-semibold mb-2">Standar</label>
            <input type="text" name="standar_variabel" id="standar_variabel" value="{{ $variabel->standar_variabel }}" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Masukkan standar">
        </div>  
        
        @if(count($variabel->standarFotos) > 0)
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Foto Standar yang ada</label>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($variabel->standarFotos as $foto)
                <div class="relative">
                    <img src="{{ asset('storage/' . $foto->image_path) }}" 
                         alt="Foto Standar" 
                         class="w-[120px] h-[120px] object-cover rounded-lg shadow-md">
                    <div class="mt-2">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="delete_photos[]" value="{{ $foto->id }}" class="form-checkbox h-5 w-5 text-red-600">
                            <span class="text-sm text-red-600">Hapus foto ini</span>
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <div class="mb-4">
            <label for="standar_foto" class="block text-gray-700 font-semibold mb-2">Tambah Foto Baru (bisa pilih lebih dari satu)</label>
            <input type="file" accept="image/*" name="standar_foto[]" id="standar_foto" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" multiple onchange="previewNewImages(event)">
            
            <div id="new-image-preview-container" class="mt-4 grid grid-cols-3 gap-4">
                <!-- Preview images will be displayed here -->
            </div>
        </div>  
        
        <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg">Simpan Perubahan</button>
    </form>
</div>
@push('scripts')   
<script>
    function previewNewImages(event) {
        const input = event.target;
        const previewContainer = document.getElementById('new-image-preview-container');
        
        // Clear previous previews
        previewContainer.innerHTML = '';
        
        if (input.files && input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                const reader = new FileReader();
                const file = input.files[i];
                
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-[120px] h-[120px] object-cover rounded-lg shadow-md';
                    img.alt = 'New Image Preview ' + (i + 1);
                    
                    previewDiv.appendChild(img);
                    previewContainer.appendChild(previewDiv);
                }
                
                reader.readAsDataURL(file);
            }
        }
    }
</script>
@endpush
@endsection