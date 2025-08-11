@extends('layouts.admin')
@section('title', 'Preview Excel - Audit App')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('detail-audit-office-admin-audit-form', $id) }}" class="text-lg font-semibold flex items-center w-fit">
            ← Kembali
        </a>
        <div class="flex space-x-3">
           <a href="{{ route('audit-office-admin-download-excel', ['id' => $id, 'email' => request('email')]) }}"
            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download Excel
            </a>
        </div>
    </div>
    
    @include('admin.audit-office.detail.preview-excel-content')
</div>

@include('components.image-modal')
@endsection