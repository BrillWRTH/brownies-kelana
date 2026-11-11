@extends('layouts.admin')

@section('title', 'Kirim Notifikasi Khusus — Panel Admin')
@section('kicker', 'KIRIM NOTIFIKASI PELANGGAN')

@section('content')
    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex flex-wrap gap-2 rounded-2xl border border-[#e8dfd3] bg-white p-4 text-xs font-semibold shadow-xs">
            <a href="{{ route('admin.notifications.index') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Inbox Admin</a>
            <a href="{{ route('admin.notifications.templates') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Template Pesan</a>
            <a href="{{ route('admin.notifications.create') }}" class="rounded-xl bg-[#52311a] px-4 py-2 text-white">Kirim Notifikasi Khusus</a>
            <a href="{{ route('admin.notifications.history') }}" class="rounded-xl bg-[#fdfbf6] px-4 py-2 text-[#52311a] hover:bg-[#f9eddd]">Riwayat Kirim</a>
        </div>

        <form method="POST" action="{{ route('admin.notifications.store') }}" class="grid gap-6 lg:grid-cols-12">
            @csrf

            <div class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-6 shadow-xs lg:col-span-7">
                <h1 class="font-serif text-lg font-bold text-[#28211c]">Kirim Pengumuman / Notifikasi Personal</h1>

                <div>
                    <label class="kelana-label">Target Penerima *</label>
                    <div class="mt-1 grid grid-cols-2 gap-3 text-xs font-semibold">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] px-3.5 py-2.5">
                            <input type="radio" name="target_type" value="single" class="notif-target-radio text-[#52311a]"
                                @checked(old('target_type', 'single') === 'single')>
                            <span>Pelanggan Tertentu</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] px-3.5 py-2.5">
                            <input type="radio" name="target_type" value="broadcast" class="notif-target-radio text-[#52311a]"
                                @checked(old('target_type') === 'broadcast')>
                            <span>Broadcast Semua Akun Aktif</span>
                        </label>
                    </div>
                </div>

                <div id="notif-customer-select-wrap">
                    <label for="user_id" class="kelana-label">Pilih Pelanggan *</label>
                    <select id="user_id" name="user_id" class="kelana-input text-xs">
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" @selected((string) old('user_id') === (string) $customer->id)>
                                {{ $customer->customer_code }} · {{ $customer->name }} ({{ $customer->phone ?: $customer->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="order_id" class="kelana-label">Tautkan Pesanan (Opsional)</label>
                        <select id="order_id" name="order_id" class="kelana-input text-xs">
                            <option value="">-- Tanpa Tautan Order --</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}" data-number="{{ $order->order_number }}" @selected((string) old('order_id') === (string) $order->id)>
                                    {{ $order->order_number }} — {{ $order->customer_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="template_preset" class="kelana-label">Muat dari Template (Opsional)</label>
                        <select id="template_preset" class="kelana-input text-xs">
                            <option value="">-- Ketik Pesan Manual --</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" data-title="{{ $tpl->title }}" data-message="{{ $tpl->message_template }}" data-category="{{ $tpl->category }}">
                                    {{ $tpl->title }} ({{ $tpl->category }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="kelana-label">Kategori *</label>
                        <select id="category" name="category" required class="kelana-input text-xs">
                            <option value="pesanan" @selected(old('category') === 'pesanan')>Informasi Pesanan</option>
                            <option value="sistem" @selected(old('category', 'sistem') === 'sistem')>Pengumuman / Sistem</option>
                        </select>
                    </div>
                    <div>
                        <label for="channel" class="kelana-label">Kanal Pengiriman *</label>
                        <select id="channel" name="channel" required class="kelana-input text-xs">
                            <option value="dashboard" @selected(old('channel', 'dashboard') === 'dashboard')>Notifikasi Dashboard Pelanggan</option>
                            <option value="whatsapp_manual" @selected(old('channel') === 'whatsapp_manual')>WhatsApp Manual (+ Catat Log)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="notif_title" class="kelana-label">Judul Notifikasi *</label>
                    <input id="notif_title" name="title" type="text" value="{{ old('title') }}" required
                        placeholder="Contoh: Jadwal Pickup Spesial Akhir Pekan" class="kelana-input text-xs">
                </div>

                <div>
                    <label for="notif_message" class="kelana-label">Isi Pesan *</label>
                    <textarea id="notif_message" name="message" rows="4" required
                        placeholder="Halo Kak {nama_pelanggan}, pesanan {nomor_pesanan} Anda..."
                        class="kelana-input text-xs">{{ old('message') }}</textarea>
                    <p class="mt-1 text-[11px] text-[#796b62]">
                        Anda dapat memakai token <code>{nama_pelanggan}</code> dan <code>{nomor_pesanan}</code>.
                    </p>
                </div>

                <button type="submit" class="btn-kelana-primary w-full py-2.5 text-xs">
                    Kirim Notifikasi Sekarang
                </button>
            </div>

            {{-- Pratinjau Langsung Pesan --}}
            <div class="space-y-4 lg:col-span-5">
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Pratinjau Pesan Pelanggan</h2>
                    <div class="mt-4 rounded-2xl border border-[#efe7dc] bg-[#fdfbf6] p-4">
                        <p id="preview-title" class="text-sm font-bold text-[#28211c]">Judul notifikasi tampil di sini</p>
                        <p id="preview-message" class="mt-2 whitespace-pre-line text-xs leading-relaxed text-[#5e524a]">
                            Isi pesan pratinjau akan menyesuaikan token nama pelanggan dan nomor pesanan secara langsung.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const targetRadios = document.querySelectorAll('.notif-target-radio');
    const customerWrap = document.getElementById('notif-customer-select-wrap');
    const userSelect = document.getElementById('user_id');
    const orderSelect = document.getElementById('order_id');
    const templateSelect = document.getElementById('template_preset');
    const titleInput = document.getElementById('notif_title');
    const messageInput = document.getElementById('notif_message');
    const categorySelect = document.getElementById('category');

    const syncTargetVisibility = () => {
        const target = document.querySelector('.notif-target-radio:checked')?.value || 'single';
        customerWrap.classList.toggle('hidden', target === 'broadcast');
    };

    const updatePreview = () => {
        const selectedTarget = document.querySelector('.notif-target-radio:checked')?.value || 'single';
        const customerName = selectedTarget === 'broadcast'
            ? 'Pelanggan Setia'
            : (userSelect.selectedOptions[0]?.dataset.name || 'Pelanggan');
        const orderNumber = orderSelect.selectedOptions[0]?.dataset.number || 'BC-1040';

        document.getElementById('preview-title').textContent = titleInput.value.trim() || 'Judul notifikasi tampil di sini';
        const rawMsg = messageInput.value.trim() || 'Isi pesan pratinjau akan muncul di sini...';
        document.getElementById('preview-message').textContent = rawMsg
            .replaceAll('{nama_pelanggan}', customerName)
            .replaceAll('{nomor_pesanan}', orderNumber);
    };

    targetRadios.forEach((r) => r.addEventListener('change', () => {
        syncTargetVisibility();
        updatePreview();
    }));

    templateSelect.addEventListener('change', () => {
        const opt = templateSelect.selectedOptions[0];
        if (!opt || !opt.value) {
            return;
        }
        titleInput.value = opt.dataset.title || '';
        messageInput.value = opt.dataset.message || '';
        if (opt.dataset.category) {
            categorySelect.value = opt.dataset.category;
        }
        updatePreview();
    });

    [userSelect, orderSelect, titleInput, messageInput].forEach((el) => {
        el.addEventListener('input', updatePreview);
        el.addEventListener('change', updatePreview);
    });

    syncTargetVisibility();
    updatePreview();
});
</script>
@endpush

