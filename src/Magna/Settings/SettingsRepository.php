<?php

declare(strict_types=1);

namespace Magna\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Magna\Audit\AuditLog;
use Magna\Settings\Attributes\Secret;
use ReflectionClass;
use ReflectionProperty;

class SettingsRepository
{
    private const CACHE_TAG = 'magna-settings';

    public function hydrate(Settings $instance): void
    {
        $group = $instance::group();

        /** @var array<string, mixed> $stored */
        $stored = Cache::tags([self::CACHE_TAG])->remember(
            "magna-settings:{$group}",
            now()->addHour(),
            fn (): array => Setting::query()
                ->where('group', $group)
                ->get()
                ->mapWithKeys(fn (Setting $s): array => [$s->key => $s->value])
                ->all(),
        );

        $ref = new ReflectionClass($instance);

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $key = $prop->getName();

            if (! array_key_exists($key, $stored)) {
                continue;
            }

            $rawValue = $stored[$key];
            $isSecret = $prop->getAttributes(Secret::class) !== [];

            if ($isSecret && is_string($rawValue)) {
                $decryptedJson = Crypt::decryptString($rawValue);
                /** @var mixed $phpValue */
                $phpValue = json_decode($decryptedJson, true);
            } else {
                /** @var mixed $phpValue */
                $phpValue = $rawValue;
            }

            $prop->setValue($instance, $phpValue);
        }
    }

    public function persist(Settings $instance): void
    {
        $group = $instance::group();
        $ref = new ReflectionClass($instance);

        $before = $this->auditData($group, $ref, maskSecrets: true);

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $key = $prop->getName();
            /** @var mixed $phpValue */
            $phpValue = $prop->getValue($instance);
            $isSecret = $prop->getAttributes(Secret::class) !== [];

            $storedValue = $isSecret
                ? Crypt::encryptString(json_encode($phpValue, JSON_THROW_ON_ERROR))
                : $phpValue;

            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $storedValue],
            );
        }

        Cache::tags([self::CACHE_TAG])->forget("magna-settings:{$group}");

        $after = $this->auditData($group, $ref, maskSecrets: true);
        $actorId = Auth::id();

        AuditLog::record(
            action: 'settings.changed',
            actorId: $actorId !== null ? (string) $actorId : null,
            actorType: $actorId !== null ? 'user' : 'system',
            ip: request()->ip(),
            before: $before,
            after: $after,
        );
    }

    /**
     * @param  ReflectionClass<Settings>  $ref
     * @return array<string, mixed>
     */
    private function auditData(string $group, ReflectionClass $ref, bool $maskSecrets): array
    {
        /** @var array<string, mixed> $stored */
        $stored = Setting::query()
            ->where('group', $group)
            ->get()
            ->mapWithKeys(fn (Setting $s): array => [$s->key => $s->value])
            ->all();

        $result = [];

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $key = $prop->getName();
            $isSecret = $prop->getAttributes(Secret::class) !== [];

            if ($maskSecrets && $isSecret) {
                $result[$key] = '[secret]';
            } elseif (array_key_exists($key, $stored)) {
                $result[$key] = $stored[$key];
            }
        }

        return $result;
    }
}
