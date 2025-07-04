@extends('layouts.admin')
@section('title', 'Konfigurasi Variabel - Audit App')

@section('content')
<div class="container mx-auto p-4 min-h-screen">
    {{-- Tombol Kembali --}}
    <a href="{{ route('tema-form', $temaFormId ?? '') }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>

    {{-- Notifikasi --}}
    @if (session('variabel_error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('variabel_error') }}
        </div>
    @endif

    @if (session('variabel_success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
            {{ session('variabel_success') }}
        </div>
    @endif

    {{-- Judul Halaman --}}
    <h1 class="text-xl font-bold mb-4">Konfigurasi Variabel</h1>

    {{-- Tombol Tambah Variabel --}}
    <a href="{{ route('add-variabel-form', $temaFormId) }}" class="bg-black text-white px-4 py-2 rounded-lg">+ Tambah Variabel</a>

    {{-- List Variabel --}}
    <div class="mt-4 space-y-4">
        @foreach ($variabel as $item)
        <div class="bg-gray-100 p-4 rounded-lg shadow-lg">
            {{-- Data Variabel --}}
            <div class="flex justify-between mb-2">
                <span class="text-lg font-semibold w-1/3">Variabel</span>
                <span class="text-lg w-2/3">{{ $item->variabel }}</span>
            </div>

            <div class="flex justify-between mb-2">
                <span class="text-lg font-semibold w-1/3">Standar</span>
                <span class="text-lg w-2/3">{{ $item->standar_variabel }}</span>
            </div>

            <div class="mb-2">
                <span class="text-lg font-semibold block mb-2">Foto Standar:</span>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @if(count($item->standarFotos) > 0)
                        @foreach($item->standarFotos as $foto)
                            <img src="{{ asset('storage/' . $foto->image_path) }}" 
                                alt="Foto Standar" 
                                class="w-24 h-24 object-cover rounded-lg shadow-md">
                        @endforeach
                    @else
                        <span class="text-gray-500">Tidak ada foto</span>
                    @endif
                </div>
            </div>

            {{-- Tombol Ubah & Hapus --}}
            <div class="flex gap-2 mt-3">
                {{-- Tombol Ubah --}}
                <a href="{{ route('edit-variabel-form', $item->id) }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-center">
                    Ubah
                </a>

                {{-- Tombol Hapus --}}
                <form action="{{ route('delete-variabel-form', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg w-full">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection