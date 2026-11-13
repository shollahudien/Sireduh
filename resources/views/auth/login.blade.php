<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; SIRESTEDUH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* ===== Maskot login bergerak ===== */
        @keyframes bot-bob {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        @keyframes bot-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        @keyframes bot-blink {
            0%, 93%, 100% { transform: scaleY(1); }
            96% { transform: scaleY(0.08); }
        }
        @keyframes bot-shine {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .bot-float { animation: bot-float 3s ease-in-out infinite; }
        .bot-antenna { animation: bot-bob 1.8s ease-in-out infinite; transform-origin: 100px 24px; }
        .bot-eye { animation: bot-blink 4.4s ease-in-out infinite; transform-box: fill-box; transform-origin: center; }
        .bot-pupils { transition: transform 0.18s ease-out; }
        .bot-arm { transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .bot-mouth { transition: opacity 0.22s ease; }
        .bot-cheek { transition: opacity 0.3s ease; }

        .login-btn-shine {
            background-image: linear-gradient(110deg, #15803d 20%, #16a34a 40%, #4ade80 50%, #16a34a 60%, #15803d 80%);
            background-size: 200% 100%;
            animation: bot-shine 3.5s linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .bot-float, .bot-antenna, .bot-eye, .login-btn-shine { animation: none !important; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl border border-green-100 overflow-hidden">

            {{-- ===== Maskot robot: mata mengikuti ketikan, menutup mata saat password ===== --}}
            <div class="pt-7 pb-1 flex flex-col items-center bg-gradient-to-b from-green-50 to-white"
                 x-data="loginBot({{ $errors->any() ? 'true' : 'false' }})">

                <svg class="bot-float w-40 sm:w-44 h-auto" viewBox="0 0 200 170" fill="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="botBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#16a34a"/>
                            <stop offset="100%" stop-color="#0f766e"/>
                        </linearGradient>
                    </defs>

                    {{-- Antena bergoyang --}}
                    <g class="bot-antenna">
                        <line x1="100" y1="40" x2="100" y2="22" stroke="#166534" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="100" cy="16" r="6.5" fill="#a3e635" stroke="#166534" stroke-width="2.5"/>
                    </g>

                    {{-- Kepala --}}
                    <rect x="45" y="40" width="110" height="95" rx="26" fill="url(#botBody)"/>
                    <rect x="62" y="56" width="76" height="58" rx="18" fill="#052e23"/>

                    {{-- Pipi merona (muncul saat senang) --}}
                    <ellipse class="bot-cheek" cx="74" cy="97" rx="4.5" ry="3" fill="#fb923c" opacity="0"
                             :opacity="showCheeks ? 0.85 : 0"/>
                    <ellipse class="bot-cheek" cx="126" cy="97" rx="4.5" ry="3" fill="#fb923c" opacity="0"
                             :opacity="showCheeks ? 0.85 : 0"/>

                    {{-- Mata: pupil mengikuti ketikan; berkedip otomatis --}}
                    <g class="bot-eye">
                        <circle cx="84" cy="84" r="10" fill="#f0fdf4"/>
                        <circle cx="116" cy="84" r="10" fill="#f0fdf4"/>
                        <g class="bot-pupils" :style="'transform: translateX(' + pupilShift + 'px)'">
                            <circle cx="84" cy="85" r="4.6" fill="#052e23"/>
                            <circle cx="116" cy="85" r="4.6" fill="#052e23"/>
                            <circle cx="85.6" cy="83.4" r="1.4" fill="#d9f99d"/>
                            <circle cx="117.6" cy="83.4" r="1.4" fill="#d9f99d"/>
                        </g>
                    </g>

                    {{-- Mulut berubah sesuai kondisi --}}
                    <path class="bot-mouth" d="M92 100 Q100 106 108 100" stroke="#a3e635" stroke-width="3.2"
                          stroke-linecap="round" fill="none" x-show="mouth === 'idle'"/>
                    <path class="bot-mouth" d="M87 98 Q100 113 113 98" stroke="#a3e635" stroke-width="3.2"
                          stroke-linecap="round" fill="none" x-show="mouth === 'happy'"/>
                    <ellipse class="bot-mouth" cx="100" cy="102" rx="5.5" ry="7" fill="#0b3b2e" stroke="#a3e635"
                             stroke-width="2.6" x-show="mouth === 'o'"/>
                    <path class="bot-mouth" d="M91 104 Q100 96 109 104" stroke="#f87171" stroke-width="3.2"
                          stroke-linecap="round" fill="none" x-show="mouth === 'sad'"/>

                    {{-- Telapak tangan naik menutup mata saat isi password --}}
                    <g class="bot-arm" :style="covering ? 'transform: translate(33px, -63px) rotate(-14deg)' : ''">
                        <rect x="18" y="116" width="40" height="50" rx="20" fill="#0f766e" stroke="#134e4a" stroke-width="2.5"/>
                        <circle cx="34" cy="128" r="3" fill="#a3e635" opacity="0.7"/>
                        <circle cx="43" cy="124" r="3" fill="#a3e635" opacity="0.7"/>
                    </g>
                    <g class="bot-arm" :style="covering ? 'transform: translate(-33px, -63px) rotate(14deg)' : ''">
                        <rect x="142" y="116" width="40" height="50" rx="20" fill="#0f766e" stroke="#134e4a" stroke-width="2.5"/>
                        <circle cx="166" cy="128" r="3" fill="#a3e635" opacity="0.7"/>
                        <circle cx="157" cy="124" r="3" fill="#a3e635" opacity="0.7"/>
                    </g>
                </svg>

                <p class="text-[11px] text-green-700/70 font-semibold mt-1 mb-1" x-text="hint"></p>

                <h1 class="text-2xl font-extrabold text-green-900 tracking-wide">SIRESTEDUH</h1>
                <p class="text-xs text-gray-400 mt-0.5 mb-4">Sistem Informasi Reservasi Terpadu</p>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-4 w-full mx-6 self-center">
                        ⚠️ {{ $errors->first() }} — coba perhatikan lagi ya!
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4 w-full px-6 pb-6 text-left"
                      @submit="submitting = true">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               @input="onEmail($event)" @focus="emailFocused = true" @blur="emailFocused = false"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-lime-400 focus:border-lime-400 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Password</label>
                        <input type="password" name="password" required autocomplete="current-password"
                               @focus="passFocused = true" @blur="passFocused = false"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-lime-400 focus:border-lime-400 outline-none transition">
                    </div>
                    <button type="submit"
                            class="login-btn-shine w-full text-white py-3 rounded-xl font-bold tracking-wide shadow-lg shadow-green-700/25 hover:shadow-green-700/40 hover:-translate-y-0.5 active:scale-95 transition-all duration-200 focus-visible:ring-4 focus-visible:ring-lime-300/60 focus-visible:outline-none">
                        <span x-text="submitting ? '⏳ Memeriksa…' : '🔐 Masuk'"></span>
                    </button>
                </form>

                <p class="text-center text-xs text-gray-400 pb-6">Akun staf dibuatkan oleh admin.</p>
            </div>
        </div>
    </div>

    <script>
        function loginBot(startSad) {
            return {
                emailFocused: false,
                passFocused: false,
                emailLen: 0,
                sad: startSad,
                submitting: false,

                onEmail(e) {
                    this.emailLen = (e.target.value ?? '').length;
                    this.sad = false;
                },

                get covering() {
                    return this.passFocused;
                },

                get pupilShift() {
                    if (!this.emailFocused || this.covering) return 0;
                    // Pupil mengikuti ketikan: bergeser ke kanan, lalu kembali saat panjang berlalu
                    const swing = this.emailLen % 14;
                    return Number((Math.sin(swing / 2.2) * 5.2).toFixed(2));
                },

                get mouth() {
                    if (this.sad) return 'sad';
                    if (this.covering) return 'o';
                    if (this.emailLen > 0) return 'happy';
                    return 'idle';
                },

                get showCheeks() {
                    return !this.sad && !this.covering;
                },

                get hint() {
                    if (this.sad) return 'Ups! Coba periksa email/password-mu 😖';
                    if (this.covering) return 'Aku menutup mata, amankan passwordmu… 🫣';
                    if (this.emailFocused) return 'Aku mengikuti ketikanmu 👀';
                    return 'Masuk sebagai petugas/admin 🤖';
                },
            };
        }
    </script>
</body>
</html>
