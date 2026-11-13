@extends('layouts.admin')
@section('page-title', 'Manajemen Pengguna')

@section('content')
<div x-data="usersPage({{ auth()->id() }})" x-init="load()">

    {{-- Toast --}}
    <div x-show="toast.message" x-cloak x-transition
         :class="toast.isError ? 'bg-red-700' : 'bg-green-700'"
         class="fixed bottom-6 right-6 z-50 text-white text-sm font-semibold rounded-xl shadow-xl px-5 py-3.5 flex items-center gap-3">
        <span x-text="toast.isError ? '⚠️' : '✅'"></span><span x-text="toast.message"></span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-800">👥 Manajemen Pengguna</h2>
                <p class="text-xs text-gray-400">Akun staf SIRESTEDUH: Admin, Kasir, Kasir Makanan, dan Petugas Check-in.</p>
            </div>
            <button @click="openModal()" class="bg-green-700 text-white text-sm font-bold rounded-xl px-4 py-2.5 hover:bg-green-800 transition shadow">
                + Tambah Pengguna
            </button>
        </div>

        {{-- Role filter tabs --}}
        <div class="px-6 pt-4 flex gap-1.5 border-b border-gray-100 pb-4 text-xs sm:text-sm">
            <template x-for="tab in tabs" :key="tab.value">
                <button type="button" @click="activeTab = tab.value"
                        :class="activeTab === tab.value ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3.5 py-2 rounded-full font-semibold transition">
                    <span x-text="tab.icon + ' ' + tab.label"></span>
                    <span class="text-[10px] opacity-80" x-text="' (' + countByRole(tab.value) + ')'"></span>
                </button>
            </template>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="rtable w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b">
                        <th class="pb-3 pr-4 pl-6">Nama</th>
                        <th class="pb-3 pr-4">Email</th>
                        <th class="pb-3 pr-4 text-center">Role</th>
                        <th class="pb-3 pr-4">Dibuat</th>
                        <th class="pb-3 pr-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="loading">
                        <tr><td colspan="5" class="py-16 text-center text-gray-400">Memuat pengguna...</td></tr>
                    </template>
                    <template x-if="!loading && filteredUsers.length === 0">
                        <tr><td colspan="5" class="py-16 text-center text-gray-400">Tidak ada pengguna pada role ini.</td></tr>
                    </template>
                    <template x-for="user in filteredUsers" :key="user.id">
                        <tr class="hover:bg-gray-50 align-middle">
                            <td data-label="Nama" class="py-3 pr-4 pl-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                         :class="avatarColor(user.role)"
                                         x-text="user.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <p class="font-semibold text-gray-800" x-text="user.name"></p>
                                        <span x-show="user.id === currentUserId" x-cloak class="text-[10px] font-bold text-green-700 uppercase tracking-wide">Anda</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Email" class="py-3 pr-4 text-gray-600" x-text="user.email"></td>
                            <td data-label="Role" class="py-3 pr-4 text-center">
                                <span class="text-[11px] font-bold rounded-full px-2.5 py-0.5" :class="roleBadge(user.role)" x-text="roleLabel(user.role)"></span>
                            </td>
                            <td data-label="Dibuat" class="py-3 pr-4 text-xs text-gray-500" x-text="formatDate(user.created_at)"></td>
                            <td class="py-3 pr-6">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="openPasswordModal(user)" class="text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg px-3 py-1.5 transition">🔑 Password</button>
                                    <button @click="openModal(user)" class="text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg px-3 py-1.5 transition">✏️ Edit</button>
                                    <button @click="destroy(user)" :disabled="user.id === currentUserId"
                                            :class="user.id === currentUserId ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-red-700 bg-red-50 hover:bg-red-100'"
                                            class="text-xs font-semibold rounded-lg px-3 py-1.5 transition">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add/Edit modal --}}
    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
        <form @submit.prevent="save()" class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800" x-text="form.id ? '✏️ Edit Pengguna' : '👥 Tambah Pengguna'"></h3>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">×</button>
            </div>
            <div class="p-6 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-500">Nama Lengkap</label>
                    <input type="text" x-model="form.name" required maxlength="255" placeholder="Nama staf"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Email</label>
                    <input type="email" x-model="form.email" required maxlength="255" placeholder="nama@simateduh.com"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div x-show="!form.id">
                    <label class="text-xs font-semibold text-gray-500">Password <span class="text-gray-400">(min. 8 karakter)</span></label>
                    <input type="password" x-model="form.password" required minlength="8" autocomplete="new-password"
                           class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Role</label>
                    <select x-model="form.role" required :disabled="form.id === currentUserId"
                            :class="form.id === currentUserId ? 'bg-gray-100 cursor-not-allowed' : ''"
                            class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="admin">Admin — akses penuh</option>
                        <option value="kasir">Kasir — POS penuh</option>
                        <option value="kasir_makanan">Kasir Makanan — khusus produk makanan</option>
                        <option value="checkin">Check-in — scanner saja</option>
                    </select>
                    <p x-show="form.id === currentUserId" x-cloak class="text-[11px] text-amber-700 mt-1">Anda tidak bisa mengubah role akun sendiri.</p>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center gap-2 justify-end border-t border-gray-100 pt-4">
                <button type="button" @click="closeModal()" class="text-sm font-semibold text-gray-500 border border-gray-200 rounded-xl px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                <button type="submit" :disabled="saving"
                        :class="saving ? 'bg-gray-300 cursor-wait' : 'bg-green-700 hover:bg-green-800'"
                        class="text-sm font-bold text-white rounded-xl px-5 py-2.5 transition"
                        x-text="saving ? 'Menyimpan...' : '💾 Simpan'"></button>
            </div>
        </form>
    </div>

    {{-- Reset password modal --}}
    <div x-show="passwordModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="passwordModalOpen = false"></div>
        <form @submit.prevent="resetPassword()" class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800">🔑 Reset Password</h3>
                <p class="text-xs text-gray-400 mt-0.5" x-text="passwordUser?.name + ' (' + passwordUser?.email + ')'"></p>
            </div>
            <div class="p-6">
                <label class="text-xs font-semibold text-gray-500">Password Baru <span class="text-gray-400">(min. 8 karakter)</span></label>
                <input type="password" x-model="newPassword" required minlength="8" autocomplete="new-password"
                       class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div class="px-6 pb-6 flex items-center gap-2 justify-end border-t border-gray-100 pt-4">
                <button type="button" @click="passwordModalOpen = false" class="text-sm font-semibold text-gray-500 border border-gray-200 rounded-xl px-4 py-2.5 hover:bg-gray-50 transition">Batal</button>
                <button type="submit" :disabled="saving"
                        :class="saving ? 'bg-gray-300 cursor-wait' : 'bg-green-700 hover:bg-green-800'"
                        class="text-sm font-bold text-white rounded-xl px-5 py-2.5 transition"
                        x-text="saving ? 'Menyimpan...' : '🔑 Reset'"></button>
            </div>
        </form>
    </div>

</div>

<script>
function usersPage(currentUserId) {
    return {
        currentUserId,
        users: [],
        loading: true,
        activeTab: 'all',
        isModalOpen: false,
        saving: false,
        form: { id: null, name: '', email: '', password: '', role: 'kasir' },
        passwordModalOpen: false,
        passwordUser: null,
        newPassword: '',
        toast: { message: '', isError: false },

        tabs: [
            { value: 'all', label: 'Semua', icon: '👥' },
            { value: 'admin', label: 'Admin', icon: '🛡️' },
            { value: 'kasir', label: 'Kasir', icon: '🛒' },
            { value: 'kasir_makanan', label: 'Kasir Makanan', icon: '🍜' },
            { value: 'checkin', label: 'Check-in', icon: '📷' },
        ],

        get filteredUsers() {
            if (this.activeTab === 'all') return this.users;
            return this.users.filter(u => u.role === this.activeTab);
        },

        countByRole(role) {
            if (role === 'all') return this.users.length;
            return this.users.filter(u => u.role === role).length;
        },

        roleLabel(role) {
            return { admin: 'ADMIN', kasir: 'KASIR', kasir_makanan: 'KASIR MAKANAN', checkin: 'CHECK-IN' }[role] ?? role.toUpperCase();
        },

        roleBadge(role) {
            return {
                admin: 'bg-red-100 text-red-800',
                kasir: 'bg-amber-100 text-amber-800',
                kasir_makanan: 'bg-orange-100 text-orange-800',
                checkin: 'bg-blue-100 text-blue-800',
            }[role] ?? 'bg-gray-100 text-gray-600';
        },

        avatarColor(role) {
            return { admin: 'bg-red-600', kasir: 'bg-amber-500', kasir_makanan: 'bg-orange-500', checkin: 'bg-blue-600' }[role] ?? 'bg-gray-500';
        },

        formatDate(iso) {
            if (!iso) return '-';
            return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/admin/users', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.users = data.data || [];
            } catch (e) {
                this.showToast('Gagal memuat pengguna.', true);
            } finally {
                this.loading = false;
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        openModal(user = null) {
            this.form = user
                ? { ...user, password: '' }
                : { id: null, name: '', email: '', password: '', role: 'kasir' };
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        openPasswordModal(user) {
            this.passwordUser = user;
            this.newPassword = '';
            this.passwordModalOpen = true;
        },

        async save() {
            this.saving = true;
            const isEdit = !!this.form.id;
            const url = isEdit ? '/api/admin/users/' + this.form.id : '/api/admin/users';
            const method = isEdit ? 'PUT' : 'POST';
            const payload = isEdit
                ? { name: this.form.name, email: this.form.email, role: this.form.role }
                : this.form;

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.ok) {
                    this.users = isEdit
                        ? this.users.map(u => (u.id === data.data.id ? data.data : u))
                        : [data.data, ...this.users];
                    this.closeModal();
                    this.showToast(isEdit ? 'Pengguna diperbarui.' : 'Pengguna ditambahkan.');
                } else {
                    this.showToast(this.firstError(data) ?? 'Gagal menyimpan.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.saving = false;
            }
        },

        async resetPassword() {
            if (!this.passwordUser) return;
            this.saving = true;
            try {
                const res = await fetch('/api/admin/users/' + this.passwordUser.id + '/password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ password: this.newPassword }),
                });
                const data = await res.json();

                if (res.ok) {
                    this.passwordModalOpen = false;
                    this.showToast('Password berhasil direset.');
                } else {
                    this.showToast(this.firstError(data) ?? 'Gagal reset password.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            } finally {
                this.saving = false;
            }
        },

        async destroy(user) {
            if (user.id === this.currentUserId) return;
            if (!confirm('Hapus pengguna "' + user.name + '"?')) return;
            try {
                const res = await fetch('/api/admin/users/' + user.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                });
                const data = await res.json();

                if (res.ok) {
                    this.users = this.users.filter(u => u.id !== user.id);
                    this.showToast('Pengguna dihapus.');
                } else {
                    this.showToast(data.message ?? 'Gagal menghapus.', true);
                }
            } catch (e) {
                this.showToast('Koneksi gagal.', true);
            }
        },

        firstError(data) {
            if (data?.errors) {
                const first = Object.values(data.errors)[0];
                return Array.isArray(first) ? first[0] : String(first);
            }
            return data?.message ?? null;
        },

        showToast(message, isError = false) {
            this.toast = { message, isError };
            setTimeout(() => { this.toast = { message: '', isError: false }; }, 4000);
        },
    };
}
</script>
@endsection
