<?php
namespace App\Traits;

use App\Enums\WhatsappMessageTemplateEnum;
use App\Events\WhatsappNotificationEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait NotifyRefundClaim
{
    private function sendClaimNotification($refundClaim)
    {
        $refundClaimArr = $refundClaim->toArray();
        $user = $refundClaim->user;
        App::setLocale($refundClaimArr['user']['lng']);

        $emailSubject = __('mail.refund_claim.subject', [
            'id' => $refundClaimArr['id']
        ]);
        
        // Mail::send('/Mails/worker/RefundClaim', [
        //     'lng' =>$user->lng, 
        //     'name'=>$user->firstname,
        //     'sickLeave' => $refundClaimArr,
        //     'status' => $refundClaim->status,
        //     'reason' => $refundClaim->rejection_comment,
        // ], function ($message) use ($user, $emailSubject) {
        //     $message->to($user->email);
        //     $message->subject($emailSubject);
        // });

        // Send WhatsApp notification
        if (!empty($user->phone)) {
            $phoneNumber = intval($user->phone);
            $status = $refundClaimArr['status'];
            if($status == "approved"){
                $results = event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_APPROVED,
                    "notificationData" => [
                        'worker' => $refundClaimArr['user'], // Pass the user data
                        'refundclaim' => $refundClaimArr
                    ],
                    "phone" => $phoneNumber
                ]));
            }else{
                $results = event(new WhatsappNotificationEvent([
                    "type" => WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_REJECTED,
                    "notificationData" => [
                        'worker' => $refundClaimArr['user'], // Pass the user data
                        'refundclaim' => $refundClaimArr
                    ],
                    "phone" => $phoneNumber
                ]));
            }
          foreach ($results as $result) {
                if ($result) {
                    return $result;
                }
            }
           
        }
    }
}
