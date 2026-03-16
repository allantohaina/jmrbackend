<?php

namespace App\Infrastructure\History\Persistence;

use App\Domain\History\TokenHistoryEvent;
use App\Models\TokenHistoryModel;

class TokenHistoryRepository implements \App\Domain\History\TokenHistoryRepository
{
    private readonly TokenHistoryModel $model;

    public function __construct(
        ?TokenHistoryModel $model = null
    ) {
        $this->model = $model ?? new TokenHistoryModel();
    }

    public function save(TokenHistoryEvent $event): void
    {
        $this->model->insert([
            'id' => $event->id,
            'user_id' => $event->userId,
            'action' => $event->action,
            'jti' => $event->jti,
            'refresh_token_id' => $event->refreshTokenId,
            'meta' => $event->meta ? json_encode($event->meta) : null,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'created_at' => $event->createdAt,
        ]);
    }
}
