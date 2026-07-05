<?php

declare(strict_types=1);

namespace Magna\Admin\Resources\Media;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Magna\Admin\Resources\MediaResource;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    /**
     * After Filament stores the uploaded file, populate the Media model fields
     * (disk, path, filename, mime_type, size, width, height) from the stored file.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $storedPath = is_array($data['file'] ?? null)
            ? ($data['file'][0] ?? null)
            : ($data['file'] ?? null);

        if (! $storedPath) {
            return $data;
        }

        $disk = 'public';
        $fullPath = Storage::disk($disk)->path($storedPath);

        $mime = function_exists('mime_content_type')
            ? (mime_content_type($fullPath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        $size = is_file($fullPath) ? (int) filesize($fullPath) : 0;

        $data['disk'] = $disk;
        $data['path'] = $storedPath;
        $data['filename'] = basename($storedPath);
        $data['original_filename'] = basename($storedPath);
        $data['mime_type'] = $mime;
        $data['size'] = $size;

        if (str_starts_with($mime, 'image/') && is_file($fullPath)) {
            [$width, $height] = @getimagesize($fullPath) ?: [null, null];
            $data['width'] = $width;
            $data['height'] = $height;
        }

        unset($data['file']);

        return $data;
    }
}
