<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    public $otp;
    public $admin;

    public function __construct($otp, $admin)
    {
        $this->otp = $otp;
        $this->admin = $admin;
    }

    public function build()
    {
        return $this->view('Mails.otp')
                    ->subject('Your OTP for Login')
                    ->with([
                        'otp' => $this->otp,
                        'admin' => $this->admin
                    ]);
    }
}

