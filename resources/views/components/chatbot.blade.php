{{-- Widget chatbot publik SIRESTEDUH — butuh Alpine & meta csrf-token --}}
<div x-data="chatWidget()" x-cloak class="fixed z-50 bottom-20 right-4 sm:bottom-4 flex flex-col items-end gap-3 print:hidden bot-bubble-hover">

    {{-- Panel chat --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="w-[calc(100vw-2rem)] max-w-sm bg-white rounded-2xl shadow-2xl border border-green-100 overflow-hidden flex flex-col"
         style="height: min(32rem, calc(100vh - 6rem));">

        {{-- Header --}}
        <div class="bg-green-700 text-white px-4 py-3 flex items-center gap-3 shrink-0">
            <div class="shrink-0"><x-bot-mascot size="w-9 h-9" /></div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm leading-tight">Teduh Bot</p>
                <p class="text-[11px] text-green-100 leading-tight">Asisten BUMDes Wisata Teduh</p>
            </div>
            <span x-show="aiLoading" x-cloak class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin shrink-0" title="Menyiapkan suara AI…"></span>
            <button type="button" @click="toggleSound()"
                    :title="soundOn ? 'Matikan suara Teduh Bot' : 'Aktifkan suara Teduh Bot'"
                    :aria-label="soundOn ? 'Matikan suara Teduh Bot' : 'Aktifkan suara Teduh Bot'"
                    class="w-8 h-8 rounded-full hover:bg-white/20 active:scale-90 transition flex items-center justify-center text-base"
                    x-text="soundOn ? '🔊' : '🔇'">🔇</button>
            <button type="button" @click="open = false" aria-label="Tutup chat"
                    class="w-8 h-8 rounded-full hover:bg-white/20 active:scale-90 transition flex items-center justify-center">✕</button>
        </div>

        {{-- Area pesan --}}
        <div x-ref="box" class="flex-1 overflow-y-auto px-3 py-3 space-y-2 bg-green-50/50">
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                        ? 'bg-green-700 text-white rounded-2xl rounded-br-md'
                        : 'bg-white text-gray-800 border border-green-100 rounded-2xl rounded-bl-md'"
                         class="max-w-[85%] px-3 py-2 text-sm leading-relaxed shadow-sm whitespace-pre-line" x-text="msg.text"></div>
                </div>
            </template>

            {{-- Indikator mengetik --}}
            <div x-show="sending" class="flex justify-start">
                <div class="bg-white border border-green-100 rounded-2xl rounded-bl-md px-3 py-2 shadow-sm">
                    <span class="inline-flex gap-1">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-bounce [animation-delay:0ms]"></span>
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-bounce [animation-delay:150ms]"></span>
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-bounce [animation-delay:300ms]"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Saran cepat --}}
        <div class="px-3 pb-1.5 flex gap-1.5 overflow-x-auto shrink-0">
            <template x-for="chip in chips" :key="chip">
                <button type="button" @click="ask(chip)" :disabled="sending"
                        class="shrink-0 text-[11px] font-semibold bg-green-100 text-green-800 rounded-full px-3 py-1.5 hover:bg-green-200 active:scale-95 transition disabled:opacity-50"
                        x-text="chip"></button>
            </template>
        </div>

        {{-- Tombol WhatsApp langsung ke pengelola --}}
        <div class="px-3 pb-1.5 shrink-0">
            <button type="button" @click="contactWhatsApp()"
                    class="w-full text-[11px] font-semibold bg-emerald-600 text-white rounded-xl px-3 py-2 hover:bg-emerald-700 active:scale-95 transition flex items-center justify-center gap-1.5">
                <span>💬</span>
                <span>Hubungi WhatsApp Pengelola</span>
                <span x-show="userName" x-cloak class="opacity-80" x-text="`(${userName})`"></span>
            </button>
        </div>
        {{-- Input --}}
        <div class="border-t border-green-100 p-2.5 flex gap-2 items-center shrink-0 bg-white">
            <input type="text" x-model="input" @keydown.enter.prevent="ask()"
                   placeholder="Tulis pertanyaan Anda…" maxlength="500"
                   class="flex-1 min-w-0 border border-green-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-600 focus:outline-none">
            <button type="button" @click="ask()" :disabled="sending || !input.trim()"
                    aria-label="Kirim"
                    class="w-10 h-10 shrink-0 rounded-xl bg-green-700 text-white font-bold hover:bg-green-800 active:scale-90 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center">➤</button>
        </div>
    </div>

    {{-- Tombol mengambang: robot Teduh Bot bergerak (melayang, antena goyang, mata berkedip) --}}
    <button type="button" @click="toggle()" x-show="!open"
            class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-green-600 to-teal-600 ring-2 ring-lime-300/50 shadow-xl shadow-green-950/30 hover:scale-105 hover:ring-lime-300 active:scale-95 transition-all duration-300 flex items-center justify-center"
            aria-label="Buka chat bantuan Teduh Bot">
        <x-bot-mascot size="w-10 h-10 sm:w-11 sm:h-11" bubble="Halo! 👋" bubble-side="right" />
    </button>
</div>

<script>
function chatWidget() {
    const _sidKey = 'teduh_bot_session_id';
    let _sid = localStorage.getItem(_sidKey);
    if (!_sid) {
        _sid = (crypto.randomUUID ? crypto.randomUUID() : ('sess-' + Date.now() + '-' + Math.random().toString(16).slice(2)));
        localStorage.setItem(_sidKey, _sid);
    }
    return {
        open: false,
        sending: false,
        input: '',
        messages: [],
        chips: ['Cara reservasi?', 'Harga glamping?', 'Cara pembayaran?', 'Cek status pesanan', 'Tiket masuk?'],
        sessionId: _sid,
        userName: localStorage.getItem('teduh_bot_user_name') || '',
        askingName: false,
        waNumber: '{{ config('services.payment.whatsapp') }}',
        soundOn: localStorage.getItem('teduh_bot_voice') === 'on',
        aiAudio: null,        // elemen <audio> untuk suara AI Gemini
        aiLoading: false,     // sedang minta suara AI ke server
        aiManualUrl: '',      // terisi bila autoplay diblokir — tampilkan pemutar manual


        toggle() {
            this.open = !this.open;

            if (this.open && this.messages.length === 0) {
                const name = this.userName ? `, ${this.userName}` : '';
                const greeting = `Halo${name}! 👋 Saya Teduh Bot, asisten BUMDes Wisata Teduh.`;
                this.messages.push({ role: 'bot', text: greeting });
                this.scrollDown();
            }

            if (!this.open) {
                this.stopSpeaking();
            }
        },

        toggleSound() {
            this.soundOn = !this.soundOn;
            localStorage.setItem('teduh_bot_voice', this.soundOn ? 'on' : 'off');
            if (this.soundOn && this.open) {
                const lastBot = [...this.messages].reverse().find(m => m.role === 'bot');
                if (lastBot) this.speakReply(lastBot.text);
            } else {
                this.stopSpeaking();
            }
        },

        // Bersihkan emoji/simbol agar suara tidak membacakan "wajah tersenyum"
        cleanForSpeech(text) {
            return text
                .replace(/[^\p{L}\p{N}\p{P}\s]/gu, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, 400);
        },

        /**
         * Suara AI Gemini saja (Despina — wanita Indonesia lembut).
         * Bila Gemini TTS gagal/diblokir, tidak ada suara cadangan;
         * pemutar manual muncul bila autoplay diblokir browser.
         */
        async speakReply(text) {
            if (!this.soundOn) return;

            this.stopSpeaking();

            const spoken = this.cleanForSpeech(text);
            if (!spoken) return;

            this.aiLoading = true;
            try {
                const res = await fetch('/api/chat/speak', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json, audio/wav',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ text: spoken }),
                });

                if (res.ok) {
                    const blob = await res.blob();
                    const url = URL.createObjectURL(blob);
                    const audio = new Audio(url);
                    audio.onended = () => {
                        URL.revokeObjectURL(url);
                        this.aiAudio = null;
                        if (this.aiManualUrl === url) {
                            this.aiManualUrl = '';
                        }
                    };
                    this.aiAudio = audio;

                    try {
                        await audio.play();
                        this.aiManualUrl = '';
                    } catch (e) {
                        // Autoplay diblokir browser — tampilkan kontrol agar bisa diputar manual
                        this.aiManualUrl = url;
                    }
                    return;
                }
            } catch (e) {
                // Gemini TTS tidak tersedia — tidak ada suara (tanpa suara cadangan)
            } finally {
                this.aiLoading = false;
            }
        },

        stopSpeaking() {
            if (this.aiAudio) {
                try {
                    this.aiAudio.pause();
                    this.aiAudio.currentTime = 0;
                } catch (e) {
                    // abaikan
                }
                this.aiAudio = null;
            }
            if (this.aiManualUrl) {
                URL.revokeObjectURL(this.aiManualUrl);
                this.aiManualUrl = '';
            }
        },

        async ask(text) {
            const message = (text ?? this.input).trim();

            if (!message || this.sending) return;

            // Tangkap nama pengunjung bila sedang diminta (untuk kontak WhatsApp)
            if (this.askingName) {
                this.input = '';
                this.userName = message.slice(0, 80);
                localStorage.setItem('teduh_bot_user_name', this.userName);
                this.askingName = false;
                this.messages.push({ role: 'user', text: message });
                this.messages.push({ role: 'bot', text: `Terima kasih, ${this.userName}! 😊\nSekarang Anda bisa hubungi pengelola via WhatsApp. Klik tombol "Hubungi WhatsApp" di bawah, pesan akan terisi otomatis dengan nama Anda.` });
                this.scrollDown();
                return;
            }

            this.messages.push({ role: 'user', text: message });
            this.input = '';
            this.sending = true;
            this.scrollDown();

            try {
                const history = this.messages
                    .filter(m => m.role === 'user' || m.role === 'bot')
                    .slice(-10)
                    .map(m => ({ role: m.role, text: m.text }));

                const res = await fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ message, history, user_name: this.userName || null, session_id: this.sessionId }),
                });
                const data = await res.json().catch(() => ({}));

                const reply = data.reply || 'Maaf, saya sedang tidak bisa menjawab. Silakan coba lagi atau hubungi WhatsApp pengelola. 🙏';
                this.messages.push({ role: 'bot', text: reply });
                this.speakReply(reply);
            } catch (e) {
                const fallback = 'Koneksi bermasalah. Silakan coba lagi, atau buka status untuk cek pesanan Anda. 🙏';
                this.messages.push({ role: 'bot', text: fallback });
            } finally {
                this.sending = false;
                this.scrollDown();
            }
        },

        scrollDown() {
            this.$nextTick(() => {
                const box = this.$refs.box;
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        contactWhatsApp() {
            if (!this.userName) {
                this.askingName = true;
                this.messages.push({ role: 'bot', text: 'Sebelum menghubungi WhatsApp, boleh saya tahu nama Anda? (ketik nama, lalu kirim)' });
                this.scrollDown();
                return;
            }

            const num = (this.waNumber || '').replace(/^0/, '62').replace(/\D/g, '');
            const lastUser = [...this.messages].reverse().find(m => m.role === 'user')?.text || '';
            const text = `Halo, saya ${this.userName}.${lastUser ? ' ' + lastUser : ''}`;
            const url = `https://wa.me/${num}?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank', 'noopener');
        },
    };
}
</script>
