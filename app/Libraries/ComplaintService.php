<?php

namespace App\Libraries;

use App\Models\ComplaintModel;

class ComplaintService
{
    protected ComplaintModel $complaintModel;

    public function __construct()
    {
        $this->complaintModel = new ComplaintModel();
    }

    public function create(
        int $sessionId,
        string $subscriberId,
        string $category,
        string $description
    ): array {

        $subscriberId = trim($subscriberId);
        $category     = trim($category);
        $description  = trim($description);

        if (
            $sessionId <= 0 ||
            $subscriberId === '' ||
            $category === '' ||
            $description === ''
        ) {
            throw new \InvalidArgumentException(
                'Invalid complaint data.'
            );
        }

        $duplicate = $this->complaintModel->findOpenDuplicate(
            $sessionId,
            $subscriberId,
            $category,
            $description
        );

        if ($duplicate) {

            return [
                'success'       => true,
                'duplicate'     => true,
                'id'            => (int) $duplicate['id'],
                'ticket_number' => $duplicate['ticket_number'],
                'status'        => $duplicate['status'],
            ];
        }

        $ticketNumber = $this->generateTicketNumber();

        $data = [
            'ticket_number' => $ticketNumber,
            'session_id'     => $sessionId,
            'subscriber_id'  => $subscriberId,
            'category'       => $category,
            'description'    => $description,
            'status'         => 'open',
        ];

        $inserted = $this->complaintModel->insert($data);

        if (!$inserted) {

            log_message(
                'error',
                '[ComplaintService] Complaint insert failed: ' .
                json_encode($this->complaintModel->errors())
            );

            throw new \RuntimeException(
                'Complaint could not be inserted.'
            );
        }

        $insertId = $this->complaintModel->getInsertID();

        return [
            'success'       => true,
            'duplicate'     => false,
            'id'            => (int) $insertId,
            'ticket_number' => $ticketNumber,
            'status'        => 'open',
        ];
    }

    protected function generateTicketNumber(): string
    {
        $year = date('Y');

        $lastComplaint = $this->complaintModel
            ->like(
                'ticket_number',
                'TATA-' . $year . '-',
                'after'
            )
            ->orderBy('id', 'DESC')
            ->first();

        if (!$lastComplaint) {

            $number = 1;

        } else {

            $lastTicket = $lastComplaint['ticket_number'];

            $parts = explode('-', $lastTicket);

            $number = isset($parts[2])
                ? ((int) $parts[2]) + 1
                : 1;
        }

        return 'TATA-' .
            $year .
            '-' .
            str_pad(
                (string) $number,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    public function getByTicket(
        string $ticketNumber
    ): ?array {

        return $this->complaintModel
            ->where(
                'ticket_number',
                trim($ticketNumber)
            )
            ->first();
    }

    public function updateStatus(
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

        return $this->complaintModel
            ->where(
                'ticket_number',
                trim($ticketNumber)
            )
            ->set('status', $status)
            ->update();
    }
}