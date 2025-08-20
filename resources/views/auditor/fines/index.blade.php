@extends('layouts.auditor')
@section('title', 'Denda - Audit App')

@section('content')
<div class="container mx-auto p-4">
    <a href="{{ route('dashboard') }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>
    @if (session('payment_success'))
        <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
            {{ session('payment_success') }}
        </div>
    @endif
    @if (session('payment_error'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('payment_error') }}
        </div>
    @endif
    @if (session('payment_pending'))
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            {{ session('payment_pending') }}
        </div>
    @endif

    <h1 class="text-2xl font-bold mb-4">Daftar Denda dan Pembayaran</h1>
    <div class="bg-gray-100 p-4 rounded-lg shadow-md mb-4">
        <p class="text-lg font-semibold">Nama: {{ $karyawan->emp_name }}</p>
        <p class="text-lg font-semibold">Total Denda: Rp {{ number_format($totalFines, 0, ',', '.') }}</p>
        <p class="text-lg font-semibold">Total Dibayar: Rp {{ number_format($totalPayments, 0, ',', '.') }}</p>
        <p class="text-lg font-semibold text-red-600">Sisa Denda: Rp {{ number_format($totalDue, 0, ',', '.') }}</p>
    </div>

    <h2 class="text-xl font-bold mb-2">Riwayat Transaksi</h2>
    <div class="space-y-4">
        @forelse ($fines as $fine)
            <div class="bg-white p-4 rounded-lg shadow-md">
                <div class="flex justify-between items-center">
                    <span class="font-semibold {{ $fine->type == 'fine' ? 'text-red-600' : 'text-green-600' }}">
                        {{ $fine->type == 'fine' ? 'Denda' : 'Pembayaran' }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $fine->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <p class="text-lg font-semibold">Rp {{ number_format($fine->amount, 0, ',', '.') }}</p>
                <p class="text-sm text-gray-600">{{ $fine->description }}</p>
                @if ($fine->evidence_path)
                    <a href="{{ asset('storage/' . $fine->evidence_path) }}" target="_blank" class="text-blue-500 text-sm hover:underline">Lihat Bukti</a>
                @else
                    <span class="text-sm text-gray-500">-</span>
                @endif
            </div>
        @empty
            <p class="text-gray-500 text-center">Belum ada transaksi.</p>
        @endforelse
    </div>

    @if ($totalDue > 0)
        <div class="mt-6 bg-gray-100 p-4 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-2">Bayar Denda</h2>
            <form action="{{ route('fines-pay', $karyawan->emp_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700">Jumlah Pembayaran (Rp)</label>
                    <input type="number" name="amount" id="amount" class="mt-1 p-2 w-full border rounded-lg focus:ring focus:ring-blue-200" required min="1" max="{{ $totalDue }}" placeholder="Masukkan jumlah">
                </div>
                <div class="mb-4">
                    <label for="evidence" class="block text-sm font-medium text-gray-700">Upload Bukti Pembayaran</label>
                    <input type="file" name="evidence" id="evidence" class="mt-1 p-2 w-full border rounded-lg" accept="image/*" required>
                </div>
                <button type="submit" class="w-full bg-red-500 text-white p-2 rounded-lg hover:bg-red-600">Submit Pembayaran</button>
            </form>
        </div>
    @endif

</div>
@endsection