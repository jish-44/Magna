<?php

declare(strict_types=1);

namespace Magna\Management\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Magna\Audit\AuditLog;
use Magna\Auth\Role;
use Magna\Users\User;

class UserRoleController extends ManagementController
{
    public function store(Request $request, string $user): JsonResponse
    {
        Gate::authorize('roles.manage');

        $record = User::query()->find(strtolower($user));
        if (! $record instanceof User) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $roleName = $validated['role'];
        $role = Role::query()->where('name', $roleName)->first();
        if (! $role instanceof Role) {
            return response()->json(['message' => "Role '{$roleName}' not found."], 404);
        }

        $record->assignRole($role);

        AuditLog::record(
            action: 'roles.assigned',
            actorId: $this->actorId(),
            ip: $request->ip(),
            subject: $record,
            after: ['role' => $roleName],
        );

        return response()->json(['message' => "Role '{$roleName}' assigned."]);
    }
}
