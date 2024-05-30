<?php

namespace App\Listeners;

use App\Events\WhatsappNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Enums\WhatsappMessageTemplateEnum;

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
            
            $headers = array();
            $url = "https://graph.facebook.com/v18.0/" . config('services.whatsapp_api.from_id') . "/messages";
            $headers[] = 'Authorization: Bearer ' . config('services.whatsapp_api.auth_token');
            $headers[] = 'Content-Type: application/json';
           
            
            if($eventType == WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE){
                $clientData = $eventData['client'];
                $propertyAddress = $eventData['property_address'];
                if($eventData['purpose'] == "Price offer"){
                    $eventData['purpose'] =  trans('mail.meeting.price_offer');
                }else if($eventData['purpose'] == "Quality check"){
                    $eventData['purpose'] =  trans('mail.meeting.quality_check');
                }else{
                    $eventData['purpose'] = $eventData['purpose'];
                }
                $params = [
                        "messaging_product"=> "whatsapp",
                        "to"=> $clientData['phone'],
                        "type"=> "template",
                        "template"=> [
                            "name"=> WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                            "language"=> [
                                "code"=> $clientData['lng'] == "heb"?'he':$clientData['lng']
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
                                        "text"=> $eventData['purpose']?$eventData['purpose']:" "
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
                                        "text"=> "meeting-schedule/".base64_encode($eventData['id'])
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "1",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "meeting-files/".base64_encode($eventData['id'])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif($eventType == WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER){
                $propertyAddress = $eventData['property_address'];
                if($eventData['purpose'] == "Price offer"){
                    $eventData['purpose'] =  trans('mail.meeting.price_offer');
                }else if($eventData['purpose'] == "Quality check"){
                    $eventData['purpose'] =  trans('mail.meeting.quality_check');
                }else{
                    $eventData['purpose'] = $eventData['purpose'];
                }
                $params = [
                        "messaging_product"=> "whatsapp",
                        "to"=> $eventData['phone'],
                        "type"=> "template",
                        "template"=> [
                            "name"=> WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER,
                            "language"=> [
                                "code"=> $eventData['lng'] == "heb"?'he':$eventData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [       
                                    [
                                        "type"=> "text",
                                        "text"=> $eventData['firstname'].' '.$eventData['lastname']
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
                                        "text"=> $eventData['purpose']?$eventData['purpose']:" "
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
                                        "text"=> "meeting-schedule/".base64_encode($eventData['id'])
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "1",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "meeting-files/".base64_encode($eventData['id'])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::OFFER_PRICE) {
                $clientData = $eventData['client'];
                $service_names = isset($eventData['service_names']) ? $eventData['service_names']: ' ';
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $clientData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::OFFER_PRICE,
                        "language"=> [
                            "code"=>  $clientData['lng'] == "heb"?'he':$clientData['lng']
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
                                        "text"=> $service_names
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
                                        "text"=> "price-offer/".base64_encode($eventData['id'])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::CONTRACT) {
                $clientData = $eventData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $clientData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::CONTRACT,
                        "language"=> [
                            "code"=> $clientData['lng'] == "heb"?'he':$clientData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "work-contract/".$eventData['contract_id']
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED) {
                $jobData = $eventData['job'];
                $clientData = $jobData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $clientData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED,
                        "language"=> [
                            "code"=> $clientData['lng'] == "heb"?'he':$clientData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $clientData['firstname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $clientData['lng'] == 'heb'? $jobData['jobservice']['heb_name'] : $jobData['jobservice']['name']
                                    ]
                                    // ,[
                                    //     "type"=> "text",
                                    //     "text"=> $jobData['shifts']
                                    // ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "client/jobs/".base64_encode($jobData['id'])."/review"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::DELETE_MEETING) {
                $clientData = $eventData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $clientData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::DELETE_MEETING,
                        "language"=> [
                            "code"=> $clientData['lng'] == "heb"?'he':$clientData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> isset($eventData['team']) && !empty($eventData['team']['name']) ? "with" . $eventData['team']['name'] : ' '
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')
                                    ],[
                                        "type"=> "text",
                                        "text"=> date("H:i", strtotime($eventData['start_time']))
                                    ],[
                                        "type"=> "text",
                                        "text"=> date("H:i", strtotime($eventData['end_time']))
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::FORM101) {
                $workerData = $eventData;
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $workerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::FORM101,
                        "language"=> [
                            "code"=> $workerData['lng'] == "heb"?'he':$workerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'].' '.$workerData['lastname']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "form101/".base64_encode($workerData['id'])
                                    ]
                                ]
                            ],                                    
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::NEW_JOB) {
                $jobData = $eventData['job'];
                $workerData = $jobData['worker'];
                $clientData = $jobData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $workerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::NEW_JOB,
                        "language"=> [
                            "code"=> $workerData['lng'] == "heb"?'he':$workerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'].' '.$workerData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['content_data']?$eventData['content_data']:' '
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y') ." ".(isset($jobData['shifts'])? ("(". $jobData['shifts']. ")") :" ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $workerData['lng'] == 'heb' ? ($jobData['jobservice']['heb_name'].', ') : ($jobData['jobservice']['name'].', ')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['property_address']['address_name'] . " " . ($jobData['property_address']['parking'] ? ("[" .$jobData['property_address']['parking'] . "]" ):  " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> ucfirst($jobData['status']) 
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker/login"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_CHANGE_REQUEST) {
                $jobData = $eventData['job'];
                $adminData = $eventData['admin'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $adminData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_CHANGE_REQUEST,
                        "language"=> [
                            "code"=> 'en'
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $adminData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['client']['firstname'].' '.$jobData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['jobservice']['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=>  $jobData['property_address']['address_name']?$jobData['property_address']['address_name']: 'NA'
                                    ],[
                                        "type"=> "text",
                                        "text"=> isset($jobData['worker'])? ($jobData['worker']['firstname'].' '.$jobData['worker']['lastname']) : "NA"
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['shifts']
                                    ]
                                ]
                            ],                         
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_CONTRACT) {
                $workerData = $eventData;
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $workerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_CONTRACT,
                        "language"=> [
                            "code"=> $workerData['lng'] == "heb"?'he':$workerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'].' '.$workerData['lastname']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker-contract/".base64_encode($workerData['worker_id'])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_JOB_APPROVAL) {
                $adminData = $eventData['admin'];
                $jobData = $eventData['job'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $adminData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> $eventType,
                        "language"=> [
                            "code"=> 'en'
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $adminData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y') . " ". ($jobData['shifts'] ? (" ( ".$jobData['shifts']. " ) ") : " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['client']['firstname'] ." " . $jobData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=>  $jobData['worker']['firstname'] ." " . $jobData['worker']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> ($jobData['jobservice']['name'].', ')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['property_address']['address_name']?$jobData['property_address']['address_name']: 'NA'
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_JOB_NOT_APPROVAL) {
                $adminData = $eventData['admin'];
                $jobData = $eventData['job'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $adminData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> $eventType,
                        "language"=> [
                            "code"=> 'en'
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $adminData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y') . " ". ($jobData['shifts'] ? (" ( ".$jobData['shifts']. " ) ") : " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['client']['firstname'] ." " . $jobData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=>  $jobData['worker']['firstname'] ." " . $jobData['worker']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> ($jobData['jobservice']['name'].', ')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['property_address']['address_name']?$jobData['property_address']['address_name']: 'NA'
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "admin/jobs/".$jobData['id']."/change-worker"
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "1", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "admin/jobs/".$jobData['id']."/change-shift"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_REMIND_JOB) {
                $jobData = $eventData['job'];
                $workerData = $jobData['worker'];
                $clientData = $jobData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $workerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_REMIND_JOB,
                        "language"=> [
                            "code"=> $workerData['lng'] == "heb"?'he':$workerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'].' '.$workerData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $workerData['lng'] == 'heb' ? ($jobData['jobservice']['heb_name'].', ') : ($jobData['jobservice']['name'].', ')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['property_address']['address_name'] . " " . ($jobData['property_address']['parking'] ? ("[" .$jobData['property_address']['parking'] . "]" ):  " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['shifts']
                                    ],[
                                        "type"=> "text",
                                        "text"=> isset($jobData['start_time'])?$jobData['start_time']:" "
                                    ],[
                                        "type"=> "text",
                                        "text"=> ucfirst($jobData['status']) 
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker/".base64_encode($workerData['id'])."/jobs"."/".base64_encode($jobData['id'])."/approve"
                                    ]
                                ]
                            ],                                    
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_UNASSIGNED) {
                $jobData = $eventData['job'];
                $oldWorkerData = $eventData['old_worker'];
                $oldJobData = $eventData['old_job'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $oldWorkerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_UNASSIGNED,
                        "language"=> [
                            "code"=> $oldWorkerData['lng'] == "heb"?'he':$oldWorkerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $oldWorkerData['firstname'].' '.$oldWorkerData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($oldJobData['start_date'])->format('M d Y')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['client']['firstname'].' '.$jobData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $oldWorkerData['lng'] == 'heb' ? ($jobData['jobservice']['heb_name'].', ') : ($jobData['jobservice']['name'].', ')
                                    ],[
                                        "type"=> "text",
                                        "text"=> $oldJobData['shifts']
                                    ]
                                ]
                            ],                                 
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION) {
                $by = $eventData['by'];
                $adminData = $eventData['admin'];
                $jobData = $eventData['job'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $by == 'client' ? $adminData['phone'] : $jobData['client']['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> ($by == 'client') ? $adminData['name'] : $jobData['client']['firstname']." ".$jobData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y')  . ($jobData['start_time'] && $jobData['end_time']? (" ( ".$jobData['start_time'] ." to ". $jobData['end_time']." ) " ): " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> ($jobData['worker'] ? ($jobData['worker']['firstname'] ." " .$jobData['worker']['lastname'] ) : "NA")
                                    ],[
                                        "type"=> "text",
                                        "text"=>  ($jobData['client'] ? ($jobData['client']['firstname'] ." " .$jobData['client']['lastname'] ) : "NA")
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['jobservice']['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> ucfirst($jobData['status'])
                                    ],[
                                        "type"=> "text",
                                        "text"=> ($by == 'client'?("Client changed the Job status to ". ucfirst($jobData['status']). "." .($jobData['cancellation_fee_amount']) ? ("With Cancellation fees ".$jobData['cancellation_fee_amount'] ." ILS."): " " ):("Job is marked as ".ucfirst($jobData['status'])))
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "client/login"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_JOB_OPENING_NOTIFICATION) {
                $workerData = $eventData['worker'];
                $adminData = $eventData['admin'];
                $jobData = $eventData['job'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $adminData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_JOB_OPENING_NOTIFICATION,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $adminData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'] ." ". $workerData['lastname']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker/view-job/".$jobData['id']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "1", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "admin/view-worker/".$jobData['id']
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION) {
                $comment = $eventData['comment'];
                $adminData = $eventData['admin'];
                $jobData = $eventData['job'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $adminData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $adminData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> ucfirst($jobData['status'])
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($jobData['start_date'])->format('M d Y') . ($jobData['start_time'] && $jobData['end_time']? (" ( ".$jobData['start_time'] ." to ". $jobData['end_time']." ) " ): " ") 
                                    ],[
                                        "type"=> "text",
                                        "text"=> ($jobData['worker'] ? ($jobData['worker']['firstname'] ." " .$jobData['worker']['lastname'] ) : "NA")
                                    ],[
                                        "type"=> "text",
                                        "text"=>  ($jobData['client'] ? ($jobData['client']['firstname'] ." " .$jobData['client']['lastname'] ) : "NA")
                                    ],[
                                        "type"=> "text",
                                        "text"=> $jobData['jobservice']['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> ucfirst($jobData['status'])
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker/view-job/".$jobData["id"]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
               
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_SAFE_GEAR) {
                $workerData = $eventData;
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $workerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_SAFE_GEAR,
                        "language"=> [
                            "code"=> $workerData['lng'] == "heb"?'he':$workerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'].' '.$workerData['lastname']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0", 
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker-safe-gear/".base64_encode($workerData["id"])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING) {
                if($eventData['purpose'] == "Price offer"){
                    $eventData['purpose'] =  trans('mail.meeting.price_offer');
                }else if($eventData['purpose'] == "Quality check"){
                    $eventData['purpose'] =  trans('mail.meeting.quality_check');
                }else{
                    $eventData['purpose'] = $eventData['purpose'];
                }
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $eventData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $eventData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['client']['firstname'].' '.$eventData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time']? (" ( ".date("H:i", strtotime($eventData['start_time'])) ." to ". date("H:i", strtotime($eventData['end_time']))." ) " ): " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> isset($eventData['property_address'])?$eventData['property_address']['address_name']:'NA'
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['purpose']?$eventData['purpose']:"NA"
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['meet_link']?$eventData['meet_link']:"NA"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::TEAM_RESCHEDULE_MEETING) {
                if($eventData['purpose'] == "Price offer"){
                    $eventData['purpose'] =  trans('mail.meeting.price_offer');
                }else if($eventData['purpose'] == "Quality check"){
                    $eventData['purpose'] =  trans('mail.meeting.quality_check');
                }else{
                    $eventData['purpose'] = $eventData['purpose'];
                }
                $teamData = $eventData['team'];
                $clientData = $eventData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $teamData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::TEAM_RESCHEDULE_MEETING,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $teamData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time']? (" ( ".date("H:i", strtotime($eventData['start_time'])) ." to ". date("H:i", strtotime($eventData['end_time']))." ) " ): " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> isset($eventData['property_address'])?$eventData['property_address']['address_name']:'NA'
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['purpose']?$eventData['purpose']:"NA"
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['meet_link']?$eventData['meet_link']:"NA"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::CLIENT_RESCHEDULE_MEETING) {
                if($eventData['purpose'] == "Price offer"){
                    $eventData['purpose'] =  trans('mail.meeting.price_offer');
                }else if($eventData['purpose'] == "Quality check"){
                    $eventData['purpose'] =  trans('mail.meeting.quality_check');
                }else{
                    $eventData['purpose'] = $eventData['purpose'];
                }
                $teamData = $eventData['team'];
                $clientData = $eventData['client'];
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $clientData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::CLIENT_RESCHEDULE_MEETING,
                        "language"=> [
                            "code"=> $clientData['lng'] == "heb"?'he':$clientData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $clientData['firstname'].' '.$clientData['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $clientData['lng'] == 'heb' ? $teamData['heb_name'] : $teamData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time']? (" ( ".date("H:i", strtotime($eventData['start_time'])) ." to ". date("H:i", strtotime($eventData['end_time']))." ) " ): " ")
                                    ],[
                                        "type"=> "text",
                                        "text"=> isset($eventData['property_address'])?$eventData['property_address']['address_name']:'NA'
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['purpose']?$eventData['purpose']:"NA"
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['meet_link']?$eventData['meet_link']:"NA"
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES) {
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $eventData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $eventData['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['client']['firstname'].' '.$eventData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time']? (" ( ".date("H:i", strtotime($eventData['start_time'])) ." to ". date("H:i", strtotime($eventData['end_time']))." ) " ): " ")
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "storage/uploads/ClientFiles/".$eventData["file_name"]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::TEAM_LEAD_FILES) {
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $eventData['team']['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::TEAM_LEAD_FILES,
                        "language"=> [
                            "code"=> "en"
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $eventData['team']['name']
                                    ],[
                                        "type"=> "text",
                                        "text"=> $eventData['client']['firstname'].' '.$eventData['client']['lastname']
                                    ],[
                                        "type"=> "text",
                                        "text"=> \Carbon\Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time']? (" ( ".date("H:i", strtotime($eventData['start_time'])) ." to ". date("H:i", strtotime($eventData['end_time']))." ) " ): " ")
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "storage/uploads/ClientFiles/".$eventData["file_name"]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }elseif ($eventType == WhatsappMessageTemplateEnum::WORKER_FORMS) {
                $workerData = $eventData;
                $params = [
                    "messaging_product"=> "whatsapp",
                    "to"=> $workerData['phone'],
                    "type"=> "template",
                    "template"=> [
                        "name"=> WhatsappMessageTemplateEnum::WORKER_FORMS,
                        "language"=> [
                            "code"=> $workerData['lng'] == "heb"?'he':$workerData['lng']
                        ],
                        "components"=> [
                            [
                                "type"=> "body",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> $workerData['firstname'].' '.$workerData['lastname']
                                    ]
                                ]
                            ],
                            [
                                "type"=> "button",
                                "sub_type" => "url",
                                "index"=> "0",
                                "parameters"=> [
                                    [
                                        "type"=> "text",
                                        "text"=> "worker-forms/".base64_encode($workerData['id'])
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];
            }
            // \Log::info($eventType);
            // \Log::info($params);
            ob_start();
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            $r = curl_exec($ch);
            // \Log::info(json_decode($r, true));
            curl_close($ch);
            ob_end_clean();
        } catch (\Throwable $th) {
            // dd($th);
            throw $th;
        }
    }
}
