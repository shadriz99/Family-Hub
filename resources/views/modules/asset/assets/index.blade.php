<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Header & Sub-nav --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Inventaris Aset & Rumah</h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Peralatan, kendaraan, jadwal perawatan, dan dokumen garansi keluarga</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('assets.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#28543e] px-4 py-2.5 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                <span>+ Tambah Aset</span>
            </a>
            <a href="{{ route('assets.issues.create') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-current/10 bg-[#fffefa] px-3.5 py-2.5 text-sm font-medium text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                <span><x-heroicon-o-exclamation-triangle class="mr-1 inline-block size-4 align-text-bottom" /> Lapor Kerusakan</span>
            </a>
        </div>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex gap-2 border-b border-current/10 pb-3 overflow-x-auto no-scrollbar sm:flex-wrap">
        <a href="{{ route('assets.index') }}" class="shrink-0 rounded-lg bg-[#28543e]/10 px-3.5 py-1.5 text-sm font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
            <x-heroicon-o-cube class="mr-1 inline-block size-4 align-text-bottom" /> Aset ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.maintenance') }}" class="shrink-0 rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-wrench class="mr-1 inline-block size-4 align-text-bottom" /> Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="shrink-0 rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="shrink-0 rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-exclamation-circle class="mr-1 inline-block size-4 align-text-bottom" /> Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="shrink-0 rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> Vendor / Teknisi
        </a>
        <a href="{{ route('assets.locations') }}" class="shrink-0 rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-map-pin class="mr-1 inline-block size-4 align-text-bottom" /> Lokasi
        </a>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="mb-6 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
            {{-- Search --}}
            <div class="md:col-span-2">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama, merek, nopol, seri..." class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] placeholder:text-[#637067] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
            </div>

            {{-- Category Filter --}}
            <div>
                <select wire:model.live="category" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Location Filter --}}
            <div>
                <select wire:model.live="location" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Group By Switch --}}
            <div class="flex items-center gap-2">
                <select wire:model.live="groupBy" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                    <option value="location">Grup: Lokasi</option>
                    <option value="category">Grup: Kategori</option>
                </select>
            </div>
        </div>

        {{-- Toggle Butuh Perhatian --}}
        <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-current/10 pt-3">
            <label class="flex items-center gap-2 text-xs font-medium text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                <input type="checkbox" wire:model.live="needsAttentionOnly" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                <span class="inline-flex items-center gap-1.5"><x-heroicon-o-exclamation-triangle class="size-4 text-[#87571f] dark:text-[#edc48b]" /> Hanya yang butuh perhatian (perawatan / dokumen / kerusakan)</span>
            </label>

            <div class="ml-auto flex items-center gap-1.5 text-xs text-[#637067]">
                <button wire:click="$set('status', 'active')" class="min-h-[36px] px-3 py-1.5 rounded-xl transition-colors {{ $status === 'active' ? 'bg-current/10 font-semibold text-[#263c32] dark:bg-current/10 dark:text-[#f3f1e8]' : 'hover:bg-current/5 ' }}">Aktif</button>
                <button wire:click="$set('status', 'all')" class="min-h-[36px] px-3 py-1.5 rounded-xl transition-colors {{ $status === 'all' ? 'bg-current/10 font-semibold text-[#263c32] dark:bg-current/10 dark:text-[#f3f1e8]' : 'hover:bg-current/5 ' }}">Semua Status</button>
            </div>
        </div>
    </div>

    {{-- Asset Listing Grouped --}}
    @if($groupedAssets->isEmpty())
        <div class="rounded-2xl border border-dashed border-current/15 bg-[#fffefa] p-12 text-center dark:bg-[#1f2c25]">
            <x-heroicon-o-cube class="mx-auto size-12 text-[#637067] dark:text-[#637067] mb-2" />
            <h3 class="mt-3 text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">Belum Ada Aset Ditemukan</h3>
            <p class="mt-1 text-sm text-[#637067] dark:text-[#637067]">Mulai catat peralatan rumah, elektronik, atau kendaraan keluarga Anda.</p>
            <div class="mt-5">
                <a href="{{ route('assets.create') }}" class="inline-flex min-h-[44px] items-center rounded-xl bg-[#28543e] px-4 py-2 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    + Tambah Aset Pertama
                </a>
            </div>
        </div>
    @else
        <div class="space-y-8">
            @foreach($groupedAssets as $groupName => $assets)
                <div>
                    <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">
                        <span class="h-2 w-2 rounded-full bg-[#28543e] dark:bg-[#bad6ae]"></span>
                        <span>{{ $groupName }}</span>
                        <span class="text-xs font-normal text-[#637067]">({{ $assets->count() }})</span>
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($assets as $asset)
                            @php
                                $urgentStatus = $asset->urgentMaintenanceStatus();
                                $warrantyDoc = $asset->documents->first(fn($d) => $d->type->value === 'warranty');
                            @endphp
                            <a href="{{ route('assets.show', $asset->id) }}" class="group relative flex flex-col justify-between rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs transition hover:border-[#28543e] dark:hover:border-[#bad6ae] hover:shadow-md dark:bg-[#1f2c25] dark:hover:border-[#28543e] dark:hover:border-[#bad6ae]">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1">
                                            <span class="text-xs font-medium text-[#637067] dark:text-[#637067]">
                                                {{ $asset->category?->name ?? 'Aset' }}
                                                @if($asset->location)
                                                    · {{ $asset->location->name }}
                                                @endif
                                            </span>
                                            <h3 class="text-base font-semibold text-[#263c32] group-hover:text-[#28543e] dark:text-[#f3f1e8] dark:group-hover:text-[#28543e] dark:text-[#bad6ae]">
                                                {{ $asset->name }}
                                            </h3>
                                            @if($asset->brand || $asset->model)
                                                <p class="text-xs text-[#637067] dark:text-[#637067]">
                                                    {{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) }}
                                                </p>
                                            @endif
                                            @if($asset->plate_number)
                                                <span class="mt-1 inline-block rounded-md bg-current/5 px-2 py-0.5 text-xs font-mono font-semibold text-[#263c32] dark:bg-current/5 dark:text-[#f3f1e8]">
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
                                        <div class="mt-2 text-xs text-[#637067]">
                                            Meter: <strong class="text-[#263c32] dark:text-[#f3f1e8]">{{ number_format($asset->current_meter, 0, ',', '.') }} {{ $asset->meter_unit?->value ?? 'km' }}</strong>
                                        </div>
                                    @endif
                                </div>

                                {{-- Badges footer: Maintenance & Warranty --}}
                                <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-current/10 pt-3">
                                    @if($urgentStatus)
                                        <span class="rounded-md px-2 py-0.5 text-[11px] font-medium {{ $urgentStatus->badgeClass() }}">
                                            <x-heroicon-o-wrench class="inline-block size-3.5 align-text-bottom" /> {{ $urgentStatus->label() }}
                                        </span>
                                    @endif

                                    @if($warrantyDoc && $warrantyDoc->expires_on)
                                        @if($warrantyDoc->isExpired())
                                            <span class="rounded-md bg-current/5 px-2 py-0.5 text-[11px] text-[#637067] dark:bg-current/5 dark:text-[#637067]">
                                                Garansi habis
                                            </span>
                                        @else
                                            <span class="rounded-md bg-[#28543e]/10 px-2 py-0.5 text-[11px] text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
                                                Garansi s/d {{ $warrantyDoc->expires_on->translatedFormat('M Y') }}
                                            </span>
                                        @endif
                                    @endif

                                    @php
                                        $openIssuesCount = $asset->issues->whereIn('status.value', ['open', 'scheduled', 'in_progress'])->count();
                                    @endphp
                                    @if($openIssuesCount > 0)
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-[#ad3f45] dark:bg-rose-950/60 dark:text-[#ffafb1]">
                                            <x-heroicon-o-exclamation-triangle class="inline-block size-3.5 align-text-bottom" /> {{ $openIssuesCount }} Kerusakan
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
