@php
    $statePath = $getStatePath();
    $directory = $getDirectory();
    $placeholder = $getPlaceholder() ?? 'mis. /avatars/lab1.png, /achievements/lab.png, atau uploads/xxx.png';
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            uploading: false,
            error: null,
            previewFailed: false,
            previewUrl() {
                if (! this.state) return null;
                const s = String(this.state).trim().replace(/\\/g, '/');
                if (! s) return null;
                if (/^(https?:)?\/\//i.test(s) || /^(data|blob):/i.test(s)) return s;
                if (s.startsWith('/')) return s;
                return '/storage/' + s.replace(/^\/+/, '');
            },
            async upload(event) {
                const file = event.target.files?.[0];
                if (! file) return;
                this.uploading = true;
                this.error = null;
                this.previewFailed = false;

                const body = new FormData();
                body.append('image', file);
                body.append('directory', @js($directory));
                body.append('_token', @js(csrf_token()));

                try {
                    const res = await fetch(@js(route('admin.uploads.store')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept': 'application/json',
                        },
                        body,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok) {
                        throw new Error(data.message ?? 'Upload gagal (' + res.status + ')');
                    }
                    this.state = data.path;
                } catch (e) {
                    this.error = e.message ?? String(e);
                } finally {
                    this.uploading = false;
                    event.target.value = '';
                }
            },
        }"
        x-on:input="previewFailed = false"
        class="flex w-full flex-col gap-2"
    >
        <div class="flex w-full flex-wrap items-center gap-2">
            <input
                type="text"
                x-model="state"
                @disabled($isDisabled)
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                class="fi-input block w-full min-w-0 flex-1 rounded-lg border-0 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 disabled:bg-gray-50 disabled:text-gray-500 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500 dark:focus:ring-primary-500"
            />

            <label
                @class(['fi-btn fi-btn-size-sm inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 hover:bg-gray-50 dark:text-white dark:ring-white/20 dark:hover:bg-white/5', 'pointer-events-none opacity-60' => $isDisabled])
            >
                <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-4 w-4" />
                <span x-text="uploading ? 'Mengupload…' : 'Upload'"></span>
                <input
                    type="file"
                    accept="image/*"
                    class="hidden"
                    x-bind:disabled="uploading"
                    @disabled($isDisabled)
                    @change="upload($event)"
                />
            </label>

            <button
                type="button"
                x-show="state"
                x-cloak
                @click="state = null; previewFailed = false"
                class="rounded-lg px-2 py-2 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-950/10 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:ring-white/20 dark:hover:bg-white/5"
                title="Kosongkan path"
            >
                <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
            </button>
        </div>

        <p x-show="error" x-cloak x-text="error" class="text-sm text-danger-600 dark:text-danger-400"></p>

        <div x-show="previewUrl() && ! previewFailed" x-cloak class="flex items-center gap-3">
            <img
                x-bind:src="previewUrl()"
                x-on:error="previewFailed = true"
                alt="preview"
                class="h-24 w-24 rounded-lg border border-gray-200 object-contain bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5"
            />
            <span class="text-xs text-gray-500 dark:text-gray-400">Preview</span>
        </div>
        <p x-show="previewUrl() && previewFailed" x-cloak class="text-xs text-warning-600 dark:text-warning-400">
            Gambar tidak bisa dimuat dari path tersebut. Path tetap tersimpan.
        </p>
    </div>
</x-dynamic-component>
