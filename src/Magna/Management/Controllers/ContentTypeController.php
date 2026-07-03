<?php

declare(strict_types=1);

namespace Magna\Management\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Magna\Audit\AuditLog;
use Magna\Content\ContentType;
use Magna\Content\Exceptions\SchemaException;
use Magna\Content\Field;
use Magna\Content\FieldTypeRegistry;
use Magna\Content\Models\ContentTypeRecord;
use Magna\Content\SchemaRegistry;
use Magna\Content\SchemaSyncer;

class ContentTypeController extends ManagementController
{
    public function __construct(
        private readonly SchemaRegistry $schema,
        private readonly SchemaSyncer $syncer,
        private readonly FieldTypeRegistry $fieldTypes,
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize('settings.view');

        /** @var array<string, ContentType> $types */
        $types = $this->schema->all();

        return response()->json([
            'data' => array_values(array_map(fn (ContentType $t): array => $this->typeToArray($t), $types)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('settings.manage');

        /** @var array<string, mixed> $body */
        $body = $request->all();

        try {
            $type = ContentType::fromArray($body, $this->fieldTypes);
        } catch (SchemaException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($this->schema->get($type->handle) !== null) {
            return response()->json(['message' => "Content type '{$type->handle}' already exists."], 409);
        }

        $this->schema->register($type);

        ContentTypeRecord::create([
            'handle' => $type->handle,
            'display_name' => $type->displayName,
            'is_database_defined' => true,
            'schema' => $body,
        ]);

        $this->syncer->syncAll($this->schema, allowDestructive: false);

        AuditLog::record(
            action: 'content_type.created',
            actorId: $this->actorId(),
            ip: $request->ip(),
            after: $this->typeToArray($type),
        );

        return response()->json(['data' => $this->typeToArray($type)], 201);
    }

    public function show(string $handle): JsonResponse
    {
        Gate::authorize('settings.view');

        $type = $this->schema->get($handle);
        if ($type === null) {
            return response()->json(['message' => "Content type '{$handle}' not found."], 404);
        }

        return response()->json(['data' => $this->typeToArray($type)]);
    }

    public function update(Request $request, string $handle): JsonResponse
    {
        Gate::authorize('settings.manage');

        if ($this->schema->get($handle) === null) {
            return response()->json(['message' => "Content type '{$handle}' not found."], 404);
        }

        /** @var array<string, mixed> $body */
        $body = $request->all();
        $body['handle'] = $handle;

        try {
            $type = ContentType::fromArray($body, $this->fieldTypes);
        } catch (SchemaException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $record = ContentTypeRecord::query()->where('handle', $handle)->first();
        if ($record instanceof ContentTypeRecord) {
            $record->schema = $body;
            $record->save();
        }

        $this->schema->register($type);

        $allowDestructive = (bool) $request->input('allow_destructive', false);
        $this->syncer->syncAll($this->schema, allowDestructive: $allowDestructive);

        AuditLog::record(
            action: 'content_type.updated',
            actorId: $this->actorId(),
            ip: $request->ip(),
            after: $this->typeToArray($type),
        );

        return response()->json(['data' => $this->typeToArray($type)]);
    }

    /** @return array<string, mixed> */
    private function typeToArray(ContentType $type): array
    {
        return [
            'handle' => $type->handle,
            'display_name' => $type->displayName,
            'localizable' => $type->localizable,
            'draftable' => $type->draftable,
            'fields' => array_map(fn (Field $f): array => [
                'handle' => $f->handle,
                'type' => $f->type->typeName(),
                'required' => $f->required,
            ], $type->fields),
        ];
    }
}
