<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $employeeName,
        public string $employeeNumber,
        public string $invitationUrl,
        public CarbonInterface $expiresAt,
    ) {}

    public function build(): self
    {
        return $this->subject('Undangan Registrasi Wajah HadirMetrik')
            ->view('emails.invitation')
            ->with([
                'name' => $this->employeeName,
                'employeeNumber' => $this->employeeNumber,
                'invitationUrl' => $this->invitationUrl,
                'expiresAt' => $this->expiresAt,
                'logoUrl' => asset('images/logo.png'),
            ]);
    }
}
