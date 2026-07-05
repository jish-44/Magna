<?php

declare(strict_types=1);

namespace Magna\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Magna\Media\ConversionPreset;
use Magna\Media\ConversionPresetRegistry;
use Magna\Media\Media;
use Magna\Media\MediaConversion;
use Magna\Settings\MediaSettings;

class ProcessMediaConversionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $mediaId,
        private readonly string $presetName,
    ) {}

    public function handle(ConversionPresetRegistry $presets): void
    {
        $media = Media::withTrashed()->find($this->mediaId);
        if ($media === null) {
            return;
        }

        $preset = $presets->get($this->presetName);
        if ($preset === null) {
            return;
        }

        if (! $media->isImage()) {
            return;
        }

        $originalContent = Storage::disk($media->disk)->get($media->path);
        if ($originalContent === null) {
            Log::warning('ProcessMediaConversionJob: source file missing from storage.', [
                'media_id' => $this->mediaId,
                'path' => $media->path,
            ]);

            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'magna_conv_');
        if ($tempPath === false) {
            throw new \RuntimeException('Could not allocate temp file for media conversion.');
        }

        try {
            file_put_contents($tempPath, $originalContent);
            $this->generateVariants($media, $preset, $tempPath);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function generateVariants(Media $media, ConversionPreset $preset, string $sourcePath): void
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($sourcePath);

        if ($preset->fit) {
            $image->cover($preset->width, $preset->height);
        } else {
            $image->scaleDown(width: $preset->width, height: $preset->height);
        }

        $outputWidth = $image->width();
        $outputHeight = $image->height();
        $basePath = 'media/conversions/'.$this->mediaId.'/'.$this->presetName;

        $settings = MediaSettings::get();

        if ($preset->generateWebP && $settings->webp_enabled) {
            $content = (string) $image->toWebp($settings->default_image_quality);
            $path = $basePath.'.webp';
            Storage::disk($media->disk)->put($path, $content);

            MediaConversion::updateOrCreate(
                ['media_id' => $this->mediaId, 'preset' => $this->presetName, 'format' => 'webp'],
                ['path' => $path, 'width' => $outputWidth, 'height' => $outputHeight, 'size' => strlen($content)],
            );
        }

        if ($preset->generateAvif && $settings->avif_enabled) {
            try {
                $content = (string) $image->toAvif($settings->default_image_quality);
                $path = $basePath.'.avif';
                Storage::disk($media->disk)->put($path, $content);

                MediaConversion::updateOrCreate(
                    ['media_id' => $this->mediaId, 'preset' => $this->presetName, 'format' => 'avif'],
                    ['path' => $path, 'width' => $outputWidth, 'height' => $outputHeight, 'size' => strlen($content)],
                );
            } catch (\Throwable $e) {
                // AVIF encoding is best-effort: skip gracefully if the GD build lacks libavif.
                Log::info('AVIF conversion skipped: GD driver does not support AVIF on this host.', [
                    'media_id' => $this->mediaId,
                    'preset' => $this->presetName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
