@extends('layouts.admin')
@section('title', 'Audit Office - Audit App')

@section('content')
<div class="container mx-auto p-4">
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
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('audit-office-admin-audit-form', $auditAnswer->area_id) }}" class="text-lg font-semibold flex items-center w-fit">
            ← Kembali
        </a>
        <a href="{{ route('audit-office-admin-preview-excel', $auditAnswer->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Preview Export Excel
        </a>
    </div>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Audit Office</h2>
            <h5 class="text-l mt-2 font-semibold text-gray-800">PIC Area: {{ $auditAnswer->pic_name ?? '-' }}</h5>
        </div>
        <div class="p-6 space-y-8">
            @foreach ($formattedData as $detail)
                <div class="audit-detail-section bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="mb-4">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            {{ $detail['kategori'] }}
                        </h3>
                        <h4 class="text-lg font-medium text-gray-600 mb-2">
                            {{ $detail['tema'] }}
                        </h4>
                        <p class="text-gray-600 mb-2">
                            <strong>Standar:</strong> {{ $detail['standar_variabel'] }}
                        </p>
                        @if ($detail['standar_foto'])
                            <div class="mt-3 mb-4">
                                <h5 class="text-md font-medium text-gray-700 mb-2">Foto Standar:</h5>
                                <img src="{{ asset('storage/' . $detail['standar_foto']) }}" 
                                     alt="Foto Standar" 
                                     class="w-40 h-40 object-cover rounded-lg shadow-sm cursor-pointer hover:opacity-80 transition-opacity"
                                     onclick="openModal('{{ asset('storage/' . $detail['standar_foto']) }}', 'Foto Standar')">
                            </div>
                        @endif
                        <p class="text-gray-600 mb-2">
                            {{ $detail['variabel'] }}
                        </p>
                    </div>
                    
                    <!-- Temuan Foto -->
                    @if (count($detail['images']) > 0)
                        <div class="mt-6 mb-4">
                            <h5 class="text-md font-medium text-gray-700 mb-2">Foto Temuan:</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach ($detail['images'] as $index => $image)
                                    <div class="relative">
                                        <img src="{{ asset('storage/' . $image['image_path']) }}" 
                                             alt="Foto Temuan {{ $index + 1 }}" 
                                             class="w-full h-32 object-cover rounded-lg shadow-sm cursor-pointer hover:opacity-80 transition-opacity hover:scale-105 transform"
                                             onclick="openModal('{{ asset('storage/' . $image['image_path']) }}', 'Foto Temuan {{ $index + 1 }}')">
                                        <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                                            {{ $index + 1 }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Tertuduh & Temuan -->
                    @if (count($detail['auditees']) > 0)
                        <div class="mt-6 mb-4">
                            <h5 class="text-md font-medium text-gray-700 mb-2">Data Tertuduh & Temuan:</h5>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <ul class="space-y-2">
                                    @foreach ($detail['auditees'] as $auditee)
                                        <li class="flex justify-between items-center p-2 bg-white rounded-lg shadow-sm">
                                            <span>{{ $auditee['auditee'] }}</span>
                                            <span class="font-medium">Temuan: {{ $auditee['temuan'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Score -->
                    <div class="mt-6">
                        <h5 class="text-md font-medium text-gray-700 mb-2">Score:</h5>
                        <div class="bg-gray-100 p-3 rounded-lg inline-block">
                            <span class="font-bold text-lg">{{ $detail['score'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-6">
                <div class="flex items-center">
                    <h5 class="text-md font-medium text-gray-700">Total Score:</h5>
                    <span class="font-bold text-md mx-2">{{ $auditAnswer->total_score }}</span>
                </div>
            
                <div class="flex items-center mt-2">
                    <h5 class="text-md font-medium text-gray-700">Grade:</h5>
                    <span class="font-bold text-md mx-2">{{ $grade }}</span>
                </div>
            </div>
            @if ($grade != 'Diamond')
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mt-8">
                <h3 class="text-xl font-semibold mb-4">Charge Fees ({{ $grade }})</h3>
        
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Tarif Denda: Rp {{ number_format($chargeFees['feeRate'], 0, ',', '.') }} per temuan</h4>
                    <p class="text-gray-600">Total Temuan: {{ $chargeFees['totalFindings'] }} | Total Denda: Rp {{ number_format($chargeFees['totalFee'], 0, ',', '.') }}</p>
                </div>

                {{-- Tertuduh Fees --}}
                @if (count($chargeFees['tertuduhDetails']) > 0)
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Denda Tertuduh:</h4>
                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Temuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Denda</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($chargeFees['tertuduhDetails'] as $name => $detail)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detail['dept'] ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detail['findings'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp {{ number_format($detail['fee'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- PIC Area Fee --}}
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Denda PIC Area (50%): {{ $auditAnswer->pic_name ?? 'Tidak Ada' }}</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-800">Total Temuan: {{ $chargeFees['totalFindings'] }} | Denda: Rp {{ number_format($chargeFees['picAreaFee'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <!-- Manager Fees -->
                @if (count($chargeFees['managerDetails']) > 0)
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Denda Manager (Rp 1.000/temuan):</h4>
                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Temuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Denda</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($chargeFees['managerDetails'] as $name => $detail)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detail['dept'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detail['findings'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp {{ number_format($detail['fee'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- GM Fees -->
                @if (count($chargeFees['gmDetails']) > 0)
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-2">Denda General Manager (Rp 2.000/temuan):</h4>
                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Temuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Denda</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($chargeFees['gmDetails'] as $name => $detail)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detail['dept'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $detail['findings'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Rp {{ number_format($detail['fee'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Summary -->
                <div class="bg-gray-100 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-gray-800 mb-2">Total Denda:</h4>
                    <ul class="space-y-2">
                        <li class="flex justify-between">
                            <span>Tertuduh:</span>
                            <span>Rp {{ number_format(array_sum($chargeFees['tertuduhFees']), 0, ',', '.') }}</span>
                        </li>
                        <li class="flex justify-between">
                            <span>PIC Area:</span>
                            <span>Rp {{ number_format($chargeFees['picAreaFee'], 0, ',', '.') }}</span>
                        </li>
                        <li class="flex justify-between">
                            <span>Manager:</span>
                            <span>Rp {{ number_format(array_sum($chargeFees['managerFees']), 0, ',', '.') }}</span>
                        </li>
                        <li class="flex justify-between">
                            <span>General Manager:</span>
                            <span>Rp {{ number_format(array_sum($chargeFees['gmFees']), 0, ',', '.') }}</span>
                        </li>
                        <li class="flex justify-between font-bold border-t pt-2 mt-2">
                            <span>TOTAL:</span>
                            <span>Rp {{ number_format($chargeFees['totalFee'], 0, ',', '.') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            @else
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mt-8">
                <h3 class="text-xl font-semibold mb-4">Charge Fees</h3>
                <p class="text-gray-600">Grade Diamond tidak dikenakan denda.</p>
            </div>
            @endif        
            <!-- Signatures Section -->
            @if (isset($signatures))
                <div class="bg-gray-50 rounded-xl p-6 shadow-md border border-gray-100 mt-8">
                    <h3 class="text-xl font-semibold mb-6 text-center">Tanda Tangan</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <h4 class="font-medium mb-3">Tanda Tangan Auditor</h4>
                            @if ($signatures->auditor_signature)
                                <img src="{{ asset('storage/' . $signatures->auditor_signature) }}" 
                                     alt="Tanda Tangan Auditor" 
                                     class="border-2 border-gray-300 rounded-lg mx-auto cursor-pointer hover:opacity-80 transition-opacity" 
                                     width="300"
                                     onclick="openModal('{{ asset('storage/' . $signatures->auditor_signature) }}', 'Tanda Tangan Auditor')">
                            @else
                                <div class="border-2 border-gray-300 rounded-lg mx-auto h-36 flex items-center justify-center text-gray-500">
                                    Tidak ada tanda tangan
                                </div>
                            @endif
                        </div>
                        <div class="text-center">
                            <h4 class="font-medium mb-3">Tanda Tangan Auditee</h4>
                            @if ($signatures->auditee_signature)
                                <img src="{{ asset('storage/' . $signatures->auditee_signature) }}" 
                                     alt="Tanda Tangan Auditee" 
                                     class="border-2 border-gray-300 rounded-lg mx-auto cursor-pointer hover:opacity-80 transition-opacity" 
                                     width="300"
                                     onclick="openModal('{{ asset('storage/' . $signatures->auditee_signature) }}', 'Tanda Tangan Auditee')">
                            @else
                                <div class="border-2 border-gray-300 rounded-lg mx-auto h-36 flex items-center justify-center text-gray-500">
                                    Tidak ada tanda tangan
                                </div>
                            @endif
                        </div>
                        <div class="text-center">
                            <h4 class="font-medium mb-3">Tanda Tangan Fasilitator</h4>
                            @if ($signatures->facilitator_signature)
                                <img src="{{ asset('storage/' . $signatures->facilitator_signature) }}" 
                                     alt="Tanda Tangan Fasilitator" 
                                     class="border-2 border-gray-300 rounded-lg mx-auto cursor-pointer hover:opacity-80 transition-opacity" 
                                     width="300"
                                     onclick="openModal('{{ asset('storage/' . $signatures->facilitator_signature) }}', 'Tanda Tangan Fasilitator')">
                            @else
                                <div class="border-2 border-gray-300 rounded-lg mx-auto h-36 flex items-center justify-center text-gray-500">
                                    Tidak ada tanda tangan
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan gambar full size -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50" onclick="closeModal()">
    <div class="relative max-w-screen-lg max-h-screen-lg p-4">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-white text-4xl font-bold hover:text-gray-300 z-10">
            ×
        </button>
        <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded-lg">
        <div id="modalCaption" class="text-white text-center mt-4 text-lg font-medium"></div>
    </div>
</div>

<script>
function openModal(imageSrc, caption) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    
    modalImage.src = imageSrc;
    modalCaption.textContent = caption;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    
    // Restore body scroll
    document.body.style.overflow = 'auto';
}

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Close modal when clicking outside image
document.getElementById('imageModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeModal();
    }
});
</script>
@endsection