<?php

namespace App\Application\Production;

use App\Application\Shared\Result;
use App\Models\ProductionTaskModel;
use App\Models\ProductionTaskReviewModel;
use App\Models\ProductionTicketModel;
use App\Models\QuoteModel;
use App\Models\UserModel;

/** Suivi des tickets/tâches atelier, qualification chef et contretemps (+50 % heures). */
class ProductionTaskService
{
    private ProductionTicketModel $tickets;
    private ProductionTaskModel $tasks;
    private ProductionTaskReviewModel $reviews;

    public function __construct()
    {
        $this->tickets = new ProductionTicketModel();
        $this->tasks = new ProductionTaskModel();
        $this->reviews = new ProductionTaskReviewModel();
    }

    public function ensureTicketForQuote(string $quoteId, string $commandeId, ?string $actorId = null): void
    {
        if ($this->tickets->where('quote_id', $quoteId)->first()) return;
        $this->tickets->insert([
            'quote_id' => $quoteId,
            'commande_id' => $commandeId,
            'status' => 'open',
            'created_by' => $actorId,
        ]);
        $ticketId = $this->tickets->getInsertID();
        if ($ticketId) {
            $this->tasks->insert([
                'ticket_id' => $ticketId,
                'title' => 'Production — confection',
                'description' => 'Suivi de la fabrication, contrôle qualité et finitions.',
                'status' => 'todo',
            ]);
        }
    }

    public function createTicket(array $data): Result
    {
        if (empty($data['commande_id'])) {
            return Result::fail(['error' => 'commande_id requis.'], 422);
        }
        $this->tickets->insert([
            'commande_id' => $data['commande_id'],
            'quote_id' => $data['quote_id'] ?? null,
            'status' => 'open',
            'created_by' => $data['created_by'] ?? null,
        ]);
        $ticketId = $this->tickets->getInsertID();
        if ($ticketId) {
            $this->tasks->insert([
                'ticket_id' => $ticketId,
                'title' => 'Production — confection',
                'description' => 'Suivi de la fabrication, contrôle qualité et finitions.',
                'status' => 'todo',
            ]);
        }
        return Result::created(['data' => $this->tickets->find($ticketId)]);
    }

    public function tickets(?string $quoteId = null): Result
    {
        $builder = $this->tickets->orderBy('created_at', 'DESC');
        if ($quoteId) $builder = $builder->where('quote_id', $quoteId);
        return Result::ok(['data' => $builder->findAll()]);
    }

    public function tasks(string $ticketId): Result
    {
        if (!$this->tickets->find($ticketId)) return Result::notFound('Ticket introuvable.');
        return Result::ok(['data' => $this->tasks->where('ticket_id', $ticketId)->findAll()]);
    }

    public function createTask(string $ticketId, array $data): Result
    {
        if (!$this->tickets->find($ticketId)) return Result::notFound('Ticket introuvable.');
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') return Result::fail(['error' => 'Le titre de la tâche est requis.'], 422);
        $this->tasks->insert([
            'ticket_id' => $ticketId,
            'title' => $title,
            'description' => $data['description'] ?? null,
            'assigned_worker_id' => $data['assigned_worker_id'] ?? null,
            'status' => 'todo',
            'due_at' => $data['due_at'] ?? null,
        ]);
        return Result::created(['data' => $this->tasks->find($this->tasks->getInsertID())]);
    }

    public function assignTask(string $id, ?string $workerId): Result
    {
        if (!$this->tasks->find($id)) return Result::notFound('Tâche introuvable.');
        $this->tasks->update($id, ['assigned_worker_id' => $workerId]);
        return Result::ok(['data' => $this->tasks->find($id)]);
    }

    /** Qualification du chef : note, feedback, et éventuel contretemps majorant les heures de 50 %. */
    public function reviewTask(string $id, array $data, ?string $reviewerId = null): Result
    {
        $task = $this->tasks->find($id);
        if (!$task) return Result::notFound('Tâche introuvable.');

        $estimated = isset($data['hours_estimated']) ? (float)$data['hours_estimated'] : null;
        $contretemps = !empty($data['contretemps']);
        $final = $estimated !== null ? ($contretemps ? round($estimated * 1.5, 2) : $estimated) : null;
        $quality = isset($data['quality_score']) ? (int)$data['quality_score'] : null;
        if ($quality !== null && ($quality < 0 || $quality > 20)) {
            return Result::fail(['error' => 'La note doit être comprise entre 0 et 20.'], 422);
        }
        if ($contretemps && empty($data['delay_reason'])) {
            return Result::fail(['error' => 'Un motif de contretemps est requis.'], 422);
        }

        $this->reviews->insert([
            'task_id' => $id,
            'quote_id' => $this->ticketQuoteId((string)$task['ticket_id']),
            'reviewer_id' => $reviewerId,
            'quality_score' => $quality,
            'feedback' => $data['feedback'] ?? null,
            'delay_reason' => $data['delay_reason'] ?? null,
            'contretemps' => $contretemps,
            'hours_estimated' => $estimated,
            'hours_final' => $final,
        ]);
        $this->tasks->update($id, ['status' => 'done', 'completed_at' => date('Y-m-d H:i:s')]);

        if ($contretemps) $this->notifyDelay($task);

        return Result::ok([
            'data' => $this->tasks->find($id),
            'review' => $this->reviews->find($this->reviews->getInsertID()),
        ]);
    }

    public function reviews(string $ticketId): Result
    {
        $taskIds = array_map(static fn($t) => $t['id'], $this->tasks->where('ticket_id', $ticketId)->findAll());
        if (!$taskIds) return Result::ok(['data' => []]);
        return Result::ok(['data' => $this->reviews->whereIn('task_id', $taskIds)->findAll()]);
    }

    public function closeTicket(string $id): Result
    {
        $ticket = $this->tickets->find($id);
        if (!$ticket) return Result::notFound('Ticket introuvable.');
        $this->tickets->update($id, ['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')]);
        return Result::ok(['data' => $this->tickets->find($id)]);
    }

    private function ticketQuoteId(string $ticketId): ?string
    {
        $ticket = $this->tickets->find($ticketId);
        return $ticket['quote_id'] ?? null;
    }

    private function notifyDelay(array $task): void
    {
        try {
            $quoteId = $this->ticketQuoteId((string)$task['ticket_id']);
            if (!$quoteId) return;
            $quote = (new QuoteModel())->getQuoteById($quoteId);
            if (!$quote) return;
            $userModel = new UserModel();
            $client = null;
            if (!empty($quote['client_id'])) $client = $userModel->find($quote['client_id']);
            if (!$client && !empty($quote['email'])) {
                $row = \Config\Database::connect()
                    ->query('SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1', [strtolower((string)$quote['email'])])
                    ->getRowArray();
                if ($row) $client = $row;
            }
            if (!$client) return;
            (new \App\Application\Notifications\NotificationService())->create(
                (string)$client['id'], 'quote.delay', 'Contretemps sur votre commande',
                'Un contretemps a été constaté en production (' . (string)($task['title'] ?? 'tâche') . '). Les heures estimées sont majorées de 50 %.',
                'quote', $quoteId, '/suivi-commande', null, 'warning'
            );
        } catch (\Throwable $e) {
            log_message('error', 'ProductionTaskService notifyDelay: ' . $e->getMessage());
        }
    }
}
