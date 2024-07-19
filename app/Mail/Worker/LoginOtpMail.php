<?php

namespace App\Mail\Worker;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $user;
    
     

    public function __construct($otp,$user)
    {
        $this->otp = $otp;
        $this->user = $user;
        App::setLocale($user->lng);

    }
   

    public function build()
    {
        return $this->view('Mails.worker.loginOtp')
                    ->subject(__('mail.otp.subject'))
                    ->with([
                        'otp' => $this->otp,
                        'user' => $this->user
                    ]);
    }
}
