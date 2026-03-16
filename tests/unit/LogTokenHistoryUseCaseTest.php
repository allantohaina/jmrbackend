<?php

namespace Tests\Unit;

use App\Application\History\TokenHistory\LogTokenHistoryInput;
use App\Application\History\TokenHistory\LogTokenHistoryUseCase;
use App\Domain\History\TokenHistoryEvent;
use App\Domain\History\TokenHistoryRepository;
use CodeIgniter\Test\CIUnitTestCase;

class LogTokenHistoryUseCaseTest extends CIUnitTestCase
{
    public function testItBuildsAndPersistsTokenHistoryEvent(): void
    {
        $repository = new TokenHistoryRepositorySpy();
        $useCase = new LogTokenHistoryUseCase($repository);

        $useCase->execute(new LogTokenHistoryInput(
            'refresh',
            'user-123',
            'jti-123',
            'refresh-123',
            ['rotated' => true],
            '127.0.0.1',
            'phpunit-agent'
        ));

        $this->assertCount(1, $repository->events);

        $event = $repository->events[0];
        $this->assertSame('refresh', $event->action);
        $this->assertSame('user-123', $event->userId);
        $this->assertSame('jti-123', $event->jti);
        $this->assertSame('refresh-123', $event->refreshTokenId);
        $this->assertSame(['rotated' => true], $event->meta);
        $this->assertSame('127.0.0.1', $event->ipAddress);
        $this->assertSame('phpunit-agent', $event->userAgent);
        $this->assertSame(36, strlen($event->id));
        $this->assertNotEmpty($event->createdAt);
    }
}

class TokenHistoryRepositorySpy implements TokenHistoryRepository
{
    /** @var TokenHistoryEvent[] */
    public array $events = [];

    public function save(TokenHistoryEvent $event): void
    {
        $this->events[] = $event;
    }
}
