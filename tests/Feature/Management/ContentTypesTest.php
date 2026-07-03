<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Magna\Auth\Role;
use Magna\Content\Models\ContentTypeRecord;
use Magna\Content\SchemaRegistry;
use Magna\Users\User;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function settingsAdminToken(): string
{
    $role = Role::factory()->create();
    $role->grant('settings.view', 'settings.manage');

    $user = User::factory()->create();
    $user->assignRole($role);

    $result = $user->createToken('mgmt', ['management'], now()->addDay());
    $result->accessToken->forceFill(['scope' => 'management'])->save();

    return $result->plainTextToken;
}

function settingsViewerToken(): string
{
    $role = Role::factory()->create();
    $role->grant('settings.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $result = $user->createToken('mgmt', ['management'], now()->addDay());
    $result->accessToken->forceFill(['scope' => 'management'])->save();

    return $result->plainTextToken;
}

// ── List ──────────────────────────────────────────────────────────────────────

it('lists registered content types', function (): void {
    $token = settingsAdminToken();

    $this->withToken($token)
        ->getJson('/api/v1/manage/content-types')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

// ── Create — generates the DB table ──────────────────────────────────────────

it('creates a content type via API and generates the DB table', function (): void {
    $token = settingsAdminToken();

    $schema = [
        'handle' => 'product',
        'displayName' => 'Product',
        'localizable' => false,
        'draftable' => false,
        'fields' => [
            ['handle' => 'name', 'type' => 'text', 'required' => true],
            ['handle' => 'price', 'type' => 'number'],
        ],
    ];

    $this->withToken($token)
        ->postJson('/api/v1/manage/content-types', $schema)
        ->assertCreated()
        ->assertJsonPath('data.handle', 'product');

    // The DB table must now exist
    expect(Schema::hasTable('magna_entries_product'))->toBeTrue();

    // A ContentTypeRecord must be persisted
    expect(ContentTypeRecord::where('handle', 'product')->exists())->toBeTrue();

    // The type must be registered in the live SchemaRegistry
    expect(app(SchemaRegistry::class)->get('product'))->not->toBeNull();
});

it('returns 409 when content type already exists', function (): void {
    $token = settingsAdminToken();

    $schema = [
        'handle' => 'duplicate',
        'displayName' => 'Duplicate',
        'localizable' => false,
        'draftable' => false,
        'fields' => [],
    ];

    $this->withToken($token)->postJson('/api/v1/manage/content-types', $schema)->assertCreated();
    $this->withToken($token)->postJson('/api/v1/manage/content-types', $schema)->assertStatus(409);
});

it('returns 403 when viewer tries to create a content type', function (): void {
    $token = settingsViewerToken();

    $this->withToken($token)
        ->postJson('/api/v1/manage/content-types', [
            'handle' => 'blog',
            'displayName' => 'Blog',
            'localizable' => false,
            'draftable' => true,
            'fields' => [],
        ])
        ->assertForbidden();
});

// ── Show ──────────────────────────────────────────────────────────────────────

it('shows a registered content type', function (): void {
    $token = settingsAdminToken();

    $this->withToken($token)
        ->postJson('/api/v1/manage/content-types', [
            'handle' => 'article',
            'displayName' => 'Article',
            'localizable' => false,
            'draftable' => true,
            'fields' => [['handle' => 'title', 'type' => 'text', 'required' => true]],
        ])
        ->assertCreated();

    $this->withToken($token)
        ->getJson('/api/v1/manage/content-types/article')
        ->assertOk()
        ->assertJsonPath('data.handle', 'article');
});

it('returns 404 for an unknown content type', function (): void {
    $token = settingsAdminToken();

    $this->withToken($token)
        ->getJson('/api/v1/manage/content-types/ghost')
        ->assertNotFound();
});

// ── Update ────────────────────────────────────────────────────────────────────

it('updates a content type and syncs the schema', function (): void {
    $token = settingsAdminToken();

    $this->withToken($token)
        ->postJson('/api/v1/manage/content-types', [
            'handle' => 'page',
            'displayName' => 'Page',
            'localizable' => false,
            'draftable' => false,
            'fields' => [['handle' => 'title', 'type' => 'text', 'required' => true]],
        ])
        ->assertCreated();

    $this->withToken($token)
        ->putJson('/api/v1/manage/content-types/page', [
            'handle' => 'page',
            'displayName' => 'Updated Page',
            'localizable' => false,
            'draftable' => false,
            'fields' => [
                ['handle' => 'title', 'type' => 'text', 'required' => true],
                ['handle' => 'body', 'type' => 'textarea'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Updated Page');

    // New column should now exist
    expect(Schema::hasColumn('magna_entries_page', 'body'))->toBeTrue();
});
