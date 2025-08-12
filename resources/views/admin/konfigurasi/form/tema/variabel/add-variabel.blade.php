@extends('layouts.admin')

@section('title', 'Tambah Variabel - Audit App')

@section('content')
<div class="container mx-auto p-4 min-h-screen">
    <a href="{{ route('variabel-form', $temaFormId) }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>

    {{-- Error Custom --}}
    @if (session('variabel_error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('variabel_error') }}
        </div>
    @endif

    {{-- Error Laravel --}}
    @if ($errors->any())
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-xl font-bold mb-4">Tambah variabel</h1>

    <form action="{{ route('add-variabel-form', $temaFormId) }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Variabel --}}
        <div class="mb-4">
            <label for="variabel" class="block text-gray-700 font-semibold mb-2">Variabel</label>
            <input type="text" name="variabel" id="variabel"
                value="{{ old('variabel') }}"
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                required placeholder="Masukkan variabel">
            @error('variabel')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>  

        {{-- Standar --}}
        <div class="mb-4">
            <label for="standar_variabel" class="block text-gray-700 font-semibold mb-2">Standar</label>
            <input type="text" name="standar_variabel" id="standar_variabel"
                value="{{ old('standar_variabel') }}"
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                required placeholder="Masukkan standar">
            @error('standar_variabel')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>  

        {{-- Foto --}}
        <div class="mb-4">
            <label for="standar_foto" class="block text-gray-700 font-semibold mb-2">Foto (bisa pilih lebih dari satu)</label>
            <input type="file" accept="image/*" name="standar_foto[]" id="standar_foto"
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                multiple onchange="previewImages(event)">
            @error('standar_foto.*')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            
            <div id="image-preview-container" class="mt-4 grid grid-cols-3 gap-4"></div>
            <p class="text-sm text-gray-600 mt-2">*Anda dapat memilih beberapa file dengan menekan tombol Ctrl atau Command saat memilih file</p>
        </div>  

        <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg">Tambah Variabel</button>
    </form>
</div>

@push('scripts')
<script>
    function previewImages(event) {
        const input = event.target;
        const previewContainer = document.getElementById('image-preview-container');
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
                    img.alt = 'Image Preview ' + (i + 1);
                    
                    const filename = document.createElement('p');
                    filename.className = 'text-xs mt-1 text-gray-600 truncate';
                    filename.textContent = file.name;
                    
                    previewDiv.appendChild(img);
                    previewDiv.appendChild(filename);
                    previewContainer.appendChild(previewDiv);
                }
                
                reader.readAsDataURL(file);
            }
        }
    }
</script>
@endpush
@endsection
