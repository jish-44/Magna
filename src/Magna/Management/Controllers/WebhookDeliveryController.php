<?php

declare(strict_types=1);

namespace Magna\Management\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Magna\Webhooks\Jobs\DispatchWebhookJob;
use Magna\Webhooks\WebhookDelivery;
use Magna\Webhooks\WebhookSubscription;

class WebhookDeliveryController extends ManagementController
{
    public function index(Request $request, string $webhook): JsonResponse
    {
        Gate::authorize('webhooks.manage');

        $sub = WebhookSubscription::find(strtolower($webhook));
        if (! $sub instanceof WebhookSubscription) {
            return response()->json(['message' => 'Webhook not found.'], 404);
        }

        $perPage = min(max($request->integer('per_page', 25), 1), 100);
        $paginator = WebhookDelivery::query()
            ->where('subscription_id', $sub->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        /** @var array<int, array<string, mixed>> $items */
        $items = collect($paginator->items())->map(
            fn (WebhookDelivery $d): array => $this->deliveryToArray($d)
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

    public function retry(Request $request, string $webhook, string $delivery): JsonResponse
    {
        Gate::authorize('webhooks.manage');

        $sub = WebhookSubscription::find(strtolower($webhook));
        if (! $sub instanceof WebhookSubscription) {
            return response()->json(['message' => 'Webhook not found.'], 404);
        }

        $record = WebhookDelivery::query()
            ->where('id', strtolower($delivery))
            ->where('subscription_id', $sub->id)
            ->first();

        if (! $record instanceof WebhookDelivery) {
            return response()->json(['message' => 'Delivery not found.'], 404);
        }

        if ($record->isDelivered()) {
            return response()->json(['message' => 'Delivery already succeeded.'], 422);
        }

        $record->forceFill(['status' => 'pending', 'attempts' => 0])->save();

        DispatchWebhookJob::dispatch($record->id);

        return response()->json(['message' => 'Delivery re-queued.', 'data' => $this->deliveryToArray($record)]);
    }

    /** @return array<string, mixed> */
    private function deliveryToArray(WebhookDelivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'subscription_id' => $delivery->subscription_id,
            'event' => $delivery->event,
            'status' => $delivery->status,
            'attempts' => $delivery->attempts,
            'last_attempt_at' => $delivery->last_attempt_at?->toIso8601String(),
            'response_code' => $delivery->response_code,
            'response_body' => $delivery->response_body,
            'created_at' => $delivery->created_at->toIso8601String(),
            'updated_at' => $delivery->updated_at->toIso8601String(),
        ];
    }
}
