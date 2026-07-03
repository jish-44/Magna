<?php

declare(strict_types=1);

namespace Magna\Audit;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Magna\Audit\Console\AuditExportCommand;
use Magna\Audit\Listeners\RecordLoginFailure;
use Magna\Audit\Listeners\RecordLoginSuccess;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Login::class, RecordLoginSuccess::class);
        Event::listen(Failed::class, RecordLoginFailure::class);

        $this->commands([AuditExportCommand::class]);
    }
}
