@extends('layouts.steerco')
@section('title', 'Audit Office - Audit App')

@section('content')
<div class="container mx-auto p-4">
    <a href="{{ route('audit-office-steerco') }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>
    @if (session('audit_office_error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('audit_office_error') }}
        </div>
    @endif
    @if (session('audit_office_success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
            {{ session('audit_office_success') }}
        </div>
    @endif
    <h1 class="text-xl font-bold mb-4">Audit Office - Pilih Form</h1>
    <div class="mt-4 space-y-4">
        @foreach ($audit_form as $item)
        <div class="bg-gray-100 p-4 rounded-lg shadow-lg">
            <div class="flex justify-between">
                <span class="text-lg font-semibold w-1/3">Tanggal</span>
                <span class="text-lg w-2/3">{{ $item->tanggal }}</span>
            </div>
        
            {{-- Data Auditor --}}
            <div class="flex justify-between">
                <span class="text-lg font-semibold w-1/3">Auditor</span>
                <span class="text-lg w-2/3">{{ $item->auditor->name }}</span>
            </div>

            {{-- Data Status --}}
            @php
                $statusClass = match($item->status) {
                    'pending' => 'text-red-600 font-bold',
                    'approved' => 'text-green-600 font-bold',
                    default => 'text-gray-500'
                };
            @endphp
            <div class="flex justify-between">
                <span class="text-lg font-semibold w-1/3">Status</span>
                <span class="text-lg w-2/3 uppercase {{ $statusClass }}">
                    {{ $item->status }}
                </span>
            </div>

            {{-- Lihat--}}
            <div class="flex gap-2 mt-3 justify-end">
                {{-- Tombol Lihat --}}
                <a href="{{ route('detail-audit-office-steerco-audit-form', $item->id) }}" class="bg-red-900 text-white px-4 py-2 rounded-lg text-center">
                    Lihat
                </a>
                <form action="{{ route('approve-audit-office-steerco', $item->id) }}" method="POST" onsubmit="return confirm('Apakah anda yakin untuk approve audit?')">
                    @csrf
                    @method('PUT')
                    
                    <button 
                        type="submit" 
                        class="bg-green-500 text-white px-4 py-2 rounded-lg w-full {{ $item->status == 'approved' ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ $item->status == 'approved' ? 'disabled' : '' }}>
                        Approve
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection