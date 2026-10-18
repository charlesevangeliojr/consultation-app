<?php

namespace App\Console\Commands;

use App\Support\AccountActivity;
use Illuminate\Console\Command;

class DeactivateInactiveStudents extends Command
{
    protected $signature = 'accounts:deactivate-inactive';
    protected $description = 'Mark student accounts inactive after two years without authenticated activity';

    public function handle(): int
    {
        $count = AccountActivity::deactivateDormantStudents();
        $this->info("Marked {$count} student accounts inactive.");

        return self::SUCCESS;
    }
}
