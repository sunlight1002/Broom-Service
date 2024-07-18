<?php

namespace App\Mail\Client;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $client;

    public function __construct($otp, $client)
    {
        $this->otp = $otp;
        $this->client = $client;
    }

    
    public function build()
    {
        return $this->view('Mails.client.loginOtp')
                    ->subject('Your OTP for Login')
                    ->with([
                        'otp' => $this->otp,
                        'client' => $this->client
                    ]);
    }
}
