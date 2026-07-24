<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Magna\Content\ContentServiceProvider;
use Magna\Plugins\PluginsServiceProvider;
use Magna\Settings\PerformanceServiceProvider;

/**
 * Regression guard for the "500 instead of installer on a fresh unzip" bug.
 *
 * A not-yet-installed site must boot without touching the database. On a host
 * whose PHP has no driver for the (defaulted) database connection, any query
 * during boot throws "could not find driver" and the web installer never
 * renders. These providers call Schema::hasTable() / read settings during
 * boot, so each must skip all database access until installation completes.
 *
 * Asserting an empty query log proves no connection is ever opened — which is
 * exactly what keeps a driver-less, pre-install host off the 500 page.
 */
it('core providers perform no database access before installation', function (): void {
    // Simulate a fresh, not-yet-installed deployment.
    config(['magna.installed_override' => false]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach ([
        PerformanceServiceProvider::class,
        ContentServiceProvider::class,
        PluginsServiceProvider::class,
    ] as $provider) {
        (new $provider($this->app))->boot();
    }

    expect(DB::getQueryLog())->toBeEmpty();
});
