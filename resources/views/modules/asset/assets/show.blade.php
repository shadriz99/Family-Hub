<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Daftar Aset
                </a>
                <span class="text-[#637067]">/</span>
                <span class="text-xs font-medium text-[#637067] dark:text-[#637067]">{{ $asset->category?->name }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $asset->name }}
            </h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[#637067] dark:text-[#637067]">
                @if($asset->brand || $asset->model)
                    <span>{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) }}</span>
                    <span>•</span>
                @endif
                @if($asset->location)
                    <span class='inline-flex items-center gap-1'><x-heroicon-o-map-pin class="size-4 text-[#637067] dark:text-[#b2beb2]" /> {{ $asset->location->name }}</span>
                    <span>•</span>
                @endif
                @if($asset->plate_number)
                    <span class="font-mono font-semibold text-[#263c32] dark:text-[#f3f1e8]">{{ $asset->plate_number }}</span>
                    <span>•</span>
                @endif
                <span class="rounded-md px-2 py-0.5 font-medium {{ $asset->status->badgeClass() }}">
                    {{ $asset->status->label() }}
                </span>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @if($asset->meter_unit)
                <button wire:click="openMeterModal" class="inline-flex items-center gap-1.5 rounded-xl border border-current/10 bg-[#fffefa] px-3.5 py-2 text-xs font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                    ⏲️ Update Meter
                </button>
            @endif

            <a href="{{ route('assets.services.create', ['asset' => $asset->id]) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#28543e] px-3.5 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                + Catat Servis
            </a>

            <a href="{{ route('assets.edit', $asset->id) }}" class="inline-flex items-center rounded-xl border border-current/10 bg-[#fffefa] px-3 py-2 text-xs font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                <x-heroicon-o-pencil class="mr-1 inline-block size-4 align-text-bottom" /> Edit
            </a>

            @if($asset->status->value === 'active')
                <button wire:click="openDisposeModal" class="inline-flex items-center rounded-xl border border-current/10 bg-[#fffefa] px-3 py-2 text-xs font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                    Pelepasan
                </button>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex border-b border-current/10">
        <button wire:click="$set('tab', 'summary')" class="border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'summary' ? 'border-[#28543e] font-semibold text-[#28543e] dark:border-[#bad6ae] dark:text-[#bad6ae]' : 'border-transparent text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]' }}">
            Ringkasan
        </button>
        <button wire:click="$set('tab', 'maintenance')" class="border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'maintenance' ? 'border-[#28543e] font-semibold text-[#28543e] dark:border-[#bad6ae] dark:text-[#bad6ae]' : 'border-transparent text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]' }}">
            Perawatan ({{ $asset->maintenancePlans->where('is_active', true)->count() }})
        </button>
        <button wire:click="$set('tab', 'history')" class="border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'history' ? 'border-[#28543e] font-semibold text-[#28543e] dark:border-[#bad6ae] dark:text-[#bad6ae]' : 'border-transparent text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]' }}">
            Riwayat Servis ({{ $asset->serviceRecords->count() }})
        </button>
        <button wire:click="$set('tab', 'documents')" class="border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'documents' ? 'border-[#28543e] font-semibold text-[#28543e] dark:border-[#bad6ae] dark:text-[#bad6ae]' : 'border-transparent text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]' }}">
            Dokumen & Garansi ({{ $asset->documents->count() }})
        </button>
        <button wire:click="$set('tab', 'issues')" class="border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $tab === 'issues' ? 'border-[#28543e] font-semibold text-[#28543e] dark:border-[#bad6ae] dark:text-[#bad6ae]' : 'border-transparent text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]' }}">
            Kerusakan ({{ $asset->issues->count() }})
        </button>
    </div>

    {{-- Tab 1: Ringkasan --}}
    @if($tab === 'summary')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Data Spek --}}
                <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
                    <h2 class="text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-3">Informasi Perangkat</h2>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 text-xs">
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Kategori</dt>
                            <dd class="font-medium text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->category?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Lokasi</dt>
                            <dd class="font-medium text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->location?->name ?? 'Belum ditentukan' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Nomor Seri / No. Rangka</dt>
                            <dd class="font-mono text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->serial_number ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Plat Nomor</dt>
                            <dd class="font-mono font-bold text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->plate_number ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Tanggal Beli</dt>
                            <dd class="font-medium text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->purchase_date ? $asset->purchase_date->translatedFormat('d F Y') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Harga Beli</dt>
                            <dd class="font-medium text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->purchase_price ? 'Rp ' . number_format($asset->purchase_price, 0, ',', '.') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#637067] dark:text-[#637067]">Dibeli Dari</dt>
                            <dd class="font-medium text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $asset->purchased_from ?: '-' }}</dd>
                        </div>
                        @if($asset->current_meter !== null)
                            <div>
                                <dt class="text-[#637067] dark:text-[#637067]">Pembacaan Meter</dt>
                                <dd class="font-bold text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ number_format($asset->current_meter, 0, ',', '.') }} {{ $asset->meter_unit?->value ?? 'km' }} ({{ $asset->meter_updated_on ? $asset->meter_updated_on->translatedFormat('d M Y') : 'awal' }})</dd>
                            </div>
                        @endif
                    </dl>

                    @if($asset->notes)
                        <div class="mt-4 border-t border-current/10 pt-3 text-xs">
                            <span class="text-[#637067] dark:text-[#637067] block mb-1">Catatan</span>
                            <p class="text-[#263c32] dark:text-[#b2beb2] whitespace-pre-line">{{ $asset->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kolom Kanan: Biaya Servis & Status Terdekat --}}
            <div class="space-y-6">
                <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
                    <h2 class="text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-3">Total Biaya Pemeliharaan</h2>
                    <div class="space-y-3">
                        <div class="rounded-xl bg-current/5 p-3 dark:bg-current/5">
                            <span class="text-xs text-[#637067] dark:text-[#637067] block">12 Bulan Terakhir</span>
                            <span class="text-lg font-bold text-[#263c32] dark:text-[#f3f1e8] tabular-nums">
                                Rp {{ number_format($totalCostPastYear, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="rounded-xl bg-current/5 p-3 dark:bg-current/5">
                            <span class="text-xs text-[#637067] dark:text-[#637067] block">Sepanjang Masa</span>
                            <span class="text-lg font-bold text-[#28543e] dark:text-[#bad6ae] tabular-nums">
                                Rp {{ number_format($totalCostAllTime, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tab 2: Perawatan --}}
    @if($tab === 'maintenance')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">Rencana Perawatan Rutin</h2>
                <a href="{{ route('assets.plans.create', ['asset' => $asset->id]) }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    + Tambah Rencana
                </a>
            </div>

            @if($asset->maintenancePlans->isEmpty())
                <div class="rounded-2xl border border-dashed border-current/15 p-8 text-center">
                    <p class="text-sm text-[#637067]">Belum ada rencana perawatan untuk aset ini.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach($asset->maintenancePlans as $plan)
                        @php
                            $status = $plan->computedStatus();
                            $effectiveDate = $plan->effectiveDueDate();
                        @endphp
                        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]" id="plan-{{ $plan->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]">{{ $plan->name }}</h3>
                                    <p class="text-xs text-[#637067] mt-0.5">
                                        Pemicu: {{ $plan->trigger->label() }}
                                        @if($plan->interval_value)
                                            ({{ $plan->interval_value }} {{ $plan->interval_unit?->label() }})
                                        @endif
                                        @if($plan->meter_interval)
                                            ({{ number_format($plan->meter_interval, 0, ',', '.') }} km)
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-lg px-2 py-0.5 text-xs font-medium {{ $status->badgeClass() }}">
                                    {{ $status->label() }}
                                </span>
                            </div>

                            <div class="mt-3 space-y-1 text-xs text-[#637067] dark:text-[#b2beb2]">
                                @if($effectiveDate)
                                    <div>Jatuh tempo: <strong>{{ $effectiveDate->translatedFormat('d M Y') }}</strong>
                                        @if($plan->snoozed_until)
                                            <span class="text-[#87571f]">(Ditunda s/d {{ $plan->snoozed_until->translatedFormat('d M') }})</span>
                                        @endif
                                    </div>
                                @endif
                                @if($plan->next_due_meter)
                                    <div>Target meter: <strong>{{ number_format($plan->next_due_meter, 0, ',', '.') }} km</strong></div>
                                @endif
                                @if($plan->last_done_on)
                                    <div class="text-[#637067]">Terakhir dilakukan: {{ $plan->last_done_on->translatedFormat('d M Y') }}</div>
                                @endif
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-current/10 pt-3">
                                <div class="flex items-center gap-2">
                                    <button wire:click="openSnoozeModal('{{ $plan->id }}')" class="text-xs font-medium text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]">
                                        <x-heroicon-o-clock class="mr-1 inline-block size-4 align-text-bottom" /> Tunda
                                    </button>
                                    <a href="{{ route('assets.plans.edit', ['plan' => $plan->id]) }}" class="text-xs font-medium text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]">
                                        <x-heroicon-o-pencil class="mr-1 inline-block size-4 align-text-bottom" /> Edit
                                    </a>
                                    <button wire:confirm="Apakah Anda yakin ingin menghapus rencana perawatan '{{ $plan->name }}'?" wire:click="deletePlan('{{ $plan->id }}')" class="text-xs font-medium text-rose-500 hover:text-[#ad3f45] dark:hover:text-rose-400">
                                        <x-heroicon-o-trash class="mr-1 inline-block size-4 align-text-bottom" /> Hapus
                                    </button>
                                </div>
                                <a href="{{ route('assets.services.create', ['asset' => $asset->id, 'plan' => $plan->id]) }}" class="inline-flex items-center rounded-lg bg-[#28543e] px-3 py-1 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                                    Tandai Selesai
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Tab 3: Riwayat Servis --}}
    @if($tab === 'history')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">Riwayat Servis & Perbaikan</h2>
                <a href="{{ route('assets.services.create', ['asset' => $asset->id]) }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    + Catat Servis
                </a>
            </div>

            @if($asset->serviceRecords->isEmpty())
                <div class="rounded-2xl border border-dashed border-current/15 p-8 text-center">
                    <p class="text-sm text-[#637067]">Belum ada riwayat servis yang dicatat.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($asset->serviceRecords->sortByDesc('performed_on') as $rec)
                        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-md bg-current/5 px-2 py-0.5 text-xs font-medium text-[#263c32] dark:bg-current/5 dark:text-[#b2beb2]">
                                            {{ $rec->type->label() }}
                                        </span>
                                        <span class="text-xs text-[#637067]">
                                            {{ $rec->performed_on->translatedFormat('d F Y') }}
                                        </span>
                                        @if($rec->meter_reading)
                                            <span class="text-xs text-[#637067]">
                                                • {{ number_format($rec->meter_reading, 0, ',', '.') }} km
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-[#263c32] dark:text-[#f3f1e8]">
                                        {{ $rec->description ?: 'Servis ' . $asset->name }}
                                    </p>
                                    @if($rec->parts)
                                        <p class="text-xs text-[#637067] mt-0.5">Suku cadang: {{ $rec->parts }}</p>
                                    @endif
                                    @if($rec->vendor)
                                        <p class="text-xs text-[#637067] mt-0.5">Vendor: {{ $rec->vendor->name }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">
                                        Rp {{ number_format($rec->cost, 0, ',', '.') }}
                                    </span>
                                    <div class="mt-2">
                                        <button wire:click="deleteService('{{ $rec->id }}')" wire:confirm="Hapus catatan servis ini? Transaksi keuangan terkait juga akan dihapus." class="text-xs text-rose-500 hover:text-[#ad3f45]">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Tab 4: Dokumen --}}
    @if($tab === 'documents')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">Dokumen & Garansi</h2>
                <a href="{{ route('assets.documents.create', ['asset' => $asset->id]) }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    + Tambah Dokumen
                </a>
            </div>

            @if($asset->documents->isEmpty())
                <div class="rounded-2xl border border-dashed border-current/15 p-8 text-center">
                    <p class="text-sm text-[#637067]">Belum ada dokumen yang disimpan.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach($asset->documents as $doc)
                        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-xs text-[#637067]">{{ $doc->type->label() }}</span>
                                    <h3 class="text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]">{{ $doc->title }}</h3>
                                    @if($doc->number)
                                        <p class="text-xs font-mono text-[#637067] dark:text-[#637067] mt-0.5">No: {{ $doc->number }}</p>
                                    @endif
                                </div>
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $doc->status->value === 'superseded' ? 'bg-current/5 text-[#637067]' : ($doc->isExpired() ? 'bg-rose-100 text-[#ad3f45]' : 'bg-[#28543e]/15 text-[#28543e]') }}">
                                    {{ $doc->status->label() }}
                                </span>
                            </div>

                            <div class="mt-3 text-xs text-[#637067] dark:text-[#b2beb2]">
                                @if($doc->expires_on)
                                    <div>Berlaku s/d: <strong>{{ $doc->expires_on->translatedFormat('d F Y') }}</strong></div>
                                @else
                                    <div class="text-[#637067]">Tidak ada tanggal kedaluwarsa</div>
                                @endif
                                @if($doc->issuer)
                                    <div class="text-[#637067]">Penerbit: {{ $doc->issuer }}</div>
                                @endif
                            </div>

                            @if($doc->status->value === 'active')
                                <div class="mt-3 border-t border-current/10 pt-2 flex justify-end">
                                    <a href="{{ route('assets.documents.show', $doc->id) }}" class="text-xs font-medium text-[#28543e] hover:text-[#28543e]">
                                        Perpanjang / Rincian →
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Tab 5: Kerusakan --}}
    @if($tab === 'issues')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">Laporan Kerusakan</h2>
                <a href="{{ route('assets.issues.create', ['asset' => $asset->id]) }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    + Lapor Kerusakan
                </a>
            </div>

            @if($asset->issues->isEmpty())
                <div class="rounded-2xl border border-dashed border-current/15 p-8 text-center">
                    <p class="text-sm text-[#637067]">Tidak ada laporan kerusakan untuk aset ini.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($asset->issues as $issue)
                        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $issue->priority->badgeClass() }}">
                                            {{ $issue->priority->label() }}
                                        </span>
                                        <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $issue->status->badgeClass() }}">
                                            {{ $issue->status->label() }}
                                        </span>
                                    </div>
                                    <h3 class="mt-1 text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]">{{ $issue->title }}</h3>
                                    @if($issue->description)
                                        <p class="text-xs text-[#637067] dark:text-[#b2beb2] mt-1">{{ $issue->description }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('assets.issues.show', $issue->id) }}" class="text-xs font-medium text-[#28543e] hover:text-[#28543e]">
                                    Detail →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Modal Update Meter --}}
    @if($showMeterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-[#fffefa] p-6 shadow-xl dark:bg-[#1f2c25]">
                <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Update Angka Meter / Odometer</h3>
                <p class="mt-1 text-xs text-[#637067]">Aset: {{ $asset->name }}</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Angka Fisik Odometer</label>
                        <input type="number" wire:model="meterInput" placeholder="mis. 16500" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                        @error('meterInput') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Tanggal Pembacaan</label>
                        <input type="date" wire:model="meterDate" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="is_reset" wire:model="meterIsReset" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                        <label for="is_reset" class="text-xs text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                            Odometer diganti atau direset (menjaga akumulasi progres servis)
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showMeterModal', false)" class="rounded-xl border border-current/10 px-4 py-2 text-xs font-semibold text-[#263c32] hover:bg-current/5 dark:text-[#b2beb2]">
                        Batal
                    </button>
                    <button wire:click="saveMeter" class="rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] hover:opacity-90">
                        Simpan Meter
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Snooze Maintenance --}}
    @if($showSnoozeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-[#fffefa] p-6 shadow-xl dark:bg-[#1f2c25]">
                <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Tunda Jadwal Perawatan (Snooze)</h3>
                <p class="mt-1 text-xs text-[#637067]">Geser tanggal jatuh tempo berikutnya tanpa mengubah riwayat terakhir.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Tunda Berapa Hari?</label>
                        <select wire:model="snoozeDays" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                            <option value="3">3 Hari</option>
                            <option value="7">1 Minggu (7 Hari)</option>
                            <option value="14">2 Minggu (14 Hari)</option>
                            <option value="30">1 Bulan (30 Hari)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showSnoozeModal', false)" class="rounded-xl border border-current/10 px-4 py-2 text-xs font-semibold text-[#263c32] hover:bg-current/5 dark:text-[#b2beb2]">
                        Batal
                    </button>
                    <button wire:click="saveSnooze" class="rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] hover:opacity-90">
                        Tunda Jadwal
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Dispose Asset --}}
    @if($showDisposeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-[#fffefa] p-6 shadow-xl dark:bg-[#1f2c25]">
                <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Pelepasan Aset</h3>
                <p class="mt-1 text-xs text-[#637067]">Aset akan dilepas dan rencana perawatan terkait dinonaktifkan.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Status Pelepasan</label>
                        <select wire:model="disposeStatus" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                            <option value="sold">Dijual</option>
                            <option value="disposed">Dibuang</option>
                            <option value="lost">Hilang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Tanggal Pelepasan</label>
                        <input type="date" wire:model="disposeDate" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showDisposeModal', false)" class="rounded-xl border border-current/10 px-4 py-2 text-xs font-semibold text-[#263c32] hover:bg-current/5 dark:text-[#b2beb2]">
                        Batal
                    </button>
                    <button wire:click="saveDispose" class="rounded-xl bg-[#ad3f45] dark:bg-[#ffafb1] dark:text-[#151f1b] px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500">
                        Konfirmasi Pelepasan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
