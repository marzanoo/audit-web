<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800">Preview Ekspor Excel</h2>
    </div>
    
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tema</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Standar</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Foto Standar</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Variabel</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Score</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Temuan</th>
                        <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Foto Temuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($formattedData as $detail)
                        <tr>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">{{ $detail['kategori'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">{{ $detail['tema'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">{{ $detail['standar_variabel'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">
                                @if (!empty($detail['list_standar_foto']) && count ($detail['list_standar_foto']) > 0)
                                    <div class="flex flex-col space-y-3">
                                        @foreach($detail['list_standar_foto'] as $index => $standarFoto)
                                            <div class="relative">
                                                <img src="{{ asset('storage/' . $standarFoto['image_path']) }}" 
                                                    alt="Foto Standar {{ $index + 1 }}" 
                                                    class="w-full max-w-xs object-cover rounded cursor-pointer hover:opacity-80 transition-opacity hover:scale-105 transform"
                                                    onclick="openModal('{{ asset('storage/' . $standarFoto['image_path']) }}', 'Foto Standar {{ $index + 1 }}')">
                                                <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                                                    {{ $index + 1 }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">Tidak ada foto</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">{{ $detail['variabel'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">{{ $detail['score'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">
                                @foreach ($detail['auditees'] as $auditee)
                                    <div class="mb-1">
                                        <strong>{{ $auditee['auditee'] }}:</strong> {{ $auditee['temuan'] }}
                                    </div>
                                @endforeach
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 text-sm">
                                @if (!empty($detail['images']) && count($detail['images']) > 0)
                                    <div class="flex flex-col space-y-3">
                                        @foreach ($detail['images'] as $index => $image)
                                            <div class="relative">
                                                <img src="{{ asset('storage/' . $image['image_path']) }}" 
                                                alt="Foto Temuan {{ $index + 1 }}" 
                                                class="w-full max-w-xs object-cover rounded cursor-pointer hover:opacity-80 transition-opacity hover:scale-105 transform"
                                                onclick="openModal('{{ asset('storage/' . $image['image_path']) }}', 'Foto Temuan {{ $index + 1 }}')">
                                                <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                                                    {{ $index + 1 }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">Tidak ada foto</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 border-b border-gray-200 text-sm" colspan="4"></td>
                        <td class="px-4 py-2 border-b border-gray-200 text-sm font-bold">Total Score</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-sm font-bold">{{ $auditAnswer->total_score }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-sm" colspan="2"></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 border-b border-gray-200 text-sm" colspan="4"></td>
                        <td class="px-4 py-2 border-b border-gray-200 text-sm font-bold">Grade</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-sm font-bold">{{ $grade }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-sm" colspan="2"></td>
                    </tr>
                </tbody>
            </table>
            @if ($grade != 'Diamond')
            <div class="mt-8">
                <h3 class="text-xl font-semibold mb-4">Charge Fees</h3>
                <p>Tarif Denda: Rp {{ number_format($chargeFees['feeRate'], 0, ',', '.') }} per temuan</p>
                <p>Total Temuan: {{ $chargeFees['totalFindings'] }} | Total Denda: Rp {{ number_format($chargeFees['totalFee'], 0, ',', '.') }}</p>

                <h4 class="text-lg font-medium mt-4">Denda Tertuduh:</h4>
                <table class="min-w-full bg-white border border-gray-200 mt-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Nama</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Departemen</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Jumlah Temuan</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chargeFees['tertuduhDetails'] as $name => $detail)
                        <tr>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $detail['dept'] ?? '-' }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $detail['findings'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">Rp {{ number_format($detail['fee'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <h4 class="text-lg font-medium mt-4">Denda PIC Area (50%):</h4>
                <p>PIC: {{ $auditAnswer->pic_name ?? 'Tidak Ada' }} | Total Temuan: {{ $chargeFees['totalFindings'] }} | Denda: Rp {{ number_format($chargeFees['picAreaFee'], 0, ',', '.') }}</p>

                @if (!empty($chargeFees['managerDetails']))
                <h4 class="text-lg font-medium mt-4">Denda Manager (Rp 1.000/temuan):</h4>
                <table class="min-w-full bg-white border border-gray-200 mt-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Nama</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Departemen</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Jumlah Temuan</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chargeFees['managerDetails'] as $name => $detail)
                        <tr>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $detail['dept'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $detail['findings'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">Rp {{ number_format($detail['fee'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                @if (!empty($chargeFees['gmDetails']))
                <h4 class="text-lg font-medium mt-4">Denda General Manager (Rp 2.000/temuan):</h4>
                <table class="min-w-full bg-white border border-gray-200 mt-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Nama</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Departemen</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Jumlah Temuan</th>
                            <th class="px-4 py-2 border-b-2 border-gray-200 bg-gray-100">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chargeFees['gmDetails'] as $name => $detail)
                        <tr>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $detail['dept'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $detail['findings'] }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">Rp {{ number_format($detail['fee'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>