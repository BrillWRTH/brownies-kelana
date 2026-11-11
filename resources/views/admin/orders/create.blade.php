@extends('layouts.admin')

@section('title', 'Catat Pesanan Manual WhatsApp — Panel Admin')
@section('kicker', 'PESANAN MANUAL WHATSAPP')

@section('content')
    @php
        $catalogPayload = $products->map(function ($product): array {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'base_price' => (int) $product->base_price,
                'allows_custom_note' => (bool) $product->allows_custom_note,
                'variants' => $product->variants->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => (int) $variant->price,
                ])->values()->all(),
            ];
        })->values()->all();

        $oldItems = old('items', [
            ['product_id' => $products->first()?->id, 'product_variant_id' => $products->first()?->variants->first()?->id, 'quantity' => 1, 'custom_note' => ''],
        ]);
        $slots = $storeSetting->delivery_slots ?? ['09.00-11.00 WIB', '13.00-15.00 WIB'];
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-serif text-xl font-bold text-[#28211c]">Buat Pesanan Manual WhatsApp</h1>
                <p class="mt-1 text-xs text-[#796b62]">
                    Catat pesanan pelanggan yang masuk lewat chat WhatsApp agar ikut terhitung di rekap produksi &amp; kuota harian.
                </p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn-kelana-outline px-4 py-2 text-xs">&larr; Kembali</a>
        </div>

        <form method="POST" action="{{ route('admin.orders.store') }}" class="grid gap-6 lg:grid-cols-12">
            @csrf

            <div class="space-y-5 lg:col-span-8">
                {{-- 1. Data Pemesan & Tautan Akun Pelanggan --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">1. Identitas Pemesan</h2>

                    <div class="mt-4">
                        <label for="user_id" class="kelana-label">Tautkan Akun Pelanggan Terdaftar (Opsional)</label>
                        <select id="user_id" name="user_id" class="kelana-input text-xs">
                            <option value="">-- Pelanggan Baru / Cocokkan Otomatis via Nomor WA --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    data-name="{{ $customer->name }}"
                                    data-email="{{ $customer->email }}"
                                    data-phone="{{ $customer->phone }}"
                                    data-address="{{ $customer->saved_address }}"
                                    data-detail="{{ $customer->saved_address_detail }}"
                                    @selected((string) old('user_id') === (string) $customer->id)>
                                    {{ $customer->customer_code }} · {{ $customer->name }} ({{ $customer->phone ?: $customer->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="kelana-label">Nama Pemesan *</label>
                            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="customer_phone" class="kelana-label">Nomor WhatsApp Pemesan *</label>
                            <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone') }}" required placeholder="0812xxxxxxxx" class="kelana-input text-xs">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="customer_email" class="kelana-label">Email Pemesan (Opsional)</label>
                            <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" class="kelana-input text-xs">
                        </div>
                    </div>
                </div>

                {{-- 2. Item Produk Pesanan --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#52311a]">2. Daftar Item Brownies / Kue</h2>
                        <button type="button" id="btn-add-item-row" class="btn-kelana-outline px-3 py-1.5 text-xs">
                            + Tambah Produk
                        </button>
                    </div>

                    <div id="order-items-container" class="mt-4 space-y-3">
                        @foreach($oldItems as $index => $oldRow)
                            <div class="item-row rounded-xl border border-[#e8dfd3] bg-[#fdfbf6] p-3.5" data-row-index="{{ $index }}">
                                <div class="grid gap-3 sm:grid-cols-12">
                                    <div class="sm:col-span-5">
                                        <label class="kelana-label">Produk *</label>
                                        <select name="items[{{ $index }}][product_id]" class="item-product-select kelana-input py-2 text-xs" required>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" @selected((int) ($oldRow['product_id'] ?? 0) === $product->id)>
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="kelana-label">Varian</label>
                                        <select name="items[{{ $index }}][product_variant_id]" class="item-variant-select kelana-input py-2 text-xs"
                                            data-selected-variant="{{ $oldRow['product_variant_id'] ?? '' }}"></select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="kelana-label">Qty *</label>
                                        <input type="number" min="1" max="100" name="items[{{ $index }}][quantity]"
                                            value="{{ $oldRow['quantity'] ?? 1 }}" required class="item-qty-input kelana-input py-2 text-xs">
                                    </div>
                                    <div class="flex items-end justify-end sm:col-span-1">
                                        <button type="button" class="btn-remove-item rounded-lg border border-red-200 px-2.5 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
                                            &times;
                                        </button>
                                    </div>
                                    <div class="sm:col-span-12">
                                        <input type="text" name="items[{{ $index }}][custom_note]" value="{{ $oldRow['custom_note'] ?? '' }}"
                                            maxlength="100" placeholder="Catatan / tulisan di atas kue (opsional)"
                                            class="kelana-input py-1.5 text-xs">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 3. Penerimaan, Alamat / Lokasi Pickup & Jadwal H-1 --}}
                <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">3. Penerimaan &amp; Jadwal Produksi</h2>

                    <div class="mt-4 flex gap-3">
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] px-4 py-2.5 text-xs font-bold">
                            <input type="radio" name="delivery_type" value="delivery" class="delivery-type-radio text-[#52311a]"
                                @checked(old('delivery_type', 'delivery') === 'delivery')>
                            <span>Diantar (Delivery)</span>
                        </label>
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] px-4 py-2.5 text-xs font-bold">
                            <input type="radio" name="delivery_type" value="pickup" class="delivery-type-radio text-[#52311a]"
                                @checked(old('delivery_type') === 'pickup')>
                            <span>Ambil Sendiri (Pickup · Rp 0)</span>
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="recipient_name" class="kelana-label">Nama Penerima *</label>
                            <input id="recipient_name" name="recipient_name" type="text" value="{{ old('recipient_name') }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="recipient_phone" class="kelana-label">Nomor HP Penerima *</label>
                            <input id="recipient_phone" name="recipient_phone" type="text" value="{{ old('recipient_phone') }}" required class="kelana-input text-xs">
                        </div>
                    </div>

                    {{-- Bagian Delivery --}}
                    <div id="manual-delivery-fields" class="mt-4 space-y-4">
                        <div>
                            <label for="delivery_address" class="kelana-label">Alamat Pengiriman *</label>
                            <textarea id="delivery_address" name="delivery_address" rows="2" class="kelana-input text-xs">{{ old('delivery_address') }}</textarea>
                        </div>
                        <div>
                            <label for="delivery_address_detail" class="kelana-label">Patokan / Detail Alamat</label>
                            <input id="delivery_address_detail" name="delivery_address_detail" type="text" value="{{ old('delivery_address_detail') }}" class="kelana-input text-xs">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="shipping_zone_id" class="kelana-label">Pilih Zona Ongkir (Opsional)</label>
                                <select id="shipping_zone_id" name="shipping_zone_id" class="kelana-input text-xs">
                                    <option value="">Tarif Toko ({{ ucfirst($storeSetting->shipping_method) }})</option>
                                    @foreach($shippingZones as $zone)
                                        <option value="{{ $zone->id }}" data-fee="{{ $zone->fee }}" @selected((string) old('shipping_zone_id') === (string) $zone->id)>
                                            {{ $zone->name }} — Rp {{ number_format($zone->fee, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="manual_shipping_fee" class="kelana-label">Override Ongkir Manual (Rp)</label>
                                <input id="manual_shipping_fee" name="manual_shipping_fee" type="number" min="0"
                                    value="{{ old('manual_shipping_fee', $storeSetting->flat_shipping_fee) }}" class="kelana-input text-xs">
                                <p class="mt-1 text-[10px] text-[#796b62]">Kosongkan jika ingin menghitung otomatis dari metode/zona.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Bagian Pickup --}}
                    <div id="manual-pickup-fields" class="mt-4 hidden">
                        <label for="pickup_location_id" class="kelana-label">Pilih Titik Lokasi Pickup *</label>
                        <select id="pickup_location_id" name="pickup_location_id" class="kelana-input text-xs">
                            <option value="">-- Pilih Lokasi Pickup --</option>
                            @foreach($pickupLocations as $pickup)
                                <option value="{{ $pickup->id }}" @selected((string) old('pickup_location_id') === (string) $pickup->id)>
                                    {{ $pickup->name }} — {{ $pickup->address }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="scheduled_date" class="kelana-label">Tanggal Jadwal Kirim / Pickup *</label>
                            <input id="scheduled_date" name="scheduled_date" type="date"
                                value="{{ old('scheduled_date', now()->addDay()->toDateString()) }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="scheduled_time_slot" class="kelana-label">Slot Jam Pengambilan / Kirim *</label>
                            <select id="scheduled_time_slot" name="scheduled_time_slot" required class="kelana-input text-xs">
                                @foreach($slots as $slot)
                                    <option value="{{ $slot }}" @selected(old('scheduled_time_slot') === $slot)>{{ $slot }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="customer_note" class="kelana-label">Catatan Pesanan dari Chat WhatsApp</label>
                        <textarea id="customer_note" name="customer_note" rows="2" class="kelana-input text-xs">{{ old('customer_note') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Sidebar Kanan: Status Bayar Awal & Ringkasan Nominal --}}
            <div class="lg:col-span-4">
                <div class="sticky top-6 space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-[#52311a]">Ringkasan &amp; Status Bayar Awal</h2>

                    <div class="space-y-2 border-b border-[#efe7dc] pb-4 text-xs">
                        <div class="flex justify-between">
                            <span class="text-[#796b62]">Estimasi Subtotal Produk</span>
                            <strong id="summary-subtotal">Rp 0</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#796b62]">Estimasi Ongkos Kirim</span>
                            <strong id="summary-shipping">Rp 0</strong>
                        </div>
                        <div class="flex justify-between pt-2 text-sm font-bold text-[#52311a]">
                            <span>Total Tagihan</span>
                            <span id="summary-total">Rp 0</span>
                        </div>
                    </div>

                    <div>
                        <label class="kelana-label">Status Pembayaran Awal *</label>
                        <div class="space-y-2">
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-[#d6c5b3] p-3 text-xs">
                                <input type="radio" name="payment_action" value="belum_dibayar" class="mt-0.5 text-[#52311a]"
                                    @checked(old('payment_action', 'belum_dibayar') === 'belum_dibayar')>
                                <div>
                                    <span class="block font-bold text-[#28211c]">Belum Dibayar (Menunggu Transfer)</span>
                                    <span class="text-[11px] text-[#796b62]">Masuk ke antrean Menunggu Pembayaran.</span>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 text-xs">
                                <input type="radio" name="payment_action" value="lunas" class="mt-0.5 text-emerald-700"
                                    @checked(old('payment_action') === 'lunas')>
                                <div>
                                    <span class="block font-bold text-emerald-900">Sudah Lunas (Langsung Diproses)</span>
                                    <span class="text-[11px] text-emerald-700">Status langsung menjadi Diproses untuk dapur.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-kelana-primary w-full py-2.5 text-xs">
                        Simpan Pesanan Manual WhatsApp
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const products = @json($catalogPayload);
    const flatShippingFee = {{ (int) $storeSetting->flat_shipping_fee }};
    const itemsContainer = document.getElementById('order-items-container');
    const addBtn = document.getElementById('btn-add-item-row');
    const userSelect = document.getElementById('user_id');

    const formatRupiah = (amount) => 'Rp ' + Number(amount || 0).toLocaleString('id-ID');

    // Sinkronkan opsi varian pada satu baris produk
    const populateVariants = (rowEl) => {
        const productSelect = rowEl.querySelector('.item-product-select');
        const variantSelect = rowEl.querySelector('.item-variant-select');
        const product = products.find((p) => String(p.id) === String(productSelect.value));
        const previousVariant = variantSelect.dataset.selectedVariant || variantSelect.value;

        variantSelect.innerHTML = '';
        if (!product || !product.variants.length) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = `Reguler (${formatRupiah(product ? product.base_price : 0)})`;
            opt.dataset.price = product ? product.base_price : 0;
            variantSelect.appendChild(opt);
        } else {
            product.variants.forEach((variant) => {
                const opt = document.createElement('option');
                opt.value = variant.id;
                opt.textContent = `${variant.name} (${formatRupiah(variant.price)})`;
                opt.dataset.price = variant.price;
                if (String(previousVariant) === String(variant.id)) {
                    opt.selected = true;
                }
                variantSelect.appendChild(opt);
            });
        }
        variantSelect.dataset.selectedVariant = '';
    };

    // Hitung ulang ringkasan nominal tagihan
    const recalculateSummary = () => {
        let subtotal = 0;
        itemsContainer.querySelectorAll('.item-row').forEach((rowEl) => {
            const variantSelect = rowEl.querySelector('.item-variant-select');
            const selectedOption = variantSelect.options[variantSelect.selectedIndex];
            const price = selectedOption ? Number(selectedOption.dataset.price || 0) : 0;
            const qty = Math.max(1, Number(rowEl.querySelector('.item-qty-input').value || 1));
            subtotal += price * qty;
        });

        const deliveryType = document.querySelector('.delivery-type-radio:checked')?.value || 'delivery';
        let shipping = 0;
        if (deliveryType === 'delivery') {
            const manualInput = document.getElementById('manual_shipping_fee');
            const zoneSelect = document.getElementById('shipping_zone_id');
            const selectedZone = zoneSelect.options[zoneSelect.selectedIndex];
            if (manualInput.value !== '') {
                shipping = Number(manualInput.value || 0);
            } else if (selectedZone && selectedZone.dataset.fee) {
                shipping = Number(selectedZone.dataset.fee);
            } else {
                shipping = flatShippingFee;
            }
        }

        document.getElementById('summary-subtotal').textContent = formatRupiah(subtotal);
        document.getElementById('summary-shipping').textContent = formatRupiah(shipping);
        document.getElementById('summary-total').textContent = formatRupiah(subtotal + shipping);
    };

    const bindRowEvents = (rowEl) => {
        rowEl.querySelector('.item-product-select').addEventListener('change', () => {
            populateVariants(rowEl);
            recalculateSummary();
        });
        rowEl.querySelector('.item-variant-select').addEventListener('change', recalculateSummary);
        rowEl.querySelector('.item-qty-input').addEventListener('input', recalculateSummary);
        rowEl.querySelector('.btn-remove-item').addEventListener('click', () => {
            if (itemsContainer.querySelectorAll('.item-row').length <= 1) {
                return;
            }
            rowEl.remove();
            recalculateSummary();
        });
    };

    itemsContainer.querySelectorAll('.item-row').forEach((rowEl) => {
        populateVariants(rowEl);
        bindRowEvents(rowEl);
    });

    let nextRowIndex = itemsContainer.querySelectorAll('.item-row').length;
    addBtn.addEventListener('click', () => {
        const idx = nextRowIndex++;
        const optionsHtml = products.map((p) => `<option value="${p.id}">${p.name}</option>`).join('');
        const wrapper = document.createElement('div');
        wrapper.className = 'item-row rounded-xl border border-[#e8dfd3] bg-[#fdfbf6] p-3.5';
        wrapper.dataset.rowIndex = idx;
        wrapper.innerHTML = `
            <div class="grid gap-3 sm:grid-cols-12">
                <div class="sm:col-span-5">
                    <label class="kelana-label">Produk *</label>
                    <select name="items[${idx}][product_id]" class="item-product-select kelana-input py-2 text-xs" required>${optionsHtml}</select>
                </div>
                <div class="sm:col-span-4">
                    <label class="kelana-label">Varian</label>
                    <select name="items[${idx}][product_variant_id]" class="item-variant-select kelana-input py-2 text-xs"></select>
                </div>
                <div class="sm:col-span-2">
                    <label class="kelana-label">Qty *</label>
                    <input type="number" min="1" max="100" name="items[${idx}][quantity]" value="1" required class="item-qty-input kelana-input py-2 text-xs">
                </div>
                <div class="flex items-end justify-end sm:col-span-1">
                    <button type="button" class="btn-remove-item rounded-lg border border-red-200 px-2.5 py-2 text-xs font-bold text-red-700 hover:bg-red-50">&times;</button>
                </div>
                <div class="sm:col-span-12">
                    <input type="text" name="items[${idx}][custom_note]" maxlength="100" placeholder="Catatan / tulisan di atas kue (opsional)" class="kelana-input py-1.5 text-xs">
                </div>
            </div>
        `;
        itemsContainer.appendChild(wrapper);
        populateVariants(wrapper);
        bindRowEvents(wrapper);
        recalculateSummary();
    });

    // Toggle field delivery vs pickup
    const toggleDeliveryFields = () => {
        const type = document.querySelector('.delivery-type-radio:checked')?.value || 'delivery';
        document.getElementById('manual-delivery-fields').classList.toggle('hidden', type === 'pickup');
        document.getElementById('manual-pickup-fields').classList.toggle('hidden', type !== 'pickup');
        recalculateSummary();
    };
    document.querySelectorAll('.delivery-type-radio').forEach((el) => el.addEventListener('change', toggleDeliveryFields));
    document.getElementById('manual_shipping_fee').addEventListener('input', recalculateSummary);
    document.getElementById('shipping_zone_id').addEventListener('change', () => {
        const opt = document.getElementById('shipping_zone_id').selectedOptions[0];
        if (opt && opt.dataset.fee) {
            document.getElementById('manual_shipping_fee').value = opt.dataset.fee;
        }
        recalculateSummary();
    });

    // Isi otomatis identitas pelanggan saat memilih akun terdaftar
    if (userSelect) {
        userSelect.addEventListener('change', () => {
            const opt = userSelect.selectedOptions[0];
            if (!opt || !opt.value) {
                return;
            }
            document.getElementById('customer_name').value = opt.dataset.name || '';
            document.getElementById('customer_email').value = opt.dataset.email || '';
            document.getElementById('customer_phone').value = opt.dataset.phone || '';
            if (!document.getElementById('recipient_name').value) {
                document.getElementById('recipient_name').value = opt.dataset.name || '';
            }
            if (!document.getElementById('recipient_phone').value) {
                document.getElementById('recipient_phone').value = opt.dataset.phone || '';
            }
            if (!document.getElementById('delivery_address').value && opt.dataset.address) {
                document.getElementById('delivery_address').value = opt.dataset.address;
            }
            if (!document.getElementById('delivery_address_detail').value && opt.dataset.detail) {
                document.getElementById('delivery_address_detail').value = opt.dataset.detail;
            }
        });
    }

    toggleDeliveryFields();
});
</script>
@endpush

