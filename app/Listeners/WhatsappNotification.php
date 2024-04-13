<?php

namespace App\Listeners;

use App\Events\WhatsappNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WhatsappNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\WhatsappNotificationEvent  $event
     * @return void
     */
    public function handle(WhatsappNotificationEvent $event)
    {
        try {
            $eventData = $event;
            $eventType = $eventData->type;
            $eventData = $eventData->notificationData;
            $clientData = $eventData['client'];
            $propertyAddress = $eventData['property_address'];
            if($eventData['purpose'] == "Price offer"){
                $eventData['purpose'] =  trans('mail.meeting.price_offer');
            }else if($eventData['purpose'] == "Quality check"){
                $eventData['purpose'] =  trans('mail.meeting.quality_check');
            }else{
                $eventData['purpose'] = $eventData['purpose'];
            }
            if($eventType == "client_meeting_schedule"){
                $headers = array();
                $url = "https://graph.facebook.com/v18.0/" . config('services.whatsapp_api.from_id') . "/messages";
                $headers[] = 'Authorization: Bearer ' . config('services.whatsapp_api.auth_token');
                $headers[] = 'Content-Type: application/json';
                $params = [
                        "messaging_product"=> "whatsapp",
                        "to"=> $clientData['phone'],
                        "type"=> "template",
                        "template"=> [
                            "name"=> config('services.whatsapp_api.meeting_schedule'),
                            "language"=> [
                                "code"=> $clientData['lng'] == "heb"?'he-IL':$clientData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [       
                                    [
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ],           
                                    [
                                        "type"=> "text",
                                        "text"=>  \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')
                                    ],
                                    [
                                        "type"=> "text",
                                        "text"=> date("H:i", strtotime($eventData['start_time']))
                                    ],
                                    [
                                        "type"=> "text",
                                        "text"=> date("H:i", strtotime($eventData['end_time']))
                                    ],
                                    [
                                        "type"=> "text",
                                        "text"=> isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ?$propertyAddress['address_name']: "NA"
                                    ],
                                    [
                                        "type"=> "text",
                                        "text"=> $eventData['purpose']
                                    ],
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "meeting-schedule/".base64_encode($clientData['id'])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
                ob_start();
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                curl_exec($ch);
                curl_close($ch);
                ob_end_clean();
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
