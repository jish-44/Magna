<?php

declare(strict_types=1);

namespace Magna\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Magna\Media\Media;

class MediaStatsWidget extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'magna::admin.widgets.media-stats';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        // Group by mime_type prefix in PHP so the query is portable across
        // SQLite (dev) and MySQL (production) — SUBSTRING_INDEX is MySQL-only.
        $rows = DB::table('magna_media')
            ->whereNull('deleted_at')
            ->select('mime_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(size) as total_bytes'))
            ->groupBy('mime_type')
            ->get();

        /** @var array<string, array{count: int, bytes: int}> $grouped */
        $grouped = [];
        foreach ($rows as $row) {
            $cat = strstr((string) $row->mime_type, '/', before_needle: true) ?: (string) $row->mime_type;
            if (! isset($grouped[$cat])) {
                $grouped[$cat] = ['count' => 0, 'bytes' => 0];
            }
            $grouped[$cat]['count'] += (int) $row->count;
            $grouped[$cat]['bytes'] += (int) $row->total_bytes;
        }

        arsort($grouped);

        $grandTotal = array_sum(array_column($grouped, 'count'));
        $grandBytes = array_sum(array_column($grouped, 'bytes'));

        $categories = [];
        foreach ($grouped as $cat => $data) {
            $categories[] = [
                'label' => ucfirst($cat),
                'raw' => $cat,
                'count' => $data['count'],
                'bytes' => $data['bytes'],
                'pct' => $grandTotal > 0 ? round(($data['count'] / $grandTotal) * 100, 1) : 0,
                'bytesPct' => $grandBytes > 0 ? round(($data['bytes'] / $grandBytes) * 100, 1) : 0,
                'color' => self::colorFor($cat),
            ];
        }

        $trashed = Media::onlyTrashed()->count();

        return compact('categories', 'grandTotal', 'grandBytes', 'trashed');
    }

    private static function colorFor(string $category): string
    {
        return match ($category) {
            'image' => '#6366f1',
            'video' => '#f59e0b',
            'audio' => '#10b981',
            'application' => '#0ea5e9',
            'text' => '#8b5cf6',
            default => '#64748b',
        };
    }
}
