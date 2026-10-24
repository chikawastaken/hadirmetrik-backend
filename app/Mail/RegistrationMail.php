<?php

namespace App\Mail;

use App\Modules\Identity\Contracts\Data\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Account $account;

    public string $token;

    /**
     * Create a new message instance.
     */
    public function __construct(Account $account, string $token)
    {
        $this->account = $account;
        $this->token = $token;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verifikasi Akun HadirMetrik')
            ->view('emails.registration')
            ->with([
                'name' => $this->account->name,
                'email' => $this->account->email,
                'token' => $this->token,
                'logoUrl' => asset('images/logo.png'),
            ]);
    }
}
