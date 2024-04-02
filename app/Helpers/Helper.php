<?php

namespace App\Helpers;

use App\Mail\MailInvoiceToClient;
use App\Models\Job;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class Helper
{
    public static function sendInvoicePayToClient($id, $docurl, $docnum, $inv_id)
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

    public static function sendWhatsappMessage($number, $template = '', $data = array())
    {
        $ch = curl_init();

        $mobile_no = $number;
        $mobile_no = str_replace("-", "", "$mobile_no");

        if (strlen($mobile_no) > 10) {
            $mobile_no = $mobile_no;
        } else {
            //$mobile_no = (strlen($mobile_no)==10)?substr($mobile_no, 1):$mobile_no;
            $mobile_no = '972' . $mobile_no;
            //$mobile_no = '91'.$mobile_no;
        }

        if ($template == '') {
            $params = [

                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $mobile_no,
                "type" => "text",
                "preview_url" =>  true,
                "text" => [
                    "body" =>  $data['message']

                ],

            ];
        } else {
            $params = [
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $mobile_no,
                "type" => "template",
                "template" => [
                    "name" => $template,
                    "language" => [
                        "code" => "he"
                    ],
                ]
            ];
        }

        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v16.0/' . config('services.whatsapp_api.code') . '/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . config('services.whatsapp_api.secret');
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        $data = json_decode($result, 1);

        curl_close($ch);
        if ($data && isset($data['error']) && !empty($data['error'])) {
            return $data['error'];
        } else {
            return 'message sent successfully.';
        }
    }

    public static function get_setting($key)
    {
        $value =  Setting::where('key', $key)->first();

        $return = (!is_null($value)) ? $value->value : '';

        return $return;
    }
}
