<?php

use App\Mail\MailInvoiceToClient;
use App\Models\Job;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use App\Events\WhatsappNotificationEvent;
use App\Enums\WhatsappMessageTemplateEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('sendInvoicePayToClient')) {
    function sendInvoicePayToClient($id, $docurl, $docnum, $inv_id)
    {
        $job = Job::query()->with('client')->find($id);
        $job = $job->toArray();

        $data = array(
            'docurl' => $docurl,
            'docnum' => $docnum,
            'id'     => $id,
            'job'    => $job,
            'inv_id' => $inv_id
        );
        // $pdf = PDF::loadView('InvoicePdf', compact('invoice'));

        Mail::to($data['job']['client']['email'])->send(new MailInvoiceToClient($data));
    }
}

if (!function_exists('sendWhatsappMessage')) {
    function sendWhatsappMessage($number, $data = array(), $lang = 'he')
    {
        $mobile_no = $number;
        $mobile_no = str_replace("-", "", "$mobile_no");

        if (strlen($mobile_no) > 10) {
            $mobile_no = $mobile_no;
        } else {
            $mobile_no = '972' . $mobile_no;
        }
        
        $response = Http::withToken(config('services.whapi.token'))
            ->post(config('services.whapi.url') . 'messages/text', [
                'to' => $mobile_no,
                'body' => str_replace("\t", "", $data['message'])
            ]);
        Log::info($response->json());
        if($response->successful()) { 
            return 'message sent successfully.';
        } else {
            return $response->object();
        }
    }
}

if (!function_exists('sendWorkerWhatsappMessage')) {
    function sendWorkerWhatsappMessage($number, $data = array(), $lang = 'he')
    {
        $mobile_no = $number;
        $mobile_no = str_replace("-", "", "$mobile_no");

        if (strlen($mobile_no) > 10) {
            $mobile_no = $mobile_no;
        } else {
            $mobile_no = '972' . $mobile_no;
        }
        
        $response = Http::withToken(config('services.whapi.worker_token'))
            ->post(config('services.whapi.url') . 'messages/text', [
                'to' => $mobile_no,
                'body' => str_replace("\t", "", $data['message'])
            ]);
        Log::info($response->json());
        if($response->successful()) { 
            return 'message sent successfully.';
        } else {
            return $response->object();
        }
    }
}

if (!function_exists('sendJobWANotification')) {
    function sendJobWANotification($emailData)
    {
        if (isset($emailData['job']['worker']) && !empty($emailData['job']['worker']['phone'])) {
            event(new WhatsappNotificationEvent([
                "type" => WhatsappMessageTemplateEnum::NEW_JOB,
                "notificationData" => $emailData
            ]));
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key)
    {
        $value =  Setting::where('key', $key)->first();

        $return = (!is_null($value)) ? $value->value : '';

        return $return;
    }
}

class FPDF extends TCPDF
{
}
