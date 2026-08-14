<?php

namespace App\Application\Notifications;

use App\Models\PushSubscriptionModel;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushService
{
    private ?WebPush $webPush = null;

    private function webPush(): WebPush
    {
        if ($this->webPush === null) {
            $this->webPush = new WebPush([
                'VAPID' => [
                    'subject' => getenv('VAPID_SUBJECT') ?: 'mailto:contact@jmrtextile.com',
                    'publicKey' => getenv('VAPID_PUBLIC_KEY'),
                    'privateKey' => getenv('VAPID_PRIVATE_KEY'),
                ],
            ], [
                'TTL' => 4 * 24 * 3600, // 4 days
                'urgency' => 'normal',
                'batchSize' => 100,
            ]);
        }

        return $this->webPush;
    }

    /**
     * Send a push notification to every subscription of the given user.
     */
    public function sendToUser(string $recipientUserId, string $title, string $message, ?string $actionUrl = null, ?string $icon = null): int
    {
        $subscriptions = (new PushSubscriptionModel())->where('user_id', $recipientUserId)->findAll();
        if (empty($subscriptions)) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $message,
            'url' => $actionUrl ?? '/mon-profil',
            'icon' => $icon ?? '/favicon.svg',
        ], JSON_UNESCAPED_UNICODE);

        $sent = 0;
        $failed = [];

        try {
            $webPush = $this->webPush();

            foreach ($subscriptions as $subscription) {
                $pushSubscription = new Subscription(
                    $subscription['endpoint'],
                    $subscription['keys_p256dh'],
                    $subscription['keys_auth'],
                );
                $webPush->queueNotification($pushSubscription, $payload);
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $failed[] = $report->getEndpoint();
                    if ($report->isSubscriptionExpired()) {
                        $this->removeByEndpoint($report->getEndpoint());
                    }
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Web push send failed: ' . $e->getMessage());
        }

        if (!empty($failed)) {
            log_message('error', 'Web push failed endpoints: ' . json_encode($failed));
        }

        return $sent;
    }

    private function removeByEndpoint(string $endpoint): void
    {
        try {
            (new PushSubscriptionModel())->where('endpoint', $endpoint)->delete();
        } catch (Throwable $e) {
            log_message('error', 'Could not remove expired push subscription: ' . $e->getMessage());
        }
    }
}