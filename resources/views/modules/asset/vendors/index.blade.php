<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                Vendor & Teknisi Langganan
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Kontak tukang AC, bengkel motor/mobil, tukang ledeng, listrik, dan servis langganan
            </p>
        </div>
        <a href="{{ route('assets.vendors.create') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
            + Tambah Vendor
        </a>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-stone-200 pb-3 dark:border-stone-800">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📦 Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🔧 Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📄 Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🛠️ Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
            👨‍🔧 Vendor / Teknisi ({{ $totalCount }})
        </a>
    </div>

    @if($vendors->isEmpty())
        <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center dark:border-stone-800">
            <span class="text-4xl">👨‍🔧</span>
            <p class="mt-2 text-sm text-stone-500">Belum ada vendor atau teknisi yang dicatat.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($vendors as $vendor)
                <a href="{{ route('assets.vendors.show', $vendor->id) }}" class="group flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-xs transition hover:border-emerald-500 hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-base font-semibold text-stone-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">
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
                                    <span class="rounded-md bg-stone-100 px-2 py-0.5 text-[11px] text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                        {{ $srv }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 space-y-1 text-xs text-stone-500">
                            @if($vendor->whatsapp || $vendor->phone)
                                <div>📞 {{ $vendor->whatsapp ?: $vendor->phone }}</div>
                            @endif
                            @if($vendor->address)
                                <div class="line-clamp-1">📍 {{ $vendor->address }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-stone-100 pt-3 text-xs text-stone-500 dark:border-stone-800">
                        <span>{{ $vendor->service_records_count }} kali servis</span>
                        <span class="font-medium text-emerald-600 group-hover:underline">Detail →</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
