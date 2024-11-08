<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class Testing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {


        App::setLocale('en');

        $text = "";

        echo "\n\n\n ### CLIENT_MEETING_SCHEDULE\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_meeting_schedule.content');



        $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("meeting-schedule/" . base64_encode(1));

        $text .= "\n\n" . __('mail.wa-message.button-label.upload_file') . ": " . url("meeting-files/" . base64_encode(1));
        $text .= "\n\n";
        echo $text;
        $text = "";




        echo "\n\n\n ### CLIENT_MEETING_REMINDER\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_meeting_schedule.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("meeting-schedule/" . base64_encode(1));

        $text .= "\n\n" . __('mail.wa-message.button-label.upload_file') . ": " . url("meeting-files/" . base64_encode(1));

        echo $text;
        $text = "";



        echo "\n\n\n ### OFFER_PRICE\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.offer_price.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.price_offer') . ": " . url("price-offer/" . base64_encode(1));

        echo $text;
        $text = "";


        echo "\n\n\n ### CONTRACT\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.check_contract') . ": " . url("work-contract/" . 1);

        echo $text;
        $text = "";


        echo "\n\n\n ### CLIENT_JOB_UPDATED\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_job_updated.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode(1) . "/review");

        echo $text;
        $text = "";





        echo "\n\n\n ### CREATE_JOB\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.create_job.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode(1) . "/review");

        $text .= __('mail.wa-message.create_job.signature');

        echo $text;
        $text = "";




        echo "\n\n\n ### DELETE_MEETING\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.delete_meeting.content');

        echo $text;
        $text = "";




        echo "\n\n\n ### FORM101\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.form101.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.form101') . ": " . url("form101/" . base64_encode(1) . "/" . base64_encode(1));

        echo $text;
        $text = "";


        echo "\n\n\n ### NEW_JOB\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.new_job.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/login");

        echo $text;
        $text = "";


        echo "\n\n\n ### WORKER_CONTRACT\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.worker_contract.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.check_contract') . ": " . url("worker-contract/" . base64_encode(1));

        echo $text;
        $text = "";




        echo "\n\n\n ### WORKER_HEARING_SCHEDULE\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.worker_hearing_schedule.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("hearing-schedule/" . base64_encode(1));

        echo $text;
        $text = "";



        echo "\n\n\n ### WORKER_JOB_APPROVAL\n\n";

        $text .= __('mail.wa-message.common.salutation', [
            'name' => 'צוות'
        ]);

        $text .= "\n\n";

        $text .= __('mail.wa-message.worker_job_approval.content');

        echo $text;
        $text = "";




        echo "\n\n\n ### WORKER_NOTIFY_AFTER_ON_MY_WAY\n\n";

        $text .= __('mail.wa-message.worker_on_my_way.subject');

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $commenttext = __('mail.wa-message.worker_on_my_way.all_comments');

        $text .= __('mail.wa-message.worker_on_my_way.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/" . 1);
        $text .= "\n" . __('mail.wa-message.button-label.contact_manager') . ": " . url("worker/jobs/view/?q=contact_manager");

        $text .= __('mail.wa-message.worker_on_my_way.signature');

        echo $text;
        $text = "";





        // echo "\n\n\n ### TEAM_NOTIFY_WORKER_AFTER_ON_MY_WAY\n\n";

       
        // $text .= __('mail.wa-message.team_worker_on_my_way.subject');

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.team_worker_on_my_way.content');

        // $text .= __('mail.wa-message.team_worker_on_my_way.signature');

        // echo $text;
        // $text = "";

        


        echo "\n\n\n ### WORKER_NOTIFY_BEFORE_ON_MY_WAY\n\n";

        $text .= __('mail.wa-message.worker_on_my_way.beforeSubject');

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

       $text .= __('mail.wa-message.worker_on_my_way.beforeContent');

       $text .= __('mail.wa-message.worker_on_my_way.signature');

        echo $text;
        $text = "";





    //     echo "\n\n\n ### TEAM_NOTIFY_WORKER_BEFORE_ON_MY_WAY\n\n";

    //     $text .= __('mail.wa-message.common.salutation');

    //     $text .= "\n\n";

    //   $text .= __('mail.wa-message.team_worker_on_my_way.beforeContent');

    //   $text .= __('mail.wa-message.team_worker_on_my_way.signature');

    //     echo $text;
    //     $text = "";




        echo "\n\n\n ### NOTIFY_WORKER_BEFORE_30MIN_JOB_END_TIME\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.before_job_endtime.content');

        $text .= __('mail.wa-message.before_job_endtime.signature');

        echo $text;
        $text = "";





        echo "\n\n\n ### WORKER_NEED_EXTRA_TIME\n\n";


        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.need_extra_time_team.content');

        $text .= __('mail.wa-message.need_extra_time_team.signature');

        echo $text;
        $text = "";



        // echo "\n\n\n ### TEAM_NOTIFY_CONTACT_MANAGER\n\n";

        // $text = 'צור קשר עם מאנגר | שירות ברום.';

        // $text .= "\n\n" . "אנא בדוק את הפרטים.";

        // $text .= __('mail.wa-message.worker_job_approval.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.actions') . ": " . url("team-btn/" . base64_encode(1));

        // echo $text;
        // $text = "";




        echo "\n\n\n ### REMIND_WORKER_TO_JOB_CONFIRM_5PM\n\n";

        $text .= __('mail.wa-message.common.salutation') . "\n\n";

        $text .= __('mail.wa-message.remind_to_worker.content');

        $text .= __('mail.wa-message.remind_to_worker.signature');

        echo $text;
        $text = "";




        echo "\n\n\n ### REMIND_WORKER_TO_JOB_CONFIRM_6PM\n\n";


        $text .= __('mail.wa-message.common.salutation') . "\n\n";

        $text .= __('mail.wa-message.remind_to_worker.content2');

        $text .= __('mail.wa-message.remind_to_worker.signature');

        echo $text;
        $text = "";



        // echo "\n\n\n ### JOB_APPROVED_NOTIFICATION_TO_TEAM\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_not_approved_job_team.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.change_worker_shift') . ": " . url("admin/jobs/" . 1 . "/change-worker");

        // $text .= "\n\n" . "Worker view" . ": " . url("admin/jobs/view/" . 1);

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### TO_TEAM_WORKER_NOT_CONFIRM_JOB\n\n";

        // $text .= __('mail.wa-message.not_confirm_job.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.not_confirm_job.content');

        // $text .= __('mail.wa-message.not_confirm_job.signature');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### WORKER_NOT_APPROVED_JOB\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_not_approved_job.content');
        // $text .= "\n\n" . __('mail.wa-message.button-label.change_worker_shift') . ": " . url("admin/jobs/" . 1 . "/change-worker");

        // echo $text;
        // $text = "";






        // echo "\n\n\n ### WORKER_NOT_LEFT_FOR_JOB\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_not_left_for_job.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.change_worker_shift') . ": " . url("admin/jobs/" . 1 . "/change-worker");

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### WORKER_NOT_STARTED_JOB\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_not_started_job.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . 1);

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### WORKER_NOT_FINISHED_JOB_ON_TIME\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_not_finished_job_on_time.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . 1);

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### WORKER_EXCEED_JOB_TIME\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_exceed_job_time.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . 1);

        // echo $text;
        // $text = "";





        echo "\n\n\n ### WORKER_REMIND_JOB\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";


        $text .= __('mail.wa-message.worker_remind_job.content');

        $text .= __('mail.wa-message.worker_remind_job.signature');

        echo $text;
        $text = "";








        echo "\n\n\n ### WORKER_UNASSIGNED\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.worker_unassigned_job.content');

        echo $text;
        $text = "";

                    



        echo "\n\n\n ### CLIENT_JOB_STATUS_NOTIFICATION\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_job_status_notification.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("client/login");

        echo $text;
        $text = "";





        // echo "\n\n\n ### WORKER_JOB_OPENING_NOTIFICATION\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_job_opening_notification.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("admin/jobs/view/" . 1);

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . 1);

        // echo $text;
        // $text = "";


                    



        // echo "\n\n\n ### WORKER_ARRIVE_NOTIFY\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_arrive.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/" . 1);

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### NOTIFY_TEAM_FOR_SKIPPED_COMMENTS\n\n";

        // $text = __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]) . "\n\n";

        // $text .= "צוות תשומת לב: הערה נדחתה עבור מזהה משרה: " . "#" . ".\n\n";

        // $text .= "לָקוּחַ: " . "[name]" . " " ."name" . "\n";
        // $text .= "טֵלֵפוֹן: " . "[phone]" . "\n";
        // $text .= "עובד שהוקצה: " . "name" . "\n";
        // $text .= "טלפון לעובד: " . "phone" . "\n\n";

        // $text .= "הֶעָרָה: " . "[comments]" . "\n";
        // $text .= "בקש תגובה: " . "[request_text]" . "\n\n";

        // $text .= "\nאנא עיין בפרטי ההערה שדילגתם ונקוט פעולה מתאימה.";
        // $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("action-comment/" . 1);
        // $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/" . 1);

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### TEAM_ADJUST_WORKER_JOB_COMPLETED_TIME\n\n";

        // // Message Template
        // $text = __('mail.job_nxt_step.completed_nxt_step_email_title'); // Optional localized message title
        // $text .= "\n\n";

        // $text .= "שלום, צוות\n\n";
        // $text .= "העבודה עבור המשימה חרגה מהזמן המתוכנן.\n";

        // // Adding worker details and job ID
        // $text .= "מזהה משרה: " . 1 . "\n";
        // $text .= "עוֹבֵד: " . "name" . "\n\n"; // Assuming worker's first name is under 'worker'

        // // Scheduled and actual completion times
        // $text .= " זמן מתוכנן: " . "start_date" . " " . "end_time" . "\n";
        // $text .= "זמן בפועל: " . "completeTime" . "\n\n";

        // // Options for the team to choose from
        // $text .= "  אנא בחר את האפשרות המתאימה:\n";
        // $text .= "שמור את הזמן האמיתי כפי שהוא: " . url("time-manage/?action=keep") . "\n";
        // $text .= "התאם את הזמן כך שיתאים לזמן המתוכנן: " . url("time-manage/?action=adjust") . "\n\n";

        // $text .= __('mail.wa-message.team_worker_on_my_way.signature');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### NOTIFY_CLIENT_FOR_REVIEWED\n\n";

        // // Create the message text
        // $text = __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_commented.content');

        // $text .= "\n\n";

        // $text .= __('mail.client_job_status.job_completed') . "\n";
        // $text .= __('mail.client_new_job.service') . ": " . "\n";
        // $text .= __('mail.client_new_job.date') . ": " . "\n";
        // $text .= __('mail.client_new_job.start_time') . ": " . "\n";

        // // Add a closing statement
        // $text .= "\n" . __('mail.common.dont_hesitate_to_get_in_touch');
        // $text .= "\n" . __('mail.common.regards') . "\n";
        // $text .= __('mail.common.company') . "\n";
        // $text .= __('mail.common.tel') . ": 03-525-70-60\n";
        // $text .= __('mail.common.email') . ": office@broomservice.co.il";

        // echo $text;
        // $text = "";




        echo "\n\n\n ### NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE\n\n";

        $text = __('mail.wa-message.notify_monday_client.subject');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_client.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_client.content');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_client.holiday');
        $text .= "\n";

        $text .= __('mail.wa-message.notify_monday_client.request');
        
        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_client.link');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_client.signature');

        echo $text;
        $text = "";






        echo "\n\n\n ### NOTIFY_MONDAY_WORKER_FOR_SCHEDULE\n\n";

        $text = __('mail.wa-message.notify_monday_worker.subject');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_worker.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_worker.content');

        $text .= "\n\n";

            $text .= __('mail.wa-message.notify_monday_worker.holiday');
        $text .= "\n";

        $text .= __('mail.wa-message.notify_monday_worker.link');

        $text .= "\n\n";

        $text .= __('mail.wa-message.notify_monday_worker.signature');

        echo $text;
        $text = "";






        // echo "\n\n\n ### WORKER_JOB_STATUS_NOTIFICATION\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_job_status_notification.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/");

        // echo $text;
        // $text = "";






        // echo "\n\n\n ### WORKER_SAFE_GEAR\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_safe_gear.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.safety_and_gear') . ": " . url("worker-safe-gear/" . base64_encode(1));

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### ADMIN_RESCHEDULE_MEETING\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.admin_reschedule_meeting.content');

        // echo $text;
        // $text = "";







        echo "\n\n\n ### CLIENT_RESCHEDULE_MEETING\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_reschedule_meeting.content');

        echo $text;
        $text = "";






        // echo "\n\n\n ### ADMIN_LEAD_FILES\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.admin_lead_files.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.check_file') . ": " . url("storage/uploads/ClientFiles/");

        // echo $text;
        // $text = "";




        echo "\n\n\n ### LEAD_NEED_HUMAN_REPRESENTATIVE\n\n";

        $text .= __('mail.wa-message.common.salutation', [
            'name' => 'צוות'
        ]);

        $text .= "\n\n";

        $text .= __('mail.wa-message.lead_need_human_representative.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.view_client') . ": " . url("admin/clients/view/");

        echo $text;
        $text = "";





        // echo "\n\n\n ### NO_SLOT_AVAIL_CALLBACK\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.no_slot_avail_callback.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_client') . ": " . url("admin/clients/view/");

        // echo $text;
        // $text = "";






        echo "\n\n\n ### WORKER_FORMS\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.worker_forms.content');

        $text .= "\n\n" . __('mail.wa-message.button-label.check_form') . ": " . url("worker-forms/");

        echo $text;
        $text = "";



        // echo "\n\n\n ### ADMIN_JOB_STATUS_NOTIFICATION\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.admin_job_status_notification.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("admin/jobs/view/");

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### WORKER_CHANGED_AVAILABILITY_AFFECT_JOB\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_changed_availability_affect_job.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### WORKER_LEAVES_JOB\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_leaves_job.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### CLIENT_PAYMENT_FAILED\n\n";

        // $text .= __('mail.wa-message.common.salutation', ['name' => 'צוות']);
        // $text .= "\n\n";
        // $text .= __('mail.wa-message.client_payment_failed.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### ORDER_CANCELLED\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.order_cancelled.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### PAYMENT_PAID / PAYMENT_PARTIAL_PAID\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.payment_paid.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY\n\n";


        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_invoice_created_and_sent_to_pay.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### CLIENT_INVOICE_PAID_CREATED_RECEIPT\n\n";


        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_invoice_paid_created_receipt.content');

        // echo $text;
        // $text = "";






        // echo "\n\n\n ### ORDER_CREATED_WITH_EXTRA\n\n";


        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.order_created_with_extra.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### ORDER_CREATED_WITH_DISCOUNT\n\n";


        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.order_created_with_discount.content');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### CLIENT_REVIEWED\n\n";


        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_reviewed.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### CLIENT_CHANGED_JOB_SCHEDULE\n\n";

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_changed_job_schedule.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### CLIENT_COMMENTED\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_commented.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### ADMIN_COMMENTED\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.admin_commented.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### WORKER_COMMENTED\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.worker_commented.content');

        // echo $text;
        // $text = "";






        // echo "\n\n\n ### NEW_LEAD_ARRIVED\n\n";
        

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.new_lead_arrived.content');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.new_lead_arrived.follow_up');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/" );
        // $text .= "\n\n" . __('mail.wa-message.button-label.call_lead') . ": ";

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### USER_STATUS_CHANGED\n\n";
        

        // // Build the WhatsApp message content
        // $text .= __('mail.wa-message.common.salutation', ['name' => "צוות"]);
        // $text .= "\n\n";
        // $text .= __('mail.wa-message.user_status_changed.content');

        // echo $text;
        // $text = "";






        echo "\n\n\n ### UNANSWERED_LEAD\n\n";
        
        $text .= __('mail.wa-message.common.salutation');
        $text .= "\n\n";
        $text .= __('mail.wa-message.tried_to_contact_you.content');
        $text .= "\n\n";

        $text .= __('mail.wa-message.tried_to_contact_you.contact_details');
        
        $text .= "\n\n";
        
        $text .= __('mail.wa-message.tried_to_contact_you.availability');

        $text .= "\n\n";

        $text .= __('mail.wa-message.common.closing');

        $text .= "\n\n";
        $text .= __('mail.wa-message.common.signature');

        echo $text;
        $text = "";





        echo "\n\n\n ### INQUIRY_RESPONSE\n\n";
        

        $text .= __('mail.wa-message.common.salutation');
        $text .= "\n\n";
        $text .= __('mail.wa-message.inquiry_response.content');
        $text .= "\n\n";
        $text .= __('mail.wa-message.inquiry_response.service_areas');
        $text .= "\n\n";
        $text .= __('mail.wa-message.common.signature');

        echo $text;
        $text = "";





        // echo "\n\n\n ### PENDING\n\n";
        
        // $text .= __('mail.wa-message.pending.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### POTENTIAL\n\n";
        
        // $text .= __('mail.wa-message.potential.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### IRRELEVANT\n\n";
        
        // $text .= __('mail.wa-message.irrelevant.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### UNINTERESTED\n\n";
        
        // $text .= __('mail.wa-message.uninterested.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### UNANSWERED\n\n";
        
        // $text .= __('mail.wa-message.unanswered.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### POTENTIAL_CLIENT\n\n";
        
        // $text .= __('mail.wa-message.potential_client.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### PENDING_CLIENT\n\n";
        
        // $text .= __('mail.wa-message.pending_client.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### WAITING\n\n";
        
        // $text .= __('mail.wa-message.waiting.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### ACTIVE_CLIENT\n\n";
        
        // $text .= __('mail.wa-message.active_client.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### WORKER_CONTACT_TO_MANAGER\n\n";
        
        // $text .= "\n\nשלום, צוות\n\n";
        // $text .= 'העובד צריך ליצור קשר עם המנהל.' . "\n\n";
       
        // $text .= "תאריך/שעה: \nלקוח: \nעובד: \nנכס: ";

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### FREEZE_CLIENT\n\n";
        
        // $text .= __('mail.wa-message.freeze_client_team.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### UNHAPPY\n\n";
        
        // $text .= __('mail.wa-message.unhappy.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### PRICE_ISSUE\n\n";
        
        // $text .= __('mail.wa-message.price_issue.content');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### MOVED\n\n";
        
        // $text .= __('mail.wa-message.moved.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### ONETIME\n\n";
        
        // $text .= __('mail.wa-message.onetime.content');

        // echo $text;
        // $text = "";

                

        echo "\n\n\n ### PAST\n\n";
        
        $text .= __('mail.wa-message.common.salutation');

        $text .= __('mail.wa-message.past.thankyou');

        $text .= "\n\n";

        $text .= __('mail.wa-message.past.content');

        $text .= "\n\n";

        $text .= __('mail.wa-message.past.feelfree');

        $text .= "\n\n";

        $text .= __('mail.wa-message.past.signature');

        echo $text;
        $text = "";




        // echo "\n\n\n ### FOLLOW_UP_REQUIRED\n\n";
        
        // $text .= __('mail.wa-message.follow_up_required.salutation');
        // $text .= "\n\n";

        // $text .= __('mail.wa-message.follow_up_required.content');
        // $text .= "\n\n";

        // $text .= __('mail.wa-message.follow_up_required.common.signature');

        // echo $text;
        // $text = "";

                


        // echo "\n\n\n ### FOLLOW_UP_PRICE_OFFER\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות',
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.follow_up_price_offer.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### FINAL_FOLLOW_UP_PRICE_OFFER\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות',
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.final_follow_up_price_offer.content');

        // echo $text;
        // $text = "";




        echo "\n\n\n ### FOLLOW_UP_PRICE_OFFER_SENT_CLIENT\n\n";
        
        $text .= __('mail.wa-message.price_offer_reminder_sent.salutation') . "\n\n";
                    
        $text .= __('mail.wa-message.price_offer_reminder_sent.content');

        echo $text;
        $text = "";



        // echo "\n\n\n ### LEAD_ACCEPTED_PRICE_OFFER\n\n";
        
        // $text .= __('mail.wa-message.lead_accepted_price_offer.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/");

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### BOOK_CLIENT_AFTER_SIGNED_CONTRACT\n\n";
        

        // $text .= __('mail.wa-message.book_client_after_signed_contract.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.book_client_after_signed_contract.content');

        // $text .= __('mail.wa-message.book_client_after_signed_contract.contract_link') . ": " . url("admin/view-contract/");

        // $text .= __('mail.wa-message.book_client_after_signed_contract.signature');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### LEAD_DECLINED_PRICE_OFFER\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות',
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.lead_declined_price_offer.content');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.lead_declined_price_offer.details');
        // $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/");

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.lead_declined_price_offer.assistance');

        // $text .= __('mail.common.regards');

        // $text .= "\n";

        // $text .= __('mail.common.company');

        // echo $text;
        // $text = "";




        echo "\n\n\n ### FILE_SUBMISSION_REQUEST\n\n";
        
        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.file_submission_request.content');

        $text .= "\n\n";

        $text .= __('mail.wa-message.file_submission_request.details');

        $text .= "\n\n";

        $text .= __('mail.wa-message.file_submission_request.assistance');

        $text .= __('mail.wa-message.file_submission_request.signature');

        echo $text;
        $text = "";





        // echo "\n\n\n ### FILE_SUBMISSION_REQUEST_TEAM\n\n";
        
        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.file_submission_request_team.content');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.file_submission_request_team.details');

        // $text .= __('mail.wa-message.file_submission_request_team.signature');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### LEAD_DECLINED_CONTRACT\n\n";
        

        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות',
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.lead_declined_contract.content');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.lead_declined_contract.details');

        // $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/");

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.lead_declined_contract.assistance');

        // $text .= __('mail.common.regards');

        // $text .= "\n";

        // $text .= __('mail.common.company');

        // echo $text;
        // $text = "";





        echo "\n\n\n ### CLIENT_IN_FREEZE_STATUS\n\n";
        
        $text .= __('mail.wa-message.common.salutation');

        $text .= __('mail.wa-message.client_in_freeze_status.thankyou');

        $text .= "\n\n";
        $text .= __('mail.wa-message.client_in_freeze_status.content');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_in_freeze_status.action_required');

        $text .= "\n\n";

        $text .= __('mail.wa-message.client_in_freeze_status.signature');

        echo $text;
        $text = "";




        // echo "\n\n\n ### STATUS_NOT_UPDATED\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות',
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.status_not_updated.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### CLIENT_LEAD_STATUS_CHANGED\n\n";
        
        // $text .= __('mail.wa-message.common.salutation', [
        //     'name' => 'צוות'
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.client_lead_status_changed.content');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### SICK_LEAVE_NOTIFICATION\n\n";
        
        // $text = __('mail.wa-message.follow_up.subject');
        // $text .= "\n\n";
        // $text .= __('mail.wa-message.follow_up.salutation');

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### REFUND_CLAIM_MESSAGE\n\n";

        // $text .= __('mail.wa-message.common.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.refund_claim.body');
        // $text .= "\n\n";
        // $text .= __('mail.refund_claim.reason');

        // echo $text;
        // $text = "";




        echo "\n\n\n ### FOLLOW_UP_ON_OUR_CONVERSATION\n\n";

        $text .= __('mail.wa-message.follow_up.introduction');
        $text .= "\n\n";
        $text .= __('mail.wa-message.follow_up.testimonials');
        $text .= "\n\n";
        $text .= __('mail.wa-message.follow_up.brochure');
        $text .= "\n\n";
        $text .= __('mail.wa-message.follow_up.commitment');
        $text .= "\n\n";
        $text .= __('mail.wa-message.follow_up.help');
        $text .= "\n\n";
        $text .= __('mail.wa-message.follow_up.signature');

        echo $text;
        $text = "";



        echo "\n\n\n ### NOTIFY_CONTRACT_VERIFY_TO_CLIENT\n\n";

        $text = __('mail.wa-message.contract_verify.subject');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_verify.info');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_verify.content');

        echo $text;
        $text = "";





        // echo "\n\n\n ### NOTIFY_CONTRACT_VERIFY_TO_TEAM\n\n";

        // $text = __('mail.wa-message.contract_verify_team.subject');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_verify_team.info',[
        //     'name' => "צוות",
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_verify_team.content');

        // echo $text;
        // $text = "";



        echo "\n\n\n ### CONTRACT_REMINDER_TO_CLIENT_AFTER_7DAY\n\n";

        $text .= __('mail.wa-message.contract_reminder.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_reminder.content');

        $text .= __('mail.wa-message.contract_reminder.signature');

        echo $text;
        $text = "";



        echo "\n\n\n ### CONTRACT_REMINDER_TO_CLIENT_AFTER_3DAY\n\n";

        $text .= __('mail.wa-message.contract_reminder.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_reminder.content');

        $text .= __('mail.wa-message.contract_reminder.signature');

        echo $text;
        $text = "";




        echo "\n\n\n ### CONTRACT_REMINDER_TO_CLIENT_AFTER_24HOUR\n\n";

        // Add the body content with dynamic client name and contract date
        $text .= __('mail.wa-message.contract_reminder.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_reminder.content');

        $text .= __('mail.wa-message.contract_reminder.signature');

        echo $text;
        $text = "";



        // echo "\n\n\n ### CONTRACT_REMINDER_TO_TEAM_AFTER_24HOUR_3_AND_7DAYS\n\n";

        // $text .= __('mail.wa-message.contract_reminder_team.salutation');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder_team.content');

        // $text .= __('mail.wa-message.contract_reminder.signature');

        // echo $text;
        // $text = "";




        echo "\n\n\n ### WEEKLY_CLIENT_SCHEDULED_NOTIFICATION\n\n";

        $text .= __('mail.wa-message.common.salutation');

        $text .= "\n\n";

        $text .= __('mail.wa-message.weekly_notification.content');

        $text .= "\n\n";

        $text .= __('mail.wa-message.button-label.change_service_date') . ": " . url("client/jobs");
        $text .= "\n\n";
        $text .= __('mail.wa-message.common.signature');

        echo $text;
        $text = "";




        // echo "\n\n\n ### CONTRACT_NOT_SIGNED_12_HOURS\n\n";

        // $text = __('mail.wa-message.contract_reminder_team.subject');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder_team.body_intro');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder_team.body_instruction');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder_team.client_contact');

        // $text .= "\n";

        // $text .= __('mail.wa-message.contract_reminder_team.client_link');

        // echo $text;
        // $text = "";





        // echo "\n\n\n ### PRICE_OFFER_REMINDER_12_HOURS\n\n";
        
        // $text = __('mail.wa-message.price_offer_reminder12.subject');

        // $text .= "\n\n";

        // // Add the body content with dynamic client name
        // $text .= __('mail.wa-message.price_offer_reminder12.body_intro');

        // $text .= "\n\n";

        // // Adding follow-up instruction
        // $text .= __('mail.wa-message.price_offer_reminder12.body_instruction');

        // $text .= "\n\n";

        // // Client contact details
        // $text .= __('mail.wa-message.price_offer_reminder12.client_contact');

        // $text .= "\n";

        // // Add client details link
        // $text .= __('mail.wa-message.price_offer_reminder12.client_link',) . ": " . url("admin/clients/view/" );

        // echo $text;
        // $text = "";




        echo "\n\n\n ### WORKER_LEAD_WEBHOOK_IRRELEVANT\n\n";
        
        $text = '';

        $text .=  __('mail.wa-message.worker_webhook_irrelevant.message');

        echo $text;
        $text = "";



        echo "\n\n\n ### NOTIFY_CONTRACT_VERIFY_TO_CLIENT\n\n";
        
        $text = __('mail.wa-message.contract_verify.subject');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_verify.info');

        $text .= "\n\n";

        $text .= __('mail.wa-message.contract_verify.content');

        echo $text;
        $text = "";




        // echo "\n\n\n ### NOTIFY_CONTRACT_VERIFY_TO_TEAM\n\n";
        
        // $text = __('mail.wa-message.contract_verify_team.subject');

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_verify_team.info',[
        //     'name' => "צוות",
        // ]);

        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_verify_team.content');

        // $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode(1) . "/review");

        // echo $text;
        // $text = "";



        // echo "\n\n\n ### CONTRACT_REMINDER_TO_CLIENT_AFTER_3DAY\n\n";
        
        // $text .= __('mail.wa-message.contract_reminder.salutation');
    
        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder.content');

        // $text .= __('mail.wa-message.contract_reminder.signature');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### CONTRACT_REMINDER_TO_CLIENT_AFTER_24HOUR\n\n";
        
        // $text .= __('mail.wa-message.contract_reminder.salutation');
    
        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder.content');

        // $text .= __('mail.wa-message.contract_reminder.signature');

        // echo $text;
        // $text = "";




        // echo "\n\n\n ### CONTRACT_REMINDER_TO_CLIENT_AFTER_7DAY\n\n";
        
        // $text .= __('mail.wa-message.contract_reminder.salutation');
    
        // $text .= "\n\n";

        // $text .= __('mail.wa-message.contract_reminder.content');

        // $text .= __('mail.wa-message.contract_reminder.signature');

        // echo $text;
        // $text = "";

        exit();

        return 0;
    }
}
