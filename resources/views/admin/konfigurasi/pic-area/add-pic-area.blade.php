@extends('layouts.admin')

@section('title', 'Tambah PIC Area - Audit App')

@section('content')
<div class="container mx-auto p-4 min-h-screen">
    <a href="{{ route('pic-area') }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>

    @if (session('pic_area_error'))
    <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
        {{ session('pic_area_error') }}
    </div>
    @endif
    <h1 class="text-xl font-bold mb-4">Tambah PIC Area</h1>

    <form action="{{ route('add-pic-area') }}" method="POST" class="max-w-lg mx-auto">
    @csrf
    <div class="mb-4">
        <label for="pic_id" class="block text-gray-700 font-semibold mb-2">PIC Area</label>
        <div class="relative">
            <select name="pic_id" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none" required>
                <option value="" hidden selected disabled>Pilih PIC Area</option>
                @foreach ($picArea as $item)
                <option value="{{ $item->emp_id }}">{{ $item->emp_name }}</option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <i class="fas fa-chevron-down text-gray-500"></i>
            </div>
        </div>
    </div>

    <button type="submit" class="w-full bg-black text-white py-3 rounded-lg font-semibold hover:bg-gray-700 transition duration-200">
        Tambah PIC Area
    </button>
</form>

</div>
@endsection