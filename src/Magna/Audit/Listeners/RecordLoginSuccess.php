<?php

declare(strict_types=1);

namespace Magna\Audit\Listeners;

use Illuminate\Auth\Events\Login;
use Magna\Audit\AuditLog;

class RecordLoginSuccess
{
    public function handle(Login $event): void
    {
        $id = $event->user->getAuthIdentifier();

        AuditLog::record(
            action: 'auth.login.success',
            actorId: match (true) {
                is_string($id) => $id,
                is_int($id) => (string) $id,
                default => null,
            },
            actorType: 'user',
            ip: request()->ip(),
        );
    }
}
