<?php
namespace App\Traits;

use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait NotifySickLeave
{
    private function sendSickLeaveNotification($sickLeave)
    {
        $sickLeaveArr = $sickLeave->toArray();
        $user = $sickLeave->user;
        App::setLocale($sickLeaveArr['user']['lng']);

        $emailSubject = __('mail.sick_leave.subject', [
            'id' => $sickLeaveArr['id']
        ]);
        
        // Mail::send('/Mails/worker/SickLeaveNotification', [
        //     'lng' =>$user->lng, 
        //     'name'=>$user->firstname,
        //     'sickLeave' => $sickLeaveArr,
        //     'status' => $sickLeave->status,
        //     'reason' => $sickLeave->rejection_comment,
        // ], function ($message) use ($user, $emailSubject) {
        //     $message->to($user->email);
        //     $message->subject($emailSubject);
        // });

        // Send WhatsApp notification
        if (!empty($user->phone)) {
            $phoneNumber = intval($user->phone);
            $results = event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::SICK_LEAVE_NOTIFICATION,
                "notificationData" => [
                    'user' => $sickLeaveArr['user'], // Pass the user data
                    'sickleave' => $sickLeaveArr
                ],
                "phone" => $phoneNumber
            ]));
          foreach ($results as $result) {
                if ($result) {
                    return $result;
                }
            }
           
        }
    }
}
