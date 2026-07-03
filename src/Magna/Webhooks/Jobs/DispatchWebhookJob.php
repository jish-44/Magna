<?php

declare(strict_types=1);

namespace Magna\Webhooks\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Magna\Webhooks\WebhookDelivery;
use Magna\Webhooks\WebhookSubscription;
use Throwable;

/**
 * Sends a single webhook delivery attempt.
 *
 * Up to 6 total attempts (1 initial + 5 retries) with exponential backoff.
 * Signs the payload with HMAC-SHA256 using the subscription's secret.
 * On final failure, the failed() hook marks the delivery as dead.
 */
class DispatchWebhookJob implements ShouldQueue
{
    use Queueable;

    /** Total attempts before the job is considered permanently failed. */
    public int $tries = 6;

    public function __construct(
        private readonly string $deliveryId,
    ) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::findOrFail($this->deliveryId);
        /** @var WebhookSubscription $subscription */
        $subscription = WebhookSubscription::findOrFail($delivery->subscription_id);

        $payload = json_encode($delivery->payload);
        if ($payload === false) {
            $delivery->forceFill(['status' => 'dead'])->save();

            return;
        }

        $timestamp = Carbon::now()->getTimestamp();
        $sig = 'sha256='.hash_hmac('sha256', $payload, $subscription->secret);

        $delivery->attempts = $this->attempts();
        $delivery->last_attempt_at = now();
        $delivery->status = 'failed';

        $responseCode = null;
        $responseBody = null;
        $success = false;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Magna-Signature-256' => $sig,
                    'X-Magna-Timestamp' => (string) $timestamp,
                ])
                ->withBody($payload, 'application/json')
                ->post($subscription->url);

            $responseCode = $response->status();
            $responseBody = substr($response->body(), 0, 2000);
            $success = $response->successful();
        } catch (Throwable) {
            // Connection error — leave status as 'failed', job will be retried.
        }

        $delivery->response_code = $responseCode;
        $delivery->response_body = $responseBody;

        if ($success) {
            $delivery->status = 'delivered';
        }

        $delivery->save();

        if (! $success) {
            throw new \RuntimeException("Webhook delivery to {$subscription->url} failed (attempt {$this->attempts()}).");
        }
    }

    /**
     * Exponential backoff: 60 s, 120 s, 240 s, 480 s, 960 s between retries.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 120, 240, 480, 960];
    }

    public function failed(Throwable $e): void
    {
        WebhookDelivery::where('id', $this->deliveryId)
            ->where('status', '!=', 'delivered')
            ->update(['status' => 'dead', 'updated_at' => now()]);
    }
}
