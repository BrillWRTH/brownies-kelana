@extends('layouts.admin')

@section('title', 'Ubah Detail Pesanan '.$order->order_number.' — Panel Admin')
@section('kicker', 'EDIT DETAIL PESANAN')

@section('content')
    @php
        $slots = $storeSetting->delivery_slots ?? ['09.00-11.00 WIB', '13.00-15.00 WIB'];
    @endphp

    <div class="mx-auto max-w-4xl space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-serif text-xl font-bold text-[#28211c]">Edit Informasi Pesanan {{ $order->order_number }}</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    Perbarui penerima, alamat tujuan, metode penerimaan, jadwal kirim/pickup, atau tarif ongkir sesuai konfirmasi pelanggan.
                </p>
            </div>
            <a href="{{ route('admin.orders.show', $order) }}" class="btn-kelana-outline px-4 py-2 text-xs">&larr; Detail Order</a>
        </div>

        <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-5 rounded-2xl border border-[#e8dfd3] bg-white p-6 shadow-xs">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="customer_name" class="kelana-label">Nama Pemesan *</label>
                    <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $order->customer_name) }}" required class="kelana-input text-xs">
                </div>
                <div>
                    <label for="customer_phone" class="kelana-label">Nomor WhatsApp Pemesan *</label>
                    <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone', $order->customer_phone) }}" required class="kelana-input text-xs">
                </div>
            </div>

            <div class="border-t border-[#efe7dc] pt-4">
                <label class="kelana-label">Metode Penerimaan *</label>
                <div class="mt-2 flex gap-3">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] px-4 py-2.5 text-xs font-bold">
                        <input type="radio" name="delivery_type" value="delivery" class="edit-delivery-radio text-[#52311a]"
                            @checked(old('delivery_type', $order->delivery_type) === 'delivery')>
                        <span>Diantar (Delivery)</span>
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] px-4 py-2.5 text-xs font-bold">
                        <input type="radio" name="delivery_type" value="pickup" class="edit-delivery-radio text-[#52311a]"
                            @checked(old('delivery_type', $order->delivery_type) === 'pickup')>
                        <span>Ambil Sendiri (Pickup)</span>
                    </label>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="recipient_name" class="kelana-label">Nama Penerima *</label>
                    <input id="recipient_name" name="recipient_name" type="text" value="{{ old('recipient_name', $order->recipient_name) }}" required class="kelana-input text-xs">
                </div>
                <div>
                    <label for="recipient_phone" class="kelana-label">HP Penerima Utama *</label>
                    <input id="recipient_phone" name="recipient_phone" type="text" value="{{ old('recipient_phone', $order->recipient_phone) }}" required class="kelana-input text-xs">
                </div>
                <div>
                    <label for="recipient_secondary_phone" class="kelana-label">HP Penerima Cadangan</label>
                    <input id="recipient_secondary_phone" name="recipient_secondary_phone" type="text"
                        value="{{ old('recipient_secondary_phone', $order->recipient_secondary_phone) }}" class="kelana-input text-xs">
                </div>
            </div>

            {{-- Field Delivery --}}
            <div id="edit-delivery-section" class="space-y-4">
                <div>
                    <label for="delivery_address" class="kelana-label">Alamat Pengiriman *</label>
                    <textarea id="delivery_address" name="delivery_address" rows="2" class="kelana-input text-xs">{{ old('delivery_address', $order->delivery_address) }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="delivery_address_detail" class="kelana-label">Detail / Patokan Alamat</label>
                        <input id="delivery_address_detail" name="delivery_address_detail" type="text"
                            value="{{ old('delivery_address_detail', $order->delivery_address_detail) }}" class="kelana-input text-xs">
                    </div>
                    <div>
                        <label for="maps_url" class="kelana-label">Tautan Google Maps</label>
                        <input id="maps_url" name="maps_url" type="text" value="{{ old('maps_url', $order->maps_url) }}" class="kelana-input text-xs">
                    </div>
                </div>
            </div>

            {{-- Field Pickup --}}
            <div id="edit-pickup-section" class="hidden">
                <label for="pickup_location_id" class="kelana-label">Titik Lokasi Pickup *</label>
                <select id="pickup_location_id" name="pickup_location_id" class="kelana-input text-xs">
                    <option value="">-- Pilih Lokasi Pickup --</option>
                    @foreach($pickupLocations as $pickup)
                        <option value="{{ $pickup->id }}" @selected((string) old('pickup_location_id', $order->pickup_location_id) === (string) $pickup->id)>
                            {{ $pickup->name }} — {{ $pickup->address }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 border-t border-[#efe7dc] pt-4 sm:grid-cols-3">
                <div>
                    <label for="scheduled_date" class="kelana-label">Tanggal Jadwal *</label>
                    <input id="scheduled_date" name="scheduled_date" type="date"
                        value="{{ old('scheduled_date', $order->scheduled_date?->toDateString()) }}" required class="kelana-input text-xs">
                </div>
                <div>
                    <label for="scheduled_time_slot" class="kelana-label">Slot Jam *</label>
                    <select id="scheduled_time_slot" name="scheduled_time_slot" required class="kelana-input text-xs">
                        @foreach($slots as $slot)
                            <option value="{{ $slot }}" @selected(old('scheduled_time_slot', $order->scheduled_time_slot) === $slot)>{{ $slot }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="shipping_fee" class="kelana-label">Biaya Ongkir (Rp) *</label>
                    <input id="shipping_fee" name="shipping_fee" type="number" min="0"
                        value="{{ old('shipping_fee', $order->shipping_fee) }}" required class="kelana-input text-xs">
                </div>
            </div>

            <div>
                <label for="customer_note" class="kelana-label">Catatan Pesanan</label>
                <textarea id="customer_note" name="customer_note" rows="2" class="kelana-input text-xs">{{ old('customer_note', $order->customer_note) }}</textarea>
            </div>

            <div>
                <label for="edit_reason" class="kelana-label">Alasan / Catatan Perubahan Admin (Tercatat di Log Audit)</label>
                <input id="edit_reason" name="edit_reason" type="text" value="{{ old('edit_reason') }}"
                    placeholder="Contoh: Perubahan jam pickup sesuai permintaan pelanggan di WA" class="kelana-input text-xs">
            </div>

            <div class="flex justify-end gap-3 border-t border-[#efe7dc] pt-4">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn-kelana-outline px-4 py-2 text-xs">Batal</a>
                <button type="submit" class="btn-kelana-primary px-5 py-2 text-xs">Simpan Perubahan Pesanan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const syncEditFields = () => {
        const type = document.querySelector('.edit-delivery-radio:checked')?.value || 'delivery';
        document.getElementById('edit-delivery-section').classList.toggle('hidden', type === 'pickup');
        document.getElementById('edit-pickup-section').classList.toggle('hidden', type !== 'pickup');
        if (type === 'pickup') {
            document.getElementById('shipping_fee').value = 0;
        }
    };
    document.querySelectorAll('.edit-delivery-radio').forEach((el) => el.addEventListener('change', syncEditFields));
    syncEditFields();
});
</script>
@endpush

