<?php

namespace App\Mail\Worker;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $user;

    public function __construct($otp,$user)
    {
        $this->otp = $otp;
        $this->user = $user;
    }


    public function build()
    {
        return $this->view('Mails.worker.loginOtp')
                    ->subject('Your OTP for Login')
                    ->with([
                        'otp' => $this->otp,
                        'user' => $this->user
                    ]);
    }
}
