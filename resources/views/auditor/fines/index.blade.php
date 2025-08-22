@extends('layouts.auditor')

@section('title', 'Audit Form - Audit App')

@section('content')
<div class="container mx-auto p-4 min-h-screen">
    @if (session('fine_payment_error'))
    <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
        {{ session('fine_payment_error') }}
    </div>
    @endif
    @if (session('fine_payment_success'))
    <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
        {{ session('fine_payment_success') }}
    </div>
    @endif
    <a href="{{ route('dashboard') }}" class="text-lg font-semibold flex items-center mb-4 w-fit">
        ← Kembali
    </a>
    <h1 class="text-xl font-bold mb-4">Isi Form Pembayaran Denda</h1>

    <form action="{{ route('payment-fines-submit') }}" method="POST" class="max-w-lg mx-auto">
        @csrf
        <div class="mb-4 relative">
            <label class="block text-gray-700 font-semibold mb-2">Nama Karyawan</label>
            <input 
                type="text" 
                placeholder="Nama Karyawan" 
                class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" 
                name="name"
                id="namaKaryawan"
                autocomplete="off"
                required
            >
            
            <!-- Dropdown hasil pencarian -->
            <ul id="dropdownKaryawan" class="absolute bg-white border border-gray-300 rounded-lg shadow-md w-full mt-1 hidden z-10"></ul>

            <!-- Info sisa denda -->
            <p id="sisaDenda" class="mt-2 text-sm text-red-600 font-semibold hidden"></p>
        </div>
        <div class="mb-4">
            <label for="amount" class="block text-gray-700 font-semibold mb-2">Jumlah Bayar</label>
            <input type="number" name="amount" id="amount" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan jumlah denda" required>
        </div>
        <div class="mb-4 mt-5">
            <button type="submit" class="w-full bg-black text-white py-3 rounded-lg font-semibold hover:bg-gray-700 transition duration-200">Bayar Denda</button>
        </div>
    </form>

</div>
@endsection
@push('scripts')
<script>
    const input = document.getElementById('namaKaryawan');
    const dropdown = document.getElementById('dropdownKaryawan');
    const sisaDenda = document.getElementById('sisaDenda');

    input.addEventListener('keyup', function() {
        let query = this.value;

        if (query.length > 0) {
            fetch(`/api/search-karyawan?q=${query}`)
                .then(response => response.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (data.length > 0) {
                        dropdown.classList.remove('hidden');
                        data.forEach(item => {
                            let li = document.createElement('li');
                            li.textContent = item.emp_name + ' - ' + item.emp_id;
                            li.className = "p-2 hover:bg-blue-100 cursor-pointer";
                            li.onclick = () => {
                                input.value = item.emp_name;
                                dropdown.classList.add('hidden');

                                // tampilkan sisa denda
                                sisaDenda.textContent = "Sisa denda: Rp " + new Intl.NumberFormat().format(item.total_due);
                                sisaDenda.classList.remove('hidden');
                            };
                            dropdown.appendChild(li);
                        });
                    } else {
                        dropdown.classList.add('hidden');
                    }
                });
        } else {
            dropdown.classList.add('hidden');
            sisaDenda.classList.add('hidden'); // sembunyikan kalau kosong
        }
    });

    // biar dropdown hilang kalau klik di luar
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
</script>
@endpush