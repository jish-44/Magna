<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Magna\Auth\Role;

/**
 * Default roles. Idempotent — safe to re-run on existing installations;
 * grants are only added, never removed, so admin customisations survive.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->updateOrCreate(['handle' => 'super-admin'], [
            'name' => 'Super Admin',
            'description' => 'Bypasses all permission checks. Assign sparingly.',
            'is_super_admin' => true,
        ]);

        $admin = Role::query()->updateOrCreate(['handle' => 'admin'], [
            'name' => 'Administrator',
            'description' => 'Full administrative access via explicit grants.',
        ]);
        $admin->grant('users.*', 'roles.*', 'settings.*', 'plugins.*', 'audit.*');

        $editor = Role::query()->updateOrCreate(['handle' => 'editor'], [
            'name' => 'Editor',
            'description' => 'Creates, edits, and publishes content and media.',
        ]);
        $editor->grant('content.*', 'media.*');

        $viewer = Role::query()->updateOrCreate(['handle' => 'viewer'], [
            'name' => 'Viewer',
            'description' => 'Read-only access to content.',
        ]);
        $viewer->grant('content.*.view');
    }
}
