<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Audit App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo_wag.png') }}">
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 w-full max-w-md text-center">
        <!-- Logo -->
        <img src="{{ asset('logo/logo_wag.png') }}" class="w-36 h-36 mx-auto mb-6" alt="Logo">

        <!-- Title -->
        <h2 class="text-2xl font-bold mb-6">Register</h2>

        @if (session('register_error'))
            <p class="text-red-500 mb-4">{{ session('register_error') }}</p>
        @endif

        <!-- Form -->
        <form action="{{ url('register') }}" method="POST">
            @csrf

            {{-- <input type="hidden" name="device_id" id="device_id"> --}}

            <div class="mb-4">
                <input type="n" name="nik" id="nik" placeholder="NIK" required autofocus
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300 appearance-none">
                <p id="nik-error" class="text-red-500 text-sm mt-1 hidden"></p>
            </div>

            <div class="mb-4">
                <input type="text" name="name" id="name" placeholder="Fullname" required readonly
                    class="w-full px-4 py-2 border rounded-lg bg-gray-100 focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div class="mb-4">
                <input type="text" name="username" placeholder="Username" required 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div class="mb-4">
                <input type="text" name="email" placeholder="Email" required 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
            </div>

            <div class="mb-4 relative">
                <input type="password" id="password" name="password" placeholder="Password" required 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                <span id="togglePassword" class="absolute inset-y-0 right-4 flex items-center cursor-pointer">
                    👁️
                </span>
            </div>

            <!-- Register Link -->
            <div class="flex justify-center mb-4 text-sm">
                <span>Sudah punya akun?</span>
                <a href="{{ url('login') }}" class="text-blue-500 font-semibold ml-1">Login</a>
            </div>

            <!-- Login Button -->
            <button type="submit" class="w-full bg-black text-white py-2 rounded-3xl hover:bg-gray-800">
                Register
            </button>
        </form>
    </div>
    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");
    
        togglePassword.addEventListener("click", function () {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePassword.textContent = "🙈";
            } else {
                passwordInput.type = "password";
                togglePassword.textContent = "👁️";
            }
        });
    
        document.getElementById('nik').addEventListener('blur', function() {
            const nik = this.value;
            const nameInput = document.getElementById('name');
            const nikError = document.getElementById('nik-error');
            if (nik) {
                fetch(`/api/karyawan/${nik}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.emp_name) {
                            nameInput.value = data.emp_name;
                            nikError.textContent = '';
                            nikError.classList.add('hidden');
                        } else {
                            nameInput.value = '';
                            nikError.textContent = 'Karyawan tidak ditemukan. Silakan hubungi HRD untuk pendaftaran data karyawan.';
                            nikError.classList.remove('hidden');
                        }
                    })
                    .catch(() => {
                        nameInput.value = '';
                        nikError.textContent = 'Terjadi kesalahan saat mengambil data karyawan.';
                        nikError.classList.remove('hidden');
                    });
            } else {
                nameInput.value = '';
                nikError.textContent = '';
                nikError.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
