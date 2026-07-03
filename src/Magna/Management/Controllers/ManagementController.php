<?php

declare(strict_types=1);

namespace Magna\Management\Controllers;

use Illuminate\Routing\Controller;

/**
 * Base class for management API controllers.
 * Provides shared helpers used across all resource controllers.
 */
abstract class ManagementController extends Controller
{
    /** Return the authenticated actor's ULID as a string, or null if unavailable. */
    protected function actorId(): ?string
    {
        $id = auth()->id();
        if (is_string($id)) {
            return $id;
        }
        if (is_int($id)) {
            return (string) $id;
        }

        return null;
    }
}
