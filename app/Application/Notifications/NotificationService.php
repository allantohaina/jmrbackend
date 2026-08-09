<?php

namespace App\Application\Notifications;

use App\Models\NotificationModel;
use Throwable;

class NotificationService
{
    public function create(string $recipientId, string $event, string $title, string $message, string $entityType, ?string $entityId = null, ?string $actionUrl = null, ?string $actorId = null, string $type = 'info'): void
    {
        try {
            (new NotificationModel())->insert([
                'recipient_user_id' => $recipientId,
                'actor_user_id' => $actorId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'event' => $event,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
            ]);
        } catch (Throwable $e) {
            // Business operations remain available while a migration is pending.
            log_message('error', 'Notification could not be created: ' . $e->getMessage());
        }
    }
}
