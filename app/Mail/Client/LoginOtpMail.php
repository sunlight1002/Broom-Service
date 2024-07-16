<?php

namespace App\Mail\Client;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

   
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    
    public function build()
    {
        return $this->view('Mails.Client.LoginOtp')->with(['otp' => $this->otp]);
    }
}
