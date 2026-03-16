<?php

namespace App\Domain\History;

interface TokenHistoryRepository
{
    public function save(TokenHistoryEvent $event): void;
}
