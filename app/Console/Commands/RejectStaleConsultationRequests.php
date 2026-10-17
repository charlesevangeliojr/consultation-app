<?php

namespace App\Console\Commands;

use App\Support\ProposedConsultationRequests;
use Illuminate\Console\Command;

class RejectStaleConsultationRequests extends Command
{
    protected $signature = 'consultations:reject-stale-requests';
    protected $description = 'Reject unanswered proposed-time requests that have expired or conflict with accepted consultations';

    public function handle(): int
    {
        $count = ProposedConsultationRequests::reconcile();
        $this->info("Rejected {$count} stale proposed-time requests.");
        return self::SUCCESS;
    }
}
