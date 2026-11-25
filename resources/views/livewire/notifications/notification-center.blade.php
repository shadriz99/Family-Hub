<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
                {{ __('Pusat Notifikasi') }}
            </h1>
            <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
                {{ __('Kelola dan lihat pemberitahuan aktivitas serta pengingat keluarga.') }}
            </p>
        </div>

        @if ($unreadCount > 0)
            <div>
                <button type="button"
                    wire:click="markAllAsRead"
                    class="inline-flex items-center gap-2 rounded-2xl border border-current/15 bg-white/70 px-4 py-2 text-sm font-medium transition-colors hover:bg-white dark:bg-[#1f2c25] dark:hover:bg-[#283830]">
                    <x-heroicon-o-check-circle class="size-4" />
                    <span>{{ __('Tandai Semua Dibaca') }}</span>
                </button>
            </div>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <x-empty-state
            :title="__('Belum Ada Notifikasi')"
            :description="__('Semua pemberitahuan dan pengingat yang masuk akan ditampilkan di sini.')" />
    @else
        <div class="space-y-3">
            @foreach ($notifications as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    $data = $notification->data;
                    $title = $data['title'] ?? __('Pemberitahuan');
                    $body = $data['body'] ?? null;
                    $url = $data['url'] ?? null;
                @endphp
                <div class="group relative flex items-start justify-between gap-4 rounded-2xl border p-4 transition-all {{ $isUnread ? 'border-[#059669]/30 bg-[#059669]/5 dark:border-[#10b981]/30 dark:bg-[#10b981]/10' : 'border-current/10 bg-[#fffefa] dark:bg-[#1f2c25]' }}">
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            @if ($isUnread)
                                <span class="size-2 rounded-full bg-[#059669] dark:bg-[#10b981]" aria-label="{{ __('Belum dibaca') }}"></span>
                            @endif
                            <h2 class="font-medium text-sm sm:text-base {{ $isUnread ? 'font-semibold text-current' : 'text-current/80' }}">
                                {{ $title }}
                            </h2>
                            <span class="text-xs text-[#637067] dark:text-[#b2beb2]">
                                • {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>

                        @if ($body)
                            <p class="text-xs leading-relaxed text-[#637067] sm:text-sm dark:text-[#b2beb2]">
                                {{ $body }}
                            </p>
                        @endif

                        @if ($url)
                            <div class="pt-1">
                                <a href="{{ $url }}"
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-[#059669] hover:underline dark:text-[#10b981]">
                                    <span>{{ __('Lihat detail') }}</span>
                                    <x-heroicon-m-arrow-top-right-on-square class="size-3.5" />
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-1">
                        @if ($isUnread)
                            <button type="button"
                                wire:click="markAsRead('{{ $notification->id }}')"
                                aria-label="{{ __('Tandai sudah dibaca') }}"
                                title="{{ __('Tandai sudah dibaca') }}"
                                class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-current/60 transition-colors hover:bg-current/10 hover:text-current focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current">
                                <x-heroicon-o-check class="size-4" />
                            </button>
                        @endif
                        <button type="button"
                            wire:click="deleteNotification('{{ $notification->id }}')"
                            aria-label="{{ __('Hapus notifikasi') }}"
                            title="{{ __('Hapus notifikasi') }}"
                            class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-current/40 transition-colors hover:bg-red-500/10 hover:text-red-600 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current">
                            <x-heroicon-o-trash class="size-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
