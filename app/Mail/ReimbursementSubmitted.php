<?php

namespace App\Mail;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReimbursementSubmitted extends Mailable
{
    public Reimbursement $reimbursement;
    public $manager;

    public function __construct(Reimbursement $reimbursement, $manager)
    {
        $this->reimbursement = $reimbursement;
        $this->manager = $manager;
    }

    public function build(): self
    {
        return $this->subject('New Reimbursement Submitted')
            ->view('emails.reimbursement_submitted')
            ->with([
                'reimbursement' => $this->reimbursement,
                'manager' => $this->manager,
            ]);
    }
}
