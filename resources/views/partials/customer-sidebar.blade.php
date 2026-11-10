@php
    $sidebarUser = auth()->user();
@endphp

<aside class="w-full shrink-0 lg:w-64">
    <div class="overflow-hidden rounded-2xl border border-[#e3d5c3] bg-[#efe3cf] p-5 shadow-xs">
        <div class="flex items-center gap-3 border-b border-[#dfd0bc] pb-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#52311a] text-sm font-bold text-white">
                {{ strtoupper(substr($sidebarUser->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-[#28211c]">{{ $sidebarUser->name }}</p>
                <p class="truncate text-xs text-[#796b62]">{{ $sidebarUser->email }}</p>
            </div>
        </div>

        <nav class="mt-4 space-y-1.5 text-xs font-semibold">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-2.5 rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('dashboard') ? 'bg-[#52311a] text-white' : 'text-[#52311a] hover:bg-[#e5d4bb]' }}">
                <span>Dashboard</span>
            </a>
            <a href="{{ route('notifications.index') }}"
                class="flex items-center justify-between rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('notifications.*') ? 'bg-[#52311a] text-white' : 'text-[#52311a] hover:bg-[#e5d4bb]' }}">
                <span>Notifikasi</span>
                @if(($sharedCustomerUnreadCount ?? 0) > 0)
                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $sharedCustomerUnreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile.show') }}"
                class="flex items-center gap-2.5 rounded-xl px-3.5 py-2.5 transition {{ request()->routeIs('profile.*') ? 'bg-[#52311a] text-white' : 'text-[#52311a] hover:bg-[#e5d4bb]' }}">
                <span>Profil Saya</span>
            </a>
            <button type="button" data-open-modal="customer-logout-modal"
                class="flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-left text-red-700 transition hover:bg-red-100/70">
                <span>Keluar</span>
            </button>
        </nav>
    </div>
</aside>

