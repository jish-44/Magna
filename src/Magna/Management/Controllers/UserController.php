<?php

declare(strict_types=1);

namespace Magna\Management\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Magna\Auth\Role;
use Magna\Settings\ApiSettings;
use Magna\Users\User;

class UserController extends ManagementController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('users.view');

        $apiSettings = ApiSettings::get();
        $perPage = min(max($request->integer('per_page', $apiSettings->default_per_page), 1), $apiSettings->max_per_page);
        $paginator = User::query()->orderByDesc('created_at')->paginate($perPage);

        /** @var array<int, array<string, mixed>> $items */
        $items = collect($paginator->items())->map(
            fn (User $u): array => $this->userToArray($u)
        )->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $user): JsonResponse
    {
        Gate::authorize('users.view');

        $record = User::query()->find(strtolower($user));
        if (! $record instanceof User) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json(['data' => $this->userToArray($record)]);
    }

    public function update(Request $request, string $user): JsonResponse
    {
        Gate::authorize('users.manage');

        $record = User::query()->find(strtolower($user));
        if (! $record instanceof User) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,suspended'],
        ]);

        $record->fill($validated);
        $record->save();

        return response()->json(['data' => $this->userToArray($record)]);
    }

    /** @return array<string, mixed> */
    private function userToArray(User $user): array
    {
        /** @var Collection<int, Role> $roles */
        $roles = $user->roles()->get();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'roles' => $roles->pluck('name')->all(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
