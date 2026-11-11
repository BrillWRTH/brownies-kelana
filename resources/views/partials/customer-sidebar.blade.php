@php
    $sidebarUser = auth()->user();
@endphp

<aside class="w-full shrink-0 lg:w-60">
    <div class="overflow-hidden rounded-2xl border border-[#e0cdb6] bg-[#ede0cd] p-5 shadow-xs">
        <div class="flex items-center gap-3 border-b border-[#dcc7ae] pb-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#b9b0a5] text-sm font-bold text-white">
                {{ strtoupper(substr($sidebarUser->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-[#2f2118]">{{ $sidebarUser->name }}</p>
                <p class="truncate text-xs text-[#6f5d50]">{{ $sidebarUser->email }}</p>
            </div>
        </div>

        <nav class="mt-4 space-y-1.5 text-xs font-semibold">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-2.5 rounded-full px-4 py-2.5 transition {{ request()->routeIs('dashboard') || request()->routeIs('orders.*') ? 'bg-[#593822] text-white shadow-xs' : 'text-[#593822] hover:bg-[#e3d2b8]' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('notifications.index') }}"
                class="flex items-center justify-between rounded-full px-4 py-2.5 transition {{ request()->routeIs('notifications.*') ? 'bg-[#593822] text-white shadow-xs' : 'text-[#593822] hover:bg-[#e3d2b8]' }}">
                <span class="flex items-center gap-2.5">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span>Notifikasi</span>
                </span>
                @if(($sharedCustomerUnreadCount ?? 0) > 0)
                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $sharedCustomerUnreadCount }}</span>
                @endif
            </a>

            <a href="{{ route('profile.show') }}"
                class="flex items-center gap-2.5 rounded-full px-4 py-2.5 transition {{ request()->routeIs('profile.*') ? 'bg-[#593822] text-white shadow-xs' : 'text-[#593822] hover:bg-[#e3d2b8]' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                <span>Profil Saya</span>
            </a>

            <button type="button" data-open-modal="customer-logout-modal"
                class="flex w-full items-center gap-2.5 rounded-full px-4 py-2.5 text-left text-[#6f5d50] transition hover:bg-red-100/70 hover:text-red-700">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
                <span>Keluar</span>
            </button>
        </nav>
    </div>
</aside>
