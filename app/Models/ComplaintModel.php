<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplaintModel extends Model
{
    protected $table = 'complaints';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'ticket_number',
        'session_id',
        'subscriber_id',
        'category',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';


    /**
     * Find an existing open/in-progress duplicate complaint.
     *
     * Same session + subscriber + category + description
     * will be considered a duplicate if the complaint is
     * currently open or in-progress.
     */
    public function findOpenDuplicate(
        int $sessionId,
        string $subscriberId,
        string $category,
        string $description
    ): ?array {

        return $this
            ->where('session_id', $sessionId)
            ->where('subscriber_id', trim($subscriberId))
            ->where('category', trim($category))
            ->where('description', trim($description))
            ->groupStart()
                ->where('status', 'open')
                ->orWhere('status', 'in-progress')
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();
    }


    /**
     * Find complaint by ticket number.
     */
    public function findByTicket(string $ticketNumber): ?array
    {
        return $this
            ->where('ticket_number', trim($ticketNumber))
            ->first();
    }


    /**
     * Update complaint status by ticket number.
     */
    public function updateStatusByTicket(
        string $ticketNumber,
        string $status
    ): bool {

        $allowedStatuses = [
            'open',
            'in-progress',
            'resolved',
            'closed',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }

        return $this
            ->where('ticket_number', trim($ticketNumber))
            ->set('status', $status)
            ->update();
    }
}