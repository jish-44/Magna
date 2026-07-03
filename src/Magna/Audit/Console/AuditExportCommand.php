<?php

declare(strict_types=1);

namespace Magna\Audit\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Magna\Audit\AuditLog;

class AuditExportCommand extends Command
{
    protected $signature = 'magna:audit:export
        {--from= : Start date inclusive (Y-m-d)}
        {--to=   : End date inclusive (Y-m-d)}';

    protected $description = 'Export audit log entries as JSON lines for SIEM ingestion.';

    public function handle(): int
    {
        $query = AuditLog::query()->orderBy('created_at');

        $from = $this->option('from');
        if (is_string($from)) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }

        $to = $this->option('to');
        if (is_string($to)) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $query->chunk(500, function ($logs): void {
            /** @var Collection<int, AuditLog> $logs */
            foreach ($logs as $log) {
                $this->line(json_encode($log->toArray(), JSON_THROW_ON_ERROR));
            }
        });

        return self::SUCCESS;
    }
}
