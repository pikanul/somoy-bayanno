<?php

namespace App\Models;

use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'type',
    'original_name',
    'storage_disk',
    'path',
    'external_url',
    'mime_type',
    'file_size',
    'width',
    'height',
    'alt_text',
    'caption',
    'credit',
    'photographer',
    'source_name',
    'source_url',
    'copyright',
    'uploaded_by',
])]
class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): ?string
    {
        if ($this->path === null || $this->storage_disk === null) {
            return $this->external_url;
        }

        return Storage::disk($this->storage_disk)->url($this->path);
    }
}
