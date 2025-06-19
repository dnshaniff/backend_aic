<?php

namespace App\Jobs;

use App\Models\Reimbursement;
use App\Mail\ReimbursementSubmitted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyReimbursement implements ShouldQueue
{
    use Queueable;

    protected Reimbursement $reimbursement;

    public function __construct(Reimbursement $reimbursement)
    {
        $this->reimbursement = $reimbursement;
    }

    public function handle(): void
    {
        $managers = \App\Models\Employee::where('position', 'Manager')
            ->whereNotNull('email')
            ->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(
                new ReimbursementSubmitted($this->reimbursement, $manager)
            );
        }
    }
}
