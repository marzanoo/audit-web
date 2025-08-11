@extends('layouts.app')
@section('title', 'Approved Audit - Audit App')

@section('content')
<div class="container mx-auto p-4">
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Berhasil!</strong>
        <span class="block sm:inline">Audit telah disetujui.</span>
    </div>

    {{-- Copy content from preview-excel.blade.php here --}}
    @include('admin.audit-office.detail.preview-excel')
</div>

@include('components.image-modal')
@endsection
