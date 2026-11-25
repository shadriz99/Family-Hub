<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                Vendor & Teknisi Langganan
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Kontak tukang AC, bengkel motor/mobil, tukang ledeng, listrik, dan servis langganan
            </p>
        </div>
        <a href="{{ route('assets.vendors.create') }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-4 py-2 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
            + Tambah Vendor
        </a>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-current/10 pb-3">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-cube class="mr-1 inline-block size-4 align-text-bottom" /> Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-wrench class="mr-1 inline-block size-4 align-text-bottom" /> Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-exclamation-circle class="mr-1 inline-block size-4 align-text-bottom" /> Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg bg-[#28543e]/10 px-3.5 py-1.5 text-sm font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
            <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> Vendor / Teknisi ({{ $totalCount }})
        </a>
    </div>

    @if($vendors->isEmpty())
        <div class="rounded-2xl border border-dashed border-current/15 p-12 text-center">
            <x-heroicon-o-wrench-screwdriver class="mx-auto size-12 text-[#637067] dark:text-[#b2beb2]" />
            <p class="mt-2 text-sm text-[#637067]">Belum ada vendor atau teknisi yang dicatat.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($vendors as $vendor)
                <a href="{{ route('assets.vendors.show', $vendor->id) }}" class="group flex flex-col justify-between rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs transition hover:border-[#28543e] dark:hover:border-[#bad6ae] hover:shadow-md dark:bg-[#1f2c25]">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-base font-semibold text-[#263c32] group-hover:text-[#28543e] dark:text-[#f3f1e8] dark:group-hover:text-[#28543e] dark:text-[#bad6ae]">
                                    {{ $vendor->name }}
                                </h3>
                                @if($vendor->rating)
                                    <div class="text-xs text-amber-500 mt-0.5">
                                        {{ str_repeat('★', $vendor->rating) }}{{ str_repeat('☆', 5 - $vendor->rating) }}
                                    </div>
                                @endif
                            </div>
                            @if($vendor->is_favorite)
                                <span class="text-sm" title="Favorit">⭐</span>
                            @endif
                        </div>

                        @if(!empty($vendor->services))
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($vendor->services as $srv)
                                    <span class="rounded-md bg-current/5 px-2 py-0.5 text-[11px] text-[#263c32] dark:bg-current/5 dark:text-[#b2beb2]">
                                        {{ $srv }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 space-y-1 text-xs text-[#637067]">
                            @if($vendor->whatsapp || $vendor->phone)
                                <div class='flex items-center gap-1'><x-heroicon-o-phone class="size-4 text-[#637067] dark:text-[#b2beb2]" /> {{ $vendor->whatsapp ?: $vendor->phone }}</div>
                            @endif
                            @if($vendor->address)
                                <div class='line-clamp-1 flex items-center gap-1'><x-heroicon-o-map-pin class="size-4 text-[#637067] dark:text-[#b2beb2]" /> {{ $vendor->address }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-current/10 pt-3 text-xs text-[#637067]">
                        <span>{{ $vendor->service_records_count }} kali servis</span>
                        <span class="font-medium text-[#28543e] group-hover:underline">Detail →</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
