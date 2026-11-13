<x-filament-panels::page>
    @php
        $levels = $this->levels();
        $groupKeys = $this->groupKeys();
        $level = $this->currentLevel();
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- ─── Panel kiri: pilih level & grup ─────────────────────────── --}}
        <div class="space-y-4 xl:col-span-4">
            <x-filament::section>
                <x-slot name="heading">Pilih Level</x-slot>

                <select
                    wire:model.live="levelId"
                    class="fi-input block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                >
                    @foreach ($levels as $item)
                        @php $dialogs = is_array($item->scene_dialogs) ? $item->scene_dialogs : []; @endphp
                        <option value="{{ $item->id }}">
                            {{ $item->title }} ({{ $item->level_key }}) — {{ count($dialogs) }} grup
                        </option>
                    @endforeach
                </select>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Grup Dialog</x-slot>
                <x-slot name="description">Key grup dipakai game sebagai pemicu, mis. <code>scene7_1_item</code>.</x-slot>

                @if (count($groupKeys))
                    <div class="flex flex-col gap-1">
                        @foreach ($groupKeys as $key)
                            @php
                                $count = is_array($level?->scene_dialogs[$key] ?? null) ? count($level->scene_dialogs[$key]) : 0;
                                $active = $key === $this->groupKey;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectGroup('{{ $key }}')"
                                @class([
                                    'flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-left text-sm transition',
                                    'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500 dark:bg-primary-500/10 dark:text-primary-400' => $active,
                                    'border-gray-200 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5' => ! $active,
                                ])
                            >
                                <span class="font-mono text-xs font-semibold">{{ $key }}</span>
                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold',
                                    'bg-primary-600 text-white' => $active,
                                    'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => ! $active,
                                ])>{{ $count }} baris</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Level ini belum punya scene dialog. Buat grup pertama di bawah.
                    </p>
                @endif

                <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
                    <input
                        type="text"
                        wire:model="newGroupKey"
                        wire:keydown.enter="createGroup"
                        placeholder="nama key baru, mis. scene7_6_enter"
                        class="fi-input block w-full min-w-0 rounded-lg border-0 bg-white px-3 py-2 font-mono text-xs shadow-sm ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                    />
                    <x-filament::button size="sm" icon="heroicon-m-plus" wire:click="createGroup">
                        Buat
                    </x-filament::button>
                </div>

                @if ($this->groupKey)
                    <div class="mt-3 flex justify-end">
                        <x-filament::button
                            size="sm"
                            color="danger"
                            icon="heroicon-m-trash"
                            outlined
                            wire:click="deleteGroup"
                            wire:confirm="Hapus grup dialog &quot;{{ $this->groupKey }}&quot; beserta semua barisnya?"
                        >
                            Hapus grup ini
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading"><x-filament::icon icon="heroicon-o-information-circle" class="inline h-4 w-4" /> Cara pakai</x-slot>
                <ul class="list-disc space-y-1 pl-5 text-xs text-gray-600 dark:text-gray-300">
                    <li>Pilih level, lalu pilih grup dialog yang ingin diedit.</li>
                    <li>Klik <strong>+ Tambah baris dialog</strong> untuk menambah baris baru.</li>
                    <li>Ubah <strong>karakter</strong>, <strong>isi dialog</strong>, <strong>sisi bicara</strong>, dan <strong>avatar</strong> (ketik path atau upload).</li>
                    <li>Gunakan panah pada tiap baris untuk mengubah urutan.</li>
                    <li>Jangan lupa klik <strong>Simpan</strong> di bawah.</li>
                </ul>
            </x-filament::section>
        </div>

        {{-- ─── Panel kanan: form repeater ─────────────────────────────── --}}
        <div class="xl:col-span-8">
            @if ($this->groupKey)
                <form wire:submit="save">
                    {{ $this->form }}

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <x-filament::button type="submit" icon="heroicon-m-check" size="lg">
                            Simpan Dialog “{{ $this->groupKey }}”
                        </x-filament::button>
                    </div>
                </form>
            @else
                <x-filament::section>
                    <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <x-filament::icon icon="heroicon-o-chat-bubble-bottom-center-text" class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Pilih atau buat grup dialog dulu di panel kiri.
                        </p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
