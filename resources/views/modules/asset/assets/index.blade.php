<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Header & Sub-nav --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">Inventaris Aset & Rumah</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">Peralatan, kendaraan, jadwal perawatan, dan dokumen garansi keluarga</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('assets.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                <span>+ Tambah Aset</span>
            </a>
            <a href="{{ route('assets.issues.create') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-medium text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                <span>⚠️ Lapor Kerusakan</span>
            </a>
        </div>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-stone-200 pb-3 dark:border-stone-800">
        <a href="{{ route('assets.index') }}" class="rounded-lg bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
            📦 Aset ({{ $totalCount }})
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
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            👨‍🔧 Vendor / Teknisi
        </a>
        <a href="{{ route('assets.locations') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📍 Lokasi
        </a>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-xs dark:border-stone-800 dark:bg-stone-900">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
            {{-- Search --}}
            <div class="md:col-span-2">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, merek, nopol, seri..." class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
            </div>

            {{-- Category Filter --}}
            <div>
                <select wire:model.live="category" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Location Filter --}}
            <div>
                <select wire:model.live="location" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Group By Switch --}}
            <div class="flex items-center gap-2">
                <select wire:model.live="groupBy" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                    <option value="location">Grup: Lokasi</option>
                    <option value="category">Grup: Kategori</option>
                </select>
            </div>
        </div>

        {{-- Toggle Butuh Perhatian --}}
        <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-stone-100 pt-3 dark:border-stone-800">
            <label class="flex items-center gap-2 text-xs font-medium text-stone-700 dark:text-stone-300 cursor-pointer">
                <input type="checkbox" wire:model.live="needsAttentionOnly" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                <span>⚠️ Hanya yang butuh perhatian (perawatan / dokumen / kerusakan)</span>
            </label>

            <div class="ml-auto flex items-center gap-2 text-xs text-stone-500">
                <button wire:click="$set('status', 'active')" class="rounded-lg px-2 py-1 {{ $status === 'active' ? 'bg-stone-200 font-semibold text-stone-900 dark:bg-stone-700 dark:text-white' : 'hover:bg-stone-100 dark:hover:bg-stone-800' }}">Aktif</button>
                <button wire:click="$set('status', 'all')" class="rounded-lg px-2 py-1 {{ $status === 'all' ? 'bg-stone-200 font-semibold text-stone-900 dark:bg-stone-700 dark:text-white' : 'hover:bg-stone-100 dark:hover:bg-stone-800' }}">Semua Status</button>
            </div>
        </div>
    </div>

    {{-- Asset Listing Grouped --}}
    @if($groupedAssets->isEmpty())
        <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-800 dark:bg-stone-900">
            <span class="text-4xl">📦</span>
            <h3 class="mt-3 text-base font-semibold text-stone-900 dark:text-white">Belum Ada Aset Ditemukan</h3>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Mulai catat peralatan rumah, elektronik, atau kendaraan keluarga Anda.</p>
            <div class="mt-5">
                <a href="{{ route('assets.create') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                    + Tambah Aset Pertama
                </a>
            </div>
        </div>
    @else
        <div class="space-y-8">
            @foreach($groupedAssets as $groupName => $assets)
                <div>
                    <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-stone-900 dark:text-white">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>{{ $groupName }}</span>
                        <span class="text-xs font-normal text-stone-500">({{ $assets->count() }})</span>
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($assets as $asset)
                            @php
                                $urgentStatus = $asset->urgentMaintenanceStatus();
                                $warrantyDoc = $asset->documents->first(fn($d) => $d->type->value === 'warranty');
                            @endphp
                            <a href="{{ route('assets.show', $asset->id) }}" class="group relative flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-xs transition hover:border-emerald-500 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-500">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1">
                                            <span class="text-xs font-medium text-stone-500 dark:text-stone-400">
                                                {{ $asset->category?->name ?? 'Aset' }}
                                                @if($asset->location)
                                                    · {{ $asset->location->name }}
                                                @endif
                                            </span>
                                            <h3 class="text-base font-semibold text-stone-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">
                                                {{ $asset->name }}
                                            </h3>
                                            @if($asset->brand || $asset->model)
                                                <p class="text-xs text-stone-600 dark:text-stone-400">
                                                    {{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) }}
                                                </p>
                                            @endif
                                            @if($asset->plate_number)
                                                <span class="mt-1 inline-block rounded-md bg-stone-100 px-2 py-0.5 text-xs font-mono font-semibold text-stone-800 dark:bg-stone-800 dark:text-stone-200">
                                                    {{ $asset->plate_number }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Status badge --}}
                                        <span class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $asset->status->badgeClass() }}">
                                            {{ $asset->status->label() }}
                                        </span>
                                    </div>

                                    @if($asset->current_meter !== null)
                                        <div class="mt-2 text-xs text-stone-500">
                                            Meter: <strong class="text-stone-800 dark:text-stone-200">{{ number_format($asset->current_meter, 0, ',', '.') }} {{ $asset->meter_unit?->value ?? 'km' }}</strong>
                                        </div>
                                    @endif
                                </div>

                                {{-- Badges footer: Maintenance & Warranty --}}
                                <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800">
                                    @if($urgentStatus)
                                        <span class="rounded-md px-2 py-0.5 text-[11px] font-medium {{ $urgentStatus->badgeClass() }}">
                                            🔧 {{ $urgentStatus->label() }}
                                        </span>
                                    @endif

                                    @if($warrantyDoc && $warrantyDoc->expires_on)
                                        @if($warrantyDoc->isExpired())
                                            <span class="rounded-md bg-stone-100 px-2 py-0.5 text-[11px] text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                                                Garansi habis
                                            </span>
                                        @else
                                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                Garansi s/d {{ $warrantyDoc->expires_on->translatedFormat('M Y') }}
                                            </span>
                                        @endif
                                    @endif

                                    @php
                                        $openIssuesCount = $asset->issues->whereIn('status.value', ['open', 'scheduled', 'in_progress'])->count();
                                    @endphp
                                    @if($openIssuesCount > 0)
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                            ⚠️ {{ $openIssuesCount }} Kerusakan
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
