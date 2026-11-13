<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\ViewField;

/**
 * Field gabungan untuk path aset gambar:
 *  - input teks (bisa isi path manual, mis. /avatars/lab1.png atau URL lengkap)
 *  - tombol upload (upload file ke public disk & path diisi otomatis)
 *  - preview gambar langsung (ikut berubah saat diketik)
 *
 * Menyimpan string path/URL persis seperti yang diketik/dihasilkan, sehingga
 * kompatibel dengan data lama.
 */
class ImagePathUpload extends ViewField
{
    protected string $view = 'filament.forms.components.image-path-upload';

    protected string | Closure | null $directory = 'uploads';

    protected string | Closure | null $placeholder = null;

    public function directory(string | Closure $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function getDirectory(): string
    {
        return $this->evaluate($this->directory) ?? 'uploads';
    }

    public function placeholder(string | Closure $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }
}
