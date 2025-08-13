@extends('layouts.auditor')
@section('title', 'Home - Audit App')
    
@section('content')
<div class="container mx-auto p-4">
    @if (session('audit_answer_error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('audit_answer_error') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if (session('audit_answer_success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
            {{ session('audit_answer_success') }}
        </div>
    @endif

    <div class="bg-gray-100 p-4 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold">Selamat Datang!</h1>
        <p class="text-xl mb-2 mt-2">{{ auth()->user()->name }}</p>
        <p class="text-lg font-semibold">Role : 
            @if (auth()->user()->role == 3) 
                <span class="bg-cyan-500 text-white px-2 py-1 rounded">Auditor</span>
            @endif
        </p>
    </div>
</div>

<div class="container mx-auto p-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-100 p-4 rounded-lg shadow-md text-center">
            <h4 class="text-xl font-bold">Total Area</h4>
            <p class="text-4xl text-green-600 mt-3 font-semibold">{{ $total_area }}</p>
        </div>
        <a href="{{ route('audit-answer')}}" class="bg-gray-100 p-4 rounded-lg shadow-md text-center">
            <h4 class="text-xl font-bold">Isi Form Audit</h4>
            <p class="text-2xl text-green-600 mt-3 font-semibold">{{ $total_audit }} Form telah diisi</p>
        </a>
    </div>
    <div class="grid mt-4">
        <div class="bg-gray-100 p-4 rounded-lg shadow-md text-center">
            <h4 class="text-xl font-bold">Total Denda</h4>
            <p class="text-2xl text-red-600 mt-3 font-semibold">Rp {{ number_format($total_due, 0, ',', '.') }}</p>
            @if ($empId)
                <a href="{{ route('fines-show', $empId) }}" class="mt-3 inline-block bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Detail Denda
                </a>
            @else
                 <p class="text-sm text-gray-500 mt-3">Akun belum terkait dengan karyawan. Hubungi admin.</p>
            @endif
        </div>
    </div>
</div>
@endsection