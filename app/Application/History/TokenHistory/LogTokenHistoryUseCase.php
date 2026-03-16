<?php

namespace App\Application\History\TokenHistory;

use App\Domain\History\TokenHistoryEvent;
use App\Domain\History\TokenHistoryRepository;

class LogTokenHistoryUseCase
{
    public function __construct(
        private readonly TokenHistoryRepository $repository
    ) {
    }

    public function execute(LogTokenHistoryInput $input): void
    {
        $event = TokenHistoryEvent::create(
            $input->action,
            $input->userId,
            $input->jti,
            $input->refreshTokenId,
            $input->meta,
            $input->ipAddress,
            $input->userAgent
        );

        $this->repository->save($event);
    }
}
