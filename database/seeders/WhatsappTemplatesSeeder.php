<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Enums\WhatsappMessageTemplateEnum;
use App\Models\WhatsappTemplate;

class WhatsappTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = [
            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_5_PM,
                'description' => '5 PM Daily Reminder to Worker to Confirm Address',
                'message_en' => 'Hello, *:worker_name*,

Please confirm that you have seen the address for tomorrow’s job:

*Address:* :job_full_address
*Date/Time:* :job_start_date_time

- *Accept Address* :job_accept_url
- *Contact Manager* :job_contact_manager_link if you have any questions.

Best Regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

אנא אשר שראית את הכתובת לעבודה מחר:

*כתובת:* :job_full_address
*תאריך/שעה:* :job_start_date_time

- *[אשר כתובת]* :job_accept_url
- *צור קשר עם המנהל* :job_contact_manager_link במידה ויש לך שאלות או בעיות.

בברכה,
צוות ברום סרוויס  ',
                'message_spa' => 'Hola, *:worker_name*,

Por favor confirma que has visto la dirección para el trabajo de mañana:

*Dirección:* :job_full_address
*Fecha/Hora:* :job_start_date_time

- *Aceptar Dirección* :job_accept_url
- *Contactar al Gerente* :job_contact_manager_link si tienes alguna pregunta.

Saludos cordiales,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

Пожалуйста, подтвердите, что вы видели адрес для завтрашней работы:

*Адрес:* :job_full_address
*Дата/время:* :job_start_date_time

- *Подтвердить адрес* :job_accept_url
- *Связаться с менеджером* :job_contact_manager_link если у вас есть вопросы.

С уважением,
Команда Broom Service',

                'suggestions' => [
                    ':worker_name' => 'Worker Name',
                    ':job_accept_url' => 'Accept Address',  
                    ':job_contact_manager_link' => 'Contact Manager',
                    ':job_full_address' => 'Address',
                    ':job_start_date_time' => 'Date/Time',
                ]
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM,
                'description' => '6 PM Daily Reminder to Worker to Confirm Address',
                'message_en' => 'Hello, *:worker_name*,

This is a reminder to confirm the address for tomorrow’s job as soon as possible:

*Address:* :job_full_address
*Date/Time:* :job_start_date_time

- *Accept Address* :job_accept_url
- *Contact Manager* :job_contact_manager_link if you have any questions.

Best Regards,
Broom Service Team
',
    'message_heb' => 'שלום, *:worker_name*,

תזכורת לאשר בהקדם האפשרי את הכתובת לעבודה מחר:

*כתובת:* :job_full_address
*תאריך/שעה:* :job_start_date_time

- *[אשר כתובת]* :job_accept_url
- *צור קשר עם המנהל* :job_contact_manager_link במידה ויש לך שאלות או בעיות.

בברכה,
צוות ברום סרוויס  ',
    'message_spa' => 'Hola, *:worker_name*,

Este es un recordatorio para confirmar la dirección para el trabajo de mañana lo antes posible:

*Dirección:* :job_full_address
*Fecha/Hora:* :job_start_date_time

- *Aceptar Dirección* :job_accept_url
- *Contactar al Gerente* :job_contact_manager_link si tienes alguna pregunta.

Saludos cordiales,
Equipo de Broom Service
',
    'message_ru' => 'Здравствуйте, *:worker_name*,

Это напоминание подтвердить адрес для завтрашней работы как можно скорее:

*Адрес:* :job_full_address
*Дата/время:* :job_start_date_time

- *Подтвердить адрес* :job_accept_url
- *Связаться с менеджером* :job_contact_manager_link если у вас есть вопросы.

С уважением,
Команда Broom Service',

    'suggestions' => [
        ':worker_name' => 'Worker Name',
        ':job_accept_url' => 'Accept Address',  
        ':job_contact_manager_link' => 'Contact Manager',
        ':job_full_address' => 'Address',
        ':job_start_date_time' => 'Date/Time',
    ]
            ],

            [
                'key' => WhatsappMessageTemplateEnum::TEAM_JOB_NOT_APPROVE_REMINDER_AT_6_PM,
                'description' => '6 PM Notification to Team if Worker Has Not Confirmed Address',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

העובד, *:worker_name*, עדיין לא אישר את הכתובת לעבודה מחר.

*שם הלקוח:* :client_name
*טלפון לקוח:* :client_phone_number
*טלפון עובד:* :worker_phone_number
*כתובת:* :job_full_address
*תאריך/שעה:* :job_start_date_time

- *אשר כתובת עבור העובד* :team_action_btns_link
- *נקוט פעולה* :team_job_action_link (החלפת עובד, שינוי משמרת או ביטול עבודה במידת הצורך).

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',

                'suggestions' => [
                    ':worker_name' => 'Worker Name',
                    ':client_name' => 'Client Name',
                    ':client_phone_number' => 'Client Phone Number',
                    ':worker_phone_number' => 'Worker Phone Number',
                    ':job_full_address' => 'Address',
                    ':job_start_date_time' => 'Date/Time',
                    ':team_action_btns_link' => 'job Acctions buttons behalf of worker',
                    ':team_job_action_link' => 'admin change worker link',
                ]
            ],

            [
                'key' => WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START,
                'description' => 'Reminder to Worker 1 Hour Before Job Start',
                'message_en' => 'Hello, *:worker_name*,

You have a job scheduled at *:job_start_time* at the following location:

*Address:* :job_full_address
*Client:* :client_name

- *I’m On My Way* :worker_job_link
- *Contact Manager* :job_contact_manager_link if you need assistance.

Best Regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

יש לך עבודה המתוכננת לשעה *:job_start_time* בכתובת הבאה:

*כתובת:* :job_full_address
*לקוח:* :client_name

- *אני בדרכי* :worker_job_link
- *צור קשר עם המנה* :job_contact_manager_link במידה ואתה זקוק לעזרה.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Hola, *:worker_name*,

Tienes un trabajo programado a las *:job_start_time* en la siguiente ubicación:

*Dirección:* :job_full_address
*Cliente:* :client_name

- *Estoy en camino* :worker_job_link
- *Contactar al gerente* :job_contact_manager_link si necesitas ayuda.

Saludos cordiales,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

У вас назначена работа на *:job_start_time* по следующему адресу:

*Адрес:* :job_full_address
*Клиент:* :client_name

- *Я в пути* :worker_job_link
- *Связаться с менеджером* :job_contact_manager_link если вам нужна помощь.

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_BEFORE_30_MINS,
                'description' => '30-Minute Reminder to Team if Worker Has Not Confirmed',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

העובד, *:worker_name*, עדיין לא אישר שהוא בדרכו לעבודה שתתחיל בשעה *:job_start_time*.

*שם הלקוח:* :client_name
*טלפון לקוח:* :client_phone_number
*טלפון עובד:* :worker_phone_number
*כתובת:* :job_full_address
*תאריך/שעה:* :job_start_date_time

- *אשר בדרכו עבור העובד* :team_action_btns_link
- *נקוט פעולה* :team_job_action_link (אפשרויות: החלפת עובד, שינוי משמרת, ביטול עבודה ועדכון הלקוח לפי הצורך).

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_CONFIRMING_ON_MY_WAY,
                'description' => 'Notification to Worker After Confirming They’re On Their Way',
                'message_en' => 'Hello, *:worker_name*,

Once you arrive at the job location, please confirm by clicking the button below.

- *Click Here to Start Job* :worker_job_link
- *Contact Manager* :job_contact_manager_link if you need assistance.

Best regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

לאחר שהגעת למקום העבודה, נא אשר זאת על ידי לחיצה על הכפתור למטה.

- *לחץ כאן כדי להתחיל עבודה* :worker_job_link
- *צור קשר עם המנהל* :job_contact_manager_link במידה ואתה זקוק לעזרה.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Hola, *:worker_name*,

Una vez que llegue al lugar de trabajo, por favor confirme haciendo clic en el botón de abajo.

- *Haga clic aquí para comenzar el trabajo* :worker_job_link
- *Contactar al gerente* :job_contact_manager_link si necesita ayuda.

Atentamente,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

По прибытии на место работы, пожалуйста, подтвердите это, нажав на кнопку ниже.

- *Нажмите здесь, чтобы начать работу* :worker_job_link
- *Связаться с менеджером* :job_contact_manager_link если вам нужна помощь.

С уважением,
Команда Broom Service
',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_AFTER_30_MINS,
                'description' => 'Notification to Team if Worker Hasn’t Started Job Within 30 Minutes',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

העובד, *:worker_name*, עדיין לא התחיל את העבודה שתוכננה להתחיל בשעה *:job_start_time*.

*שם הלקוח:* :client_name
*טלפון לקוח:* :client_phone_number
*טלפון עובד:* :worker_phone_number
*כתובת:* :job_full_address
*תאריך/שעה:* :job_start_date_time

- *התחל עבודה עבור העובד*  :team_action_btns_link
- *נקוט פעולה* :team_job_action_link (אפשרויות: החלפת עובד, שינוי משמרת, ביטול עבודה ועדכון הלקוח לפי הצורך).

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_START_THE_JOB,
                'description' => 'Worker Notification Upon Shift Start - Job Details',
                'message_en' => 'Hello, *:worker_name*,

Your job at *:job_full_address* has started. You have *:job_remaining_hours hours* to complete the service, and it should be finished by *:job_end_time*.

Please review and complete the following tasks:
- *Service:* :job_service_name
:job_comments
When you’re finished, please confirm::comment_worker_job_link
- *Contact Manager* :job_contact_manager_link if you have any issues with the tasks.

Best regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

התחלת את העבודה בכתובת *:job_full_address*. יש לך *:job_remaining_hours שעות* לסיום העבודה, והיא צריכה להסתיים עד *:job_end_time*.

אנא עיין ובצע את המשימות הבאות:
- *שירות:* :job_service_name
:job_comments
כשתסיים, נא אשר::comment_worker_job_link
- *צור קשר עם המנהל* :job_contact_manager_link אם יש בעיות בביצוע המשימות.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Hola, *:worker_name*,

Su trabajo en *:job_full_address* ha comenzado. Usted tiene *:job_remaining_hours horas* para completar el servicio, y debe terminar antes de *:job_end_time*.

Por favor, revise y complete las siguientes tareas:
- *Servicio:* :job_service_name
:job_comments
Cuando haya terminado, por favor confirme::comment_worker_job_link
- *Contactar al gerente* :job_contact_manager_link si tiene algún problema con las tareas.

Atentamente,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

Ваша работа по адресу *:job_full_address* началась. У вас есть *:job_remaining_hours часа* для завершения работы, и она должна быть завершена к *:job_end_time*.

Пожалуйста, ознакомьтесь и выполните следующие задачи:
- *Услуга:* :job_service_name
:job_comments
Когда закончите, пожалуйста, подтвердите::comment_worker_job_link
- *Связаться с менеджером*, :job_contact_manager_link если у вас есть проблемы с задачами.

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_SKIPPED_COMMENTS,
                'description' => 'Notification to Team if Worker Contacts Manager about Comments',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

העובד *:worker_name* דיווח על בעיות בביצוע המשימות שהוגדרו בעבור הלקוח *:client_name* בכתובת *:job_full_address*.

*אפשרויות:*
1. *דלג על המשימות* :team_skip_comment_link (דורש כתיבת הערה ללקוח מדוע לא בוצעו)
2. *ערוך משימות*  :team_job_link (לצפייה, עריכה ומענה לכל משימה)

טלפון הלקוח: *:client_phone_number*
טלפון העובד: *:worker_phone_number*

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION,
                'description' => 'Notification to Client - Update on Comment Resolution',
                'message_en' => "Hello, *:client_name*,

We’ve added updates to the tasks on your job for *:job_service_name* scheduled for *:job_start_date_time*. Please review the latest updates and our responses to each task.

- *View Comments and Updates* :client_view_job_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, *:client_name*,

הוספנו עדכונים לביצוע המשימות בעבודה שלך לשירות *:job_service_name*, שנקבעה ל-*:job_start_date_time*. אנא עיין בעדכונים האחרונים ובתגובות שלנו לכל משימה.

- *צפה במשימות ובתשובות* :client_view_job_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ALL_COMMENTS_COMPLETED,
                'description' => 'Notification to Client - Update on Comment Resolution',
                'message_en' => 'Hello, *:worker_name*,

All tasks have been marked as completed. You can now finalize your shift.
- *Click here to finish the job* :worker_job_link
- *Contact Manager* :job_contact_manager_link if there are any issues.

Best regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

המשימות בוצעו. כעת באפשרותך לסיים את המשמרת.

- *לחץ כאן לסיום העבודה* :worker_job_link
- *צור קשר עם המנהל* :job_contact_manager_link אם יש בעיות.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Hola, *:worker_name*,

Todas las tareas han sido marcadas como completadas. Ahora puedes finalizar tu turno.
- *Haz clic aquí para terminar el trabajo* :worker_job_link
- *Contacta al gerente* :job_contact_manager_link si hay algún problema.

Saludos cordiales,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

Все задачи отмечены как выполненные. Теперь вы можете завершить смену.

- *Нажмите здесь, чтобы завершить работу* :worker_job_link
- *Связаться с менеджером* :job_contact_manager_link если возникли проблемы.

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_FOR_NEXT_JOB_ON_COMPLETE_JOB,
                'description' => 'Worker Notification for Next Job',
                'message_en' => 'Thank you, *:worker_name*!

You have a job scheduled at *:job_start_time* at the following location:

*Address:* :job_full_address
*Client:* :client_name

- *I’m On My Way* :worker_job_link
- *Contact Manager* :job_contact_manager_link if you need assistance.

Best Regards,
Broom Service Team',
                'message_heb' => 'תודה, *:worker_name*!
יש לך עבודה המתוכננת לשעה *:job_start_time* בכתובת הבאה:

*כתובת:* :job_full_address
*לקוח:* :client_name

- *אני בדרכי*  :worker_job_link
- *צור קשר עם המנהל* :job_contact_manager_link במידה ואתה זקוק לעזרה.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Gracias, *:worker_name*!
Tienes un trabajo programado a las *:job_start_time* en la siguiente ubicación:

*Dirección:* :job_full_address
*Cliente:* :client_name

- *Estoy en camino* :worker_job_link
- *Contactar al gerente* :job_contact_manager_link si necesitas ayuda.

Saludos cordiales,
Equipo Broom Service',
                'message_ru' => 'Спасибо, *:worker_name*!

У вас назначена работа на *:job_start_time* по следующему адресу:

*Адрес:* :job_full_address
*Клиент:* :client_name

- *Я в пути* :worker_job_link
- *Связаться с менеджером* :job_contact_manager_link если вам нужна помощь.

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_FINAL_NOTIFICATION_OF_DAY,
                'description' => 'Worker Final Notification of the Day (if last job)',
                'message_en' => 'Thank you for your work today, :worker_name! Have a great rest of your day.

Best regards,
Broom Service Team',
                'message_heb' => 'תודה, :worker_name! המשך יום נפלא.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Gracias por tu trabajo hoy, :worker_name! Que tengas un excelente resto del día.

Saludos cordiales,
Equipo Broom Service',
                'message_ru' => 'Спасибо, :worker_name! Приятного вам дня.

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_CONTACT_TO_MANAGER,
                'description' => 'Team Notification if Worker Contacts Manager (with Actions)',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

העובד *:worker_name* פנה בבקשה לעזרה בסיום העבודה עבור הלקוח *:client_name* בכתובת *:job_full_address*.

**אפשרויות פעולה:**
1. *סיים את העבודה עבור העובד* :team_action_btns_link
2. * ערוך עבודה/שנה מחיר* :team_job_link
טלפון הלקוח: *:client_phone_number*
טלפון העובד: *:worker_phone_number*

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER,
                'description' => 'Notification to Worker (sent 1 minute after scheduled job completion time)',
                'message_en' => 'Hello, :worker_name,

The job at :job_full_address was scheduled to be completed by :job_end_time. Please finish the job if you have completed all tasks, or contact your manager if you need assistance.
*Options:*
- Finish Job :worker_job_link
- Contact Manager :job_contact_manager_link

Best regards,
Broom Service Team',
                'message_heb' => 'היי, :worker_name,

העבודה בכתובת :job_full_address הייתה אמורה להסתיים בשעה :job_end_time. אנא סיים את העבודה אם כל המשימות הושלמו, או צור קשר עם המנהל במידת הצורך.
*אפשרויות:*
- סיים עבודה  :worker_job_link
- צור קשר עם המנהל :job_contact_manager_link

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Hola, :worker_name,

El trabajo en :job_full_address estaba programado para completarse a las :job_end_time. Por favor, finaliza el trabajo si has completado todas las tareas, o contacta a tu gerente si necesitas ayuda.
*Opciones:*
- Finalizar trabajo :worker_job_link
- Contactar al gerente :job_contact_manager_link

Saludos cordiales,
Equipo Broom Service',
                'message_ru' => 'Привет, :worker_name,

Работа по адресу :job_full_address должна была завершиться к :job_end_time. Пожалуйста, завершите работу, если все задачи выполнены, или свяжитесь с менеджером, если нужна помощь.
*Варианты:*
- Завершить работу :worker_job_link
- Связаться с менеджером :job_contact_manager_link

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOT_FINISHED_JOB_ON_TIME,
                'description' => 'Notification to Team (sent 1 minute after scheduled job completion time)',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

העובד :worker_name לא סיים את העבודה בזמן בכתובת :job_full_address.
נא לסיים את העבודה עבורו במידת הצורך או לנקוט פעולה.

**אפשרויות:**
- סיים עבודה  :team_action_btns_link
- ערוך עבודה/שנה מחיר :team_job_link

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE,
                'description' => 'Client meeting schedule message template',
                'message_en' => "Hello, *:client_name*

Just a friendly reminder that you have an upcoming appointment on *:meeting_date* between *:meeting_start_time* to *:meeting_end_time* at *:meeting_address* for *:meeting_purpose*. Click the *Accept/Reject* or *Upload file* button for Accept, Reject, Reschedule, and Upload Files.

Accept/Reject: :meeting_reschedule_link

Upload file: :meeting_file_upload_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, *:client_name*

רק תזכורת ידידותית שיש לך פגישה קרובה ב-*:meeting_date* בין *:meeting_start_time* ל-*:meeting_end_time* בכתובת *:meeting_address* עבור *:meeting_purpose*. לחץ על הלחצן *קבל/דחה* או *העלה קובץ* כדי לקבל, לדחות, לתאם מחדש ולהעלות קבצים.

קבל/דחה: :meeting_reschedule_link

העלה קובץ: :meeting_file_upload_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_MEETING_CANCELLED,
                'description' => 'Reminder to Team - Client Cancel meeting',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

לקוח בשם :client_name ביצע שינוי בפגישה שתוכננה ל :today_tommarow_or_date.

- *פעולה שבוצעה* : בוטלה
- *תאריך ושעה חדשה*: :meeting_date_time
- *מיקום*: :meet_link
- *לינק להודעה ב-CRM*: :client_detail_url

אנא ודאו שהשינויים מעודכנים ביומנים שלכם והיו ערוכים בהתאם.

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_RESCHEDULE_MEETING,
                'description' => 'Reminder to Team - Client Reschedule meeting',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

לקוח בשם :client_name ביצע שינוי בפגישה שתוכננה ל :today_tommarow_or_date.

- *פעולה שבוצעה*: תואמה מחדש
- *תאריך ושעה חדשה*: :meeting_date_time
- *מיקום*: :meet_link
- *לינק להודעה ב-CRM*: :client_detail_url

אנא ודאו שהשינויים מעודכנים ביומנים שלכם והיו ערוכים בהתאם.

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::CONTACT_ME_TO_RESCHEDULE_THE_MEETING_TEAM,
                'description' => 'Reminder to Team - Client Contact Me to Reschedule meeting',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

לקוח בשם :client_name ביקש לתאם מחדש את הפגישה שנקבעה.
הסטטוס שונה ל"ממתין" יש לפנות אליו בהקדם לתיאום מועד חדש לפגישה.

נא לעדכן לאחר קביעת הפגישה החדשה.

תודה,
צוות ברום סרוויס 🌹',
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::CONTACT_ME_TO_RESCHEDULE_THE_MEETING_CLIENT,
                'description' => 'Reminder to Client - that asked to reschedule meeting',
                'message_en' => 'Hello :client_name,

We received your request to reschedule the meeting.
A representative from our team will contact you shortly to set a new date and time that works for you.

In the meantime, feel free to read about the experiences of our satisfied customers here:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

We look forward to assisting you.

Best regards,
The Broom Service Team 🌹
www.broomservice.co.il
Phone: 03-525-70-60
office@broomservice.co.i

If you no longer wish to receive messages from us, please reply with "STOP" at any time.',
                'message_heb' => 'שלום :client_name,

קיבלנו את בקשתך לתיאום מחדש של הפגישה.
נציג מטעמנו יצור איתך קשר בהקדם על מנת לקבוע מועד חדש.

בינתיים, אנו מזמינים אותך לקרוא על חוויות של לקוחות מרוצים מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

נשמח לעמוד לשירותך,

בברכה,
צוות ברום סרוויס 🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח "הפסק" בכל עת.',
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING,
                'description' => 'Reminder to Client - Admin Reschedule meeting',
                'message_en' => "Hello, *:client_name*

Hello :client_name,

We would like to inform you that your scheduled meeting has been rescheduled to a new date.

The updated meeting is set for :meeting_date, between :meeting_start_time and :meeting_end_time at the address: :meeting_address, for a quote discussion.

Please use the links below to confirm, decline, or reschedule the meeting, or to upload any necessary files:
- *Accept/Decline*: :meeting_reschedule_link
- *Upload Files*: :meeting_file_upload_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, :client_name

ברצוננו להודיעך כי הפגישה שנקבעה עבורך שונתה למועד חדש.

הפגישה המתואמת שלך תתקיים בתאריך :meeting_date בין השעות :meeting_start_time ל-:meeting_end_time בכתובת :meeting_address עבור הצעת מחיר.
אנא לחץ על הלחצנים הבאים כדי לאשר, לדחות או לתאם מחדש את הפגישה, או להעלות קבצים במידת הצורך:

- *קבל/דחה*: :meeting_reschedule_link
- *העלה קובץ*: :meeting_file_upload_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                'description' => 'Send message to client for upload files (off site meeting)',
                'message_en' => "Hello, *:client_name*

To provide you with an accurate quote for the requested services, we kindly ask that you send us a few photos or a video of the area that needs to be cleaned. This will help us better understand your needs and prepare a detailed quote for you.

Please click on blow link and upload the requested files at your earliest convenience.

:meeting_file_upload_link

If you have any questions or need assistance, feel free to reach out to us.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, :client_name

כדי לספק לך הצעת מחיר מדויקת לשירותים המבוקשים, נשמח אם תוכל לשלוח לנו כמה תמונות או סרטון של האזור שזקוק לניקיון. כך נוכל להבין טוב יותר את הצרכים שלך ולהכין הצעת מחיר מפורטת עבורך.

אנא לחץ על הקישור למטה והעלה את הקבצים המבוקשים בהקדם האפשרי.

:meeting_file_upload_link

אם יש לך שאלות או שאתה זקוק לעזרה, אנא אל תהסס לפנות אלינו.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES,
                'description' => 'Send message to team when client upload file in meeting',
                'message_en' => '',
                'message_heb' => 'שלום, *צוות*

:client_name נוספו קבצים חדשים בפרטי הפגישה המוזכרים למטה.

תאריך/שעה: :file_upload_date

בדוק קובץ: :meeting_uploaded_file_url',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::DELETE_MEETING,
                'description' => 'Send message to client on meeting cancelled',
                'message_en' => "Hello, *:client_name*

Just a friendly reminder that your meeting *:meeting_team_member_name* on *:meeting_date* between *:meeting_start_time* to *:meeting_end_time* has been cancelled.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, *:client_name*

זוהי תזכורת לכך שהפגישה שלך *:meeting_team_member_name* ב-*:meeting_date* בין *:meeting_start_time* ל-*:meeting_end_time* בוטלה כעת.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::OFFER_PRICE,
                'description' => 'Client new price offer message template',
                'message_en' => "Hello, *:property_person_name*

Please check the price offer for the *:offer_service_names*. After your approval, an engagement agreement will be sent to you which you will need to fill out and sign below then we will be ready to start the work.
Click the below button to see the price offer.

Price Offer: :client_price_offer_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, *:property_person_name*

מצ'ב הצעת מחיר עבור *:offer_service_names*. לאחר אישורכם, יישלח אליכם הסכם התקשרות אותו תצטרכו למלא ולחתום למטה ואז נהיה מוכנים להתחיל בעבודה.
לחץ על הכפתור למטה כדי לראות את הצעת המחיר.

הצעת מחיר: :client_price_offer_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::FOLLOW_UP_REQUIRED,
                'description' => 'Notification to Team - Lead Pending Over 24 Hours-every 24h',
                'message_en' => '',
                'message_heb' => 'שלום צוות,
הליד הבא נמצא במצב "ממתין" במשך למעלה מ-24 שעות. נא לבדוק ולעדכן את הסטטוס בהתאם.

פרטי ליד:
שם ליד: :client_name
טלפון ליד: :client_phone_number
תאריך יצירת ליד: :client_create_date

אפשרויות:
עדכון סטטוס ליד :lead_detail_url
צור קשר עם ליד :client_phone_number

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED,
                'description' => 'Reminder to Team - Price Offer Sent (24 Hours, 3 Days, 7 Days)',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

חלפו :offer_pending_since מאז שנשלחה הצעת המחיר ללקוח הבא. נא לעיין בפרטי ההצעה ולעדכן את הסטטוס בהתאם או ליצור קשר עם הלקוח להמשך.

פרטי לקוח:
שם לקוח: :client_name
טלפון לקוח: :client_phone_number
תאריך הצעת המחיר: :offer_sent_date

אפשרויות:
עדכון סטטוס הצעת המחיר :offer_detail_url
צור קשר עם לקוח :client_phone_number

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT,
                'description' => 'Reminder to Client - Price Offer Sent (24 Hours, 3 Days, 7 Days)',
                'message_en' => "Hello :property_person_name,

Just a reminder that you received a price offer from us on :offer_sent_date.
Please find attached the price offer again for :price_offer_services. Once you confirm, we will send you an engagement agreement to complete and sign.

Click the button below to view the price offer.
If you have any questions or need any assistance, we are here to help.

Click here to view your price offer :client_price_offer_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום :property_person_name,

רק תזכורת לכך שקיבלת מאיתנו הצעת מחיר בתאריך :offer_sent_date.
מצ'ב שוב הצעת המחיר לשירות :price_offer_services. לאחר אישורכם, יישלח אליכם הסכם התקשרות למילוי וחתימה.

לחץ על הכפתור למטה כדי לצפות בהצעת המחיר.
אם יש לך שאלות, או לכל עניין אחר, אנו פה לשירותכם.

לחץ כאן לצפייה בהצעת המחיר שלך :client_price_offer_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED,
                'description' => 'Reminder to Client - Agreement Signature (After 24 Hours, 3 Days, and 7 Days)',
                'message_en' => "Hello :property_person_name,

Just a reminder that an engagement agreement was sent to you on :contract_sent_date.
Please find the agreement attached again. Kindly complete all details and sign where required.

Click the button below to view the agreement.
If you have any questions or need assistance, we are here to help.

Click here to view your agreement :client_contract_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום :property_person_name,

רק תזכורת לכך שנשלח אליכם הסכם התקשרות בתאריך :contract_sent_date.
מצ'ב שוב הסכם ההתקשרות. נא מלאו את כל הפרטים וחתמו במקומות הנדרשים.

לחץ על הכפתור למטה לצפייה בהסכם.
אם יש לך שאלות, או לכל עניין אחר, אנו פה לשירותכם.

לחץ כאן לצפייה בהסכם שלך :client_contract_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TO_TEAM_CONTRACT_NOT_SIGNED,
                'description' => 'Reminder to Team - Agreement Pending Signature (After 24 Hours, 3 Days, and 7 Days)',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

הסכם התקשרות נשלח ללקוח :client_name בתאריך :contract_sent_date ועדיין ממתין לחתימתו.
אנא עקבו אחר הסטטוס ובדקו אם נדרשת פעולה נוספת.

פרטי הלקוח:
- שם: :client_name
- טלפון: :client_phone_number

לחץ כאן לצפייה בהסכם :team_contract_link

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT,
                'description' => 'Notification to Team - Client Signed Agreement',
                'message_en' => '',
                'message_heb' => 'שלום צוות,

לקוח :client_name חתם על הסכם התקשרות.
אנא אימתו את ההסכם ושבצו את הלקוח בהתאם לזמינות.

*פרטי הלקוח:*
- שם: :client_name
- טלפון: :client_phone_number

לחץ כאן לצפייה בהסכם :team_contract_link

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_CLIENT,
                'description' => 'Notification to Client - Reminder for Pending Client Files (Sent after 24 hours, 3 days, and 7 days)',
                'message_en' => "Hello, :client_name,

Just a friendly reminder that we have not yet received the requested photos or video of the area needing cleaning, which are essential to prepare your quote.

Please send the files at your earliest convenience to help us provide an accurate quote and proceed with the service.

If you have any questions or requests, we’re here to assist you.

Click here to upload your photos/video :meeting_file_upload_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, :client_name,

רק תזכורת לכך שעדיין לא קיבלנו ממך תמונות או סרטון לצורך הצעת המחיר.

נא שלחו את התמונות או הסרטון בהקדם כדי שנוכל לספק הצעת מחיר מדויקת ולהתקדם בתהליך.

אם יש לך שאלות או בקשות, אנו פה לשירותך.

לחץ כאן לשליחת התמונות/סרטון :meeting_file_upload_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_TEAM,
                'description' => 'Notification to Team - Reminder for Pending Client Files (Sent after 24 hours, 3 days, and 7 days)',
                'message_en' => '',
                'message_heb' => "שלום צוות,

הלקוח :client_name עדיין לא שלח תמונות או סרטון של האזור הנדרש לניקוי.
אנא עקבו אחר הלקוח לבדיקת סטטוס והשלמת הפרטים לצורך מתן הצעת המחיר.

פרטי הלקוח:
    • שם: :client_name
    • טלפון: :client_phone_number

בברכה,
צוות ברום סרוויס",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE,
                'description' => 'Every Monday, send a notification to all clients and workers asking if they have any changes to their schedule for the following week or if they would like to keep the same schedule. Also, notify them if there is any holiday during that week.',
                'message_en' => "Dear Clients, good morning,

Today is Monday, and we’re finalizing the work schedule for next week. If you have any constraints, changes, or special requests, please send them to us by the end of the day.

For any questions or requests, we’re here to assist you.

*Click here to send a message regarding a change or request* :request_change_schedule

Wishing you a wonderful day! 🌸
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "לקוחות יקרים, בוקר טוב,

היום יום שני, ואנו סוגרים סידור עבודה לשבוע הבא. במידה ויש לכם אילוצים, שינויים או בקשות מיוחדות, נבקש להעבירם עוד היום.

לכל שאלה או בקשה, אנו פה לשירותכם.

*לחץ כאן לשליחת הודעה על שינוי או בקשה* :request_change_schedule

המשך יום נהדר! 🌸
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE,
                'description' => 'Every Monday, send a notification to all workers and workers asking if they have any changes to their schedule for the following week or if they would like to keep the same schedule. Also, notify them if there is any holiday during that week.',
                'message_en' => "Hi :worker_name, how are you?

Do you need any day or half-day off next week?
We need to finalize next week’s schedule today, so please let us know as soon as possible if you have any specific requirements.

If your schedule is the same as last week, no changes are needed.
*Click here to request a change in your schedule* :request_change_schedule

Best Regards,
Broom Service Team",
                'message_heb' => "שלום :worker_name,

האם אתה זקוק ליום חופש מלא או חצי יום חופש בשבוע הבא?
אנו סוגרים את סידור העבודה להיום ונבקש לדעת בהקדם אם יש לך בקשות מיוחדות.

אם הלוז שלך נשאר כמו שבוע שעבר, אין צורך בשינוי.
*לחץ כאן לבקשת שינוי בלוח הזמנים שלך* :request_change_schedule

בברכה,
צוות ברום סרוויס",
                'message_spa' => 'Hola :worker_name, ¿cómo estás?

¿Necesitas algún día o medio día libre la próxima semana?
Necesitamos finalizar el cronograma de la próxima semana hoy, así que avísanos lo antes posible si tienes algún requisito específico.

Si tu cronograma es el mismo que el de la semana pasada, no es necesario realizar cambios.
*Haz clic aquí para solicitar un cambio en tu cronograma* :request_change_schedule

Saludos cordiales,
Equipo de servicio de escobas',
                'message_ru' => 'Привет, :worker_name  ,

Вам нужен полный или половина выходного дня на следующей неделе?
Сегодня мы завершаем планирование графика на следующую неделю, поэтому, пожалуйста, сообщите нам как можно скорее, если у вас есть особые пожелания.

Если ваш график остается таким же, как на прошлой неделе, изменений не требуется.
*Нажмите здесь, чтобы запросить изменение в вашем графике* :request_change_schedule

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_CLIENT,
                'description' => 'notify team, client requested to change schedule',
                'message_en' => '',
                'message_heb' => "שלום צוות,

התקבלה בקשת שינוי מסידור העבודה מצד הלקוח הבא:

- *שם הלקוח:* :client_name
- *מספר טלפון:* :client_phone_number
- *פרטי הבקשה:* :request_details

אנא בדקו את הבקשה ובצעו את השינויים הנדרשים בהתאם.
במידה ויש שאלות או צורך בפעולה נוספת, ניתן ליצור קשר עם הלקוח ישירות.

בברכה,
צוות ברום סרוויס",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_WORKER,
                'description' => 'notify team, worker requested to change schedule',
                'message_en' => '',
                'message_heb' => "שלום צוות,

התקבלה בקשת שינוי מסידור העבודה מצד הלקוח הבא:

- *שם הלקוח:* :worker_name
- *מספר טלפון:* :worker_phone_number
- *פרטי הבקשה:* :request_details

אנא בדקו את הבקשה ובצעו את השינויים הנדרשים בהתאם.
במידה ויש שאלות או צורך בפעולה נוספת, ניתן ליצור קשר עם הלקוח ישירות.

בברכה,
צוות ברום סרוויס",
                'message_spa' => '',
                'message_ru' => '',
            ],






            [
                'key' => WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT,
                'description' => 'notification send when worker lead webhook status is irrelevant',
                'message_en' => "Hello :worker_name,

🌟 Thank you for contacting us at Job4Service.

We offer the best jobs in the house cleaning industry in Israel.
We hire only people with suitable visas for work in Israel.
We offer house cleaning jobs only in the Tel Aviv area, and only during weekday mornings. We do not work on weekends or in the evenings.
We are a professional cleaning team, so we hire only people with experience in house cleaning.
If this may suit you or your friends now or in the future, you are more than welcome to contact us again. 😀
👫 Know someone who'd be a great fit for our team? Invite them to join this group and explore the opportunities with us! Just send them this link:

https://chat.whatsapp.com/H0dpX0ERLNRAbM8ejgjT
https://t.me/+m84PexCmLjs0MmZk\nhttps://www.facebook.com/JobinIsraelforubr

Have a wonderful day!🌟",
                'message_heb' => "שלום :worker_name,

🌟 תודה שפנית אלינו ב-Job4Service.

אנחנו מציעים את המשרות הטובות ביותר בענף ניקיון בתים בישראל.
אנחנו שוכרים רק אנשים עם אשרות מתאימות לעבודה בישראל.
אנחנו מציעים עבודות ניקיון בתים רק באזור תל אביב, ורק בשעות הבוקר של ימי חול אנו לא עובדים בסופי שבוע או בערבים.
אנחנו צוות ניקיון מקצועי, ולכן אנחנו שוכרים רק אנשים עם ניסיון בניקיון בתים.
אם זה יכול להתאים לכם או. החברים שלכם עכשיו או בעתיד, אתם יותר ממוזמנים לפנות אלינו שוב 😀
👫 מכירים מישהו שיתאים מאוד לצוות שלנו. פשוט שלח להם את הקישור הזה:

https://chat.whatsapp.com/H0dpX0ERLNRAbM8ejgjT
https://t.me/+m84PexCmLjs0MmZk
https://www.facebook.com/JobinIsraelforubr

שיהיה לך יום נפלא !🌟",
                'message_spa' => "Hola :worker_name

🌟 Gracias por contactarnos en Job4Service.

Ofrecemos los mejores trabajos en la industria de limpieza de casas en Israel.
Solo contratamos personas con visas adecuadas para trabajar en Israel.
Ofrecemos trabajos de limpieza de casas solo en el área de Tel Aviv, y solo durante las mañanas de lunes a viernes. No trabajamos los fines de semana ni por las noches.
Somos un equipo de limpieza profesional, por lo que solo contratamos personas con experiencia en limpieza de casas.
Si esto le conviene. tus amigos ahora o en el futuro, eres más que bienvenido a contactarnos nuevamente 😀

👫 ¿Conoces a alguien que encajaría perfectamente en nuestro equipo? Invítalo a unirse a este grupo y explorar las oportunidades con nosotros. Solo envíales este enlace:
https://chat.whatsapp.com/H0dpX0ERLNRAbM8ejgjT
https://t.me/+m84PexCmLjs0MmZk
https://www.facebook.com/JobinIsraelforubr

Que tengas un día maravilloso !🌟",
                'message_ru' => "Привет :worker_name,

🌟 Спасибо, что связались с нами в Job4Service

Мы предлагаем лучшие вакансии в сфере уборки домов в Израиле.
Мы нанимаем только людей с подходящими визами для работы в Израиле.
Мы предлагаем работу по уборке домов только в районе Тель-Авива, и только по утрам в будние дни. Мы не работаем по выходным или вечерам.
Мы профессиональная команда по уборке, поэтому нанимаем только людей с опытом работы в этой сфере.
Если это может подойти вам или вашим друзьям сейчас или в будущем, вы всегда можете связаться с нами снова. 😀

👫 Знаете кого-то, кто идеально подойдет для нашей команды? Пригласите их присоединиться к этой группе и исследовать возможности с нами! Просто отправьте им эту ссылку:

https://chat.whatsapp.com/H0dpX0ERLNRAbM8ejgjT
https://t.me/+m84PexCmLjs0MmZk
https://www.facebook.com/JobinIsraelforubr

Для получения дополнительной информации, не стесняйтесь обращаться к нам.

Хорошего дня! 🌟",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER,
                'description' => 'notify team, Lead accepted price offer',
                'message_en' => "Hello Team,

:client_name has accepted the price offer. Please ensure that the contract is signed and all necessary details are confirmed so we can proceed with scheduling the service.

View details :lead_detail_url

Thank you,
Broom Service Team",
                'message_heb' => "שלום צוות,

:client_name קיבל את ההצעת מחיר. אנא ודאו שהחוזה נחתם וכל הפרטים הנדרשים מאושרים כדי שנוכל להתקדם בתכנון השירות.

הצג פרטים :lead_detail_url

תודה,
שירות ברום",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::LEAD_DECLINED_PRICE_OFFER,
                'description' => 'notify team, Lead declined price offer',
                'message_en' => "Hello Team,

The following client has declined the price offer for the service:

Client Details:
- Name: :client_name
- Reason for Decline: :reason

View details :lead_detail_url

Please review the details and update the status accordingly.

Thank you,
Broom Service Team",
                'message_heb' => "שלום צוות,

הלקוח הבא דחה את הצעת המחיר עבור השירות:

פרטי הלקוח:
- שם: :client_name
- סיבת הסירוב: :reason

הצג פרטים :lead_detail_url

אנא בדקו את הפרטים ועדכנו את הסטטוס בהתאם.

תודה,
צוות שירות ברום",
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_DECLINED_PRICE_OFFER,
                'description' => 'notify client, Already accepted price offer, then declined price offer',
                'message_en' => "Hello :client_name,

We have received your response regarding the price offer sent to you.
If there is anything else we can do for you or if you have any additional questions, we are here to assist.

Please feel free to contact us for any inquiries.

We look forward to assisting you.

Best regards,
The Broom Service Team 🌹
www.broomservice.co.il
Phone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום :client_name,

קיבלנו את תגובתך בהמשך להצעת המחיר שנשלחה אליך.
נשמח לדעת אם יש משהו נוסף שנוכל לעשות עבורך או אם יש לך שאלות נוספות שנוכל לסייע בהן.

אנו כאן לשירותך ומזמינים אותך ליצור איתנו קשר בכל נושא.

נשמח לעמוד לשירותך.

בברכה,
צוות ברום סרוויס 🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::LEAD_DECLINED_CONTRACT,
                'description' => 'notify team, Lead declined contract',
                'message_en' => "Hello Team,

Thank you,
Broom Service Team",
                'message_heb' => "שלום צוות,

הלקוח הבא סירב לחתום על החוזה לשירות:

פרטי הלקוח:
- שם: :client_name
- סיבת הסירוב: :reason

הצג פרטים :lead_detail_url

אנא בדקו את הפרטים ועדכנו את הסטטוס בהתאם.

תודה,
צוות שירות ברום",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_DECLINED_CONTRACT,
                'description' => 'notify client, Client declined contract',
                'message_en' => "Hello :client_name,  

We have received your response regarding the agreement sent to you. Thank you for taking the time to review it.  

If you have any questions or if there’s anything further we can do to assist you, please don’t hesitate to reach out to us.  

We are here to help and look forward to assisting you in the future.  

Best regards,  
Broom Service 🌹  
www.broomservice.co.il  
Telephone: 03-525-70-60  
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום :client_name,  

קיבלנו את תגובתך בנוגע להסכם ההתקשרות שנשלח אליך. 

אם יש לך שאלות נוספות או אם יש משהו נוסף שנוכל לסייע בו, נשמח לעמוד לשירותך בכל עת.  


בברכה,  
צוות ברום סרוויס 🌹  
www.broomservice.co.il  
טלפון: 03-525-70-60  
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS,
                'description' => 'notify team, Client is in freeze status',
                'message_en' => "Hello :client_name,

At Broom Service, we understand that sometimes there’s a need to take a break, and we want to thank you for the trust you have placed in us so far.
We wanted to remind you that we are here for you and ready to resume services whenever you decide. We continue to improve and expand our service offerings to ensure that you always receive the best.

If your needs have changed or if you would like to discuss new options, we are here at your service. Feel free to reach out anytime.

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום :client_name,

אנו בברום סרוויס מבינים שלפעמים יש צורך לעשות הפסקה, ואנו רוצים להודות לכם על האמון שהענקתם לנו עד כה.
רצינו להזכיר לכם שאנו כאן בשבילכם ומוכנים לחדש את השירות בכל עת שתחליטו. אנו ממשיכים לשפר ולהרחיב את מגוון השירותים שלנו כדי להבטיח שתמיד תקבלו את הטוב ביותר.

אם יש שינוי בצרכים שלכם או שאתם מעוניינים לדון באפשרויות חדשות, אנו כאן לשירותכם. אל תהססו ליצור קשר בכל עת.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_LEAD_STATUS_CHANGED,
                'description' => 'notify team, when Lead status changed',
                'message_en' => "Hello Team,

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il",
                'message_heb' => "שלום צוות,

הסטטוס של :client_name שונה ל- :new_status.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::PENDING,
                'description' => 'Send message to team when lead is pending',
                'message_en' => 'Hello, *Team*,

New lead alert! A potential client, :client_name, has been added to the system and is awaiting initial contact.

Phone: :client_phone_number.
Click here to take action: :lead_detail_url',

                'message_heb' => 'שלום, *צוות*

"הלקוח :client_name קיבל את הצעת המחיר ואת החוזה.
נא להמשיך בשלבים הבאים.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::POTENTIAL,
                'description' => 'Send message to team when lead is potential',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

עדכון: פגישה נקבעה או סרטון הוזמן מ:client_name. נא להיערך בהתאם.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::IRRELEVANT,
                'description' => 'Send message to team when lead is irrelevant',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

עדכון סטטוס: הליד :client_name סומן כלא רלוונטי בשל חוסר התאמה לשירות או מגבלת מיקום.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::UNINTERESTED,
                'description' => 'Send message to team when lead is unintrested',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח הפוטנציאלי :client_name הביע חוסר עניין בהמשך.
נא לסמן כהושלם או לסגור את הליד.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::UNANSWERED,
                'description' => 'Send message to team when lead is unanswered',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הודעה: הלקוח הפוטנציאלי :client_name לא השיב לאחר ניסיונות יצירת קשר מרובים.
נא לבדוק ולבצע מעקב בהתאם לצורך.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::POTENTIAL_CLIENT,
                'description' => 'Send message to team when lead is potential client',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח הפוטנציאלי :client_name קיבל הצעת מחיר ושוקל אותה.
ממתינים להחלטתו.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::PENDING_CLIENT,
                'description' => 'Send message to team when lead is pending_client',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :name קיבל את הצעת המחיר ואת החוזה.
נא להמשיך בשלבים הבאים.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WAITING,
                'description' => 'Send message to team when lead is waiting',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name חתם על החוזה וממתין להזמנה הראשונה.
נא לתאם את השירות בהקדם האפשרי.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::ACTIVE_CLIENT,
                'description' => 'Send message to team when lead is active_client',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

עדכון: הלקוח :client_name פעיל כעת ומקבל שירותים.
יש לעדכן את הצוות ולהתכונן למפגשים הקרובים.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::FREEZE_CLIENT,
                'description' => 'Send message to team when lead is freeze_client',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

התראה: הלקוח :client_name סומן כ’בהקפאה’ מכיוון שעברו 7 ימים ללא קבלת שירות.
נא לבדוק עם הלקוח ולפתור כל בעיה קיימת.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::UNHAPPY,
                'description' => 'Send message to team when lead is unhappy',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name סומן כ’לא מרוצה’ בשל חוסר שביעות רצון מאיכות השירות.
נא לבדוק אם נדרשת פעולה מתקנת.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::PRICE_ISSUE,
                'description' => 'Send message to team when lead is price_issue',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name סומן כ’בעיית מחיר’ בשל דאגות הנוגעות למחיר.
שקלו לבחון מחדש את אסטרטגיית התמחור במידת הצורך.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::MOVED,
                'description' => 'Send message to team when lead is moved',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name סומן כ’עבר דירה’ מכיוון שעבר לאזור שאינו בתחום השירות.
אין צורך בפעולה נוספת אלא אם כן יחזור.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::ONETIME,
                'description' => 'Send message to team when lead is onetime',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name סומן כ’חד-פעמי’ מכיוון שהשתמש בשירות רק פעם אחת.
אנא קחו זאת בחשבון למעקב עתידי או מבצעים.

טלפון: :client_phone_number.
לחץ כאן כדי לפעול: :lead_detail_url',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_DISCOUNT,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הזמנה (:order_id) נוצרה עבור :client_name עם הנחה של ₪:discount ובסך הכל ₪:total לאחר ההנחה.

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_EXTRA,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הזמנה (:order_id) נוצרה עבור :client_name עם הנחה של ₪:discount ובסך הכל ₪:total לאחר ההנחה.

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

קבלה לחשבונית (:invoice_id) נוצרה עבור :client_name,

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

חשבונית (:invoice_id) נוצרה ונשלחה ל- :client_name.

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::PAYMENT_PAID,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name ביצע תשלום.

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::PAYMENT_PARTIAL_PAID,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

הלקוח :client_name ביצע תשלום.

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::ORDER_CANCELLED,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

ההזמנה של הלקוח :client_name (:order_id) בוטלה.

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

התשלום של :client_name עם הכרטיס [**** **** **** :card_number] נכשל.

:admin_add_client_card

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED_TO_CLIENT,
                'description' => 'Send message to client to arrange a callbac',
                'message_en' => "Hello, *:client_name*,

Greetings from Broom Service

Your payment with card [**** **** **** :card_number] has failed. Please add a new card.

:client_card

Best regards,
Broom Service Team
📞 03-525-70-60
🌐 www.broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

התשלום עם כרטיס [**** **** ****:card_number] נכשל. אנא עדכנו לכרטיס תקין או צרו איתנו קשר בהקדם.

:client_card

בברכה,
ברום סרוויס צוות
📞 03-525-70-60
🌐 www.broomservice.co.i

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_LEAVES_JOB,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => "Hello, *Team*,

Worker :worker_name's leave job date is set to :date

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

העובד :worker_name קבע תאריך לעזיבת עבודה ל-:last_work_date.

בברכה,
ברום סרוויס צוות",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => "Hello, *Team*,


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

:worker_name שינה זמינות שמשפיעה על עבודה ב-:date.

בברכה,
ברום סרוויס צוות",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_FORMS,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => "Hello, *:worker_name*,

You have successfully registered on our portal. Please sign the below forms to start working.
Click the below button to fill forms.

Check Forms: :check_form

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *:worker_name*

נרשמת בהצלחה לפורטל שלנו. אנא חתום על הטפסים למטה כדי להתחיל לעבוד בעבודה.
לחץ על הכפתור למטה כדי למלא את הטפסים.

בדוק טפסים: :check_form

בברכה,
ברום סרוויס צוות",

                'message_spa' => "Hola, *:worker_name*

Te has registrado exitosamente en nuestro portal. Por favor, firma los siguientes formularios para comenzar a trabajar.
Haz clic en el botón de abajo para completar los formularios.

Consultar formularios: :check_form

Saludos cordiales,
Equipo de Broom Service",
                'message_ru' => "Привет, *:worker_name*

Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите приведенные ниже формы, чтобы начать работу.
Нажмите кнопку ниже, чтобы заполнить формы.

Проверить формы: :check_form

С уважением,
Команда Broom Service",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::SEND_TO_WORKER_PENDING_FORMS,
                'description' => 'Send reminder to worker to fill Pending forms',
                'message_en' => "Hello, *:worker_name*,

Please sign the below forms to start working.
Click the below button to fill forms.

Check Forms: :check_form

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *:worker_name*

אנא חתום על הטפסים למטה כדי להתחיל.
לחץ על הכפתור למטה כדי למלא את הטפסים.

בדוק טפסים: :check_form

בברכה,
ברום סרוויס צוות",

                'message_spa' => "Hola, *:worker_name*

Firme los formularios para comenzar.
Haga clic en el botón a continuación para completar los formularios.

Consultar formularios: :check_form

Saludos cordiales,
Equipo de Broom Service",
                'message_ru' => "Привет, *:worker_name*

Пожалуйста, подпишите формы ниже, чтобы начать.
Нажмите кнопку ниже, чтобы заполнить формы.

Проверить формы: :check_form

С уважением,
Команда Broom Service",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK,
                'description' => 'Send message to team to arrange a callbac',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

אין פגישות זמינות. אנא תאם שיחה חוזרת עבור :client_name.

צפה בלקוח: :client_detail_url

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE,
                'description' => 'Send message to team when lead need human representative',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => 'שלום, *צוות*

:client_name רוצה לדבר עם נציג אנושי.

צפה בלקוח: :client_detail_url

בברכה,
ברום סרוויס צוות',

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION,
                'description' => 'Send message to team when lead need human representative',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => "שלום, *צוות*

עובד שינה את סטטוס העבודה ל-:job_status. אנא בדוק את הפרטים למטה.

תאריך/שעה: :job_start_date_time
עובד: :worker_name
לקוח: :client_name
שירות: :job_service_name
סטטוס: :job_status

צפה בעבודה :worker_job_link

בברכה,
ברום סרוויס צוות",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT,
                'description' => 'Send message to client when Contract is verified',
                'message_en' => "Hello *:property_person_name*

Your agreement has been successfully confirmed. We will contact you soon to schedule your service.

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום *:property_person_name*',

ההסכם שלך אומת בהצלחה. ניצור איתך קשר בקרוב לתיאום השירות.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM,
                'description' => 'Send message to team when Contract is verified',
                'message_en' => 'Hello, *Team*,

               ',

                'message_heb' => "שלום, *צוות*

הלקוח :client_name חתם ואימת את ההסכם. יש לבצע שיבוץ בהקדם האפשרי

:create_job

בברכה,
ברום סרוויס צוות",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CONTRACT,
                'description' => 'Send message to client when Contract is sent',
                'message_en' => "Hello :property_person_name

Greetings from Broom Service.

A work agreement for digital signature is attached. The credit card must be added to the payment, together with the cardholder's signature confirming that it will be charged on the billing date. The card will be charged NIS 1 and then credited, to verify its integrity. The details will be stored in a secure system. In addition, you must sign the last page and confirm the agreement.

Check Contract: :client_contract_link

contact us: 03-525-70-60 or reply to this email.

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:property_person_name*

מצורף בזאת הסכם התקשרות לחתימה דיגיטלית. יש להוסיף את כרטיס האשראי לתשלום, בצירוף חתימת בעל הכרטיס המאשר לחייבו במועד החיוב. הכרטיס יחויב בסכום של 1 ש\"ח ולאחר מכן יזוכה, זאת כדי לוודא את תקינותו. הפרטים יישמרו במערכת מאובטחת. בנוסף, יש לחתום בעמוד האחרון ולאשר את ההסכם.

בדוק חוזה: :client_contract_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CREATE_JOB,
                'description' => 'Send message to client when job is created',
                'message_en' => "Hello :property_person_name

A service has been scheduled for you: *:job_service_name* on *:job_start_date* at *:job_start_time*
Please note that the estimated arrival time of our team can be up to an hour and a half from the scheduled start time.

For any questions or requests, feel free to contact us.

View Job: :client_view_job_link

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:property_person_name*

נקבע עבורך שירות :job_service_name בתאריך :job_start_date בשעה :job_start_time.

לתשומת לבך, זמן ההגעה המשוער של הצוות יכול להיות עד שעה וחצי מזמן ההתחלה שתואם.

לכל שאלה או בקשה, נשמח לעמוד לשירותך.

צפה בעבודה: :client_view_job_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",

                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED,
                'description' => 'Review message for client after job completion',
                'message_en' => "Hello, *:property_person_name*

We hope you enjoyed the service provided by our team.

We value your feedback and would love to hear about your experience. Your review helps us maintain our high standards and ensure every visit meets your expectations.

Please take a moment to rate us and share your thoughts.

*Click here to leave a review* :client_job_review

Thank you for choosing Broom Service!

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, *:property_person_name*

אנו מקווים שנהניתם מהשירות שניתן על ידי הצוות שלנו.

נשמח לשמוע את דעתכם ועל החוויה שלכם. המשוב שלכם חשוב לנו כדי לשמור על הסטנדרטים הגבוהים שלנו ולוודא שכל ביקור יעמוד בציפיותיכם.

נשמח אם תקדישו רגע לדרג את השירות ולשתף את מחשבותיכם.

*לחצו כאן להשארת חוות דעת* :client_job_review

תודה שבחרתם בברום סרוויס!

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:client_name*

Just a friendly reminder that you have an upcoming appointment on *:meeting_date* between *:meeting_start_time* to *:meeting_end_time* at *:meeting_address* for *:meeting_purpose*.
Click the *Accept/Reject* or *Upload file* button for Accept, Reject, Reschedule, and Upload Files.

Accept/Reject: :meeting_reschedule_link

Upload file: :meeting_file_upload_link

Best regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",
                'message_heb' => "שלום, *:client_name*

רק תזכורת ידידותית שיש לך פגישה קרובה ב-*:meeting_date* בין *:meeting_start_time* ל-*:meeting_end_time* בכתובת *:meeting_address* עבור *:meeting_purpose*.
לחץ על הלחצן *קבל/דחה* או *העלה קובץ* כדי לקבל, לדחות, לתאם מחדש ולהעלות קבצים.

קבל/דחה: :meeting_reschedule_link

העלה קובץ: :meeting_file_upload_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::FORM101,
                'description' => 'Send message to worker for send form 101 request',
                'message_en' => "Hello, *:worker_name*

You have successfully registered on our portal. Please sign the Form 101 to start working on the job.

Click the below button to fill Form101.

Form 101: :form_101_link

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *:worker_name*

נרשמת בהצלחה בפורטל שלנו. נא לחתום על טופס 101 כדי להתחיל לעבוד.

לחץ על הלחצן למטה כדי למלא טופס 101.

טופס 101: :form_101_link

בברכה,
ברום סרוויס צוות",
                'message_spa' => "Hola, *:worker_name*

Te has registrado exitosamente en nuestro portal. Por favor, firma el Formulario 101 para comenzar a trabajar en el trabajo.

Haz clic en el botón de abajo para completar el Formulario 101.

Saludos cordiales,
Equipo de Broom Service",
                'message_ru' => "Привет, *:worker_name*

Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите форму 101, чтобы начать работу.

Нажмите кнопку ниже, чтобы заполнить форму 101.

С уважением,
Команда Broom Service",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NEW_JOB,
                'description' => 'Send job reminder to worker on new job assign',
                'message_en' => "Hello, *:worker_name*

:job_content_txt Please check the details.

Date/Time: :job_start_date_time
Client: :client_name
Service: :job_service_name
Property: :job_full_address
Status: :job_status

View Job: :worker_job_detail_link

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *:worker_name*

:job_content_txt אנא בדוק את הפרטים.

תאריך/שעה: :job_start_date_time
לקוח: :client_name
שירות: :job_service_name
נכס: :job_full_address
סטטוס: :job_status

הצג עבודה: :worker_job_link

בברכה,
ברום סרוויס צוות",

                'message_spa' => "Hola, *:worker_name*

:job_content_txt Por favor, revisa los detalles.

Fecha/Hora: :job_start_date_time
Cliente: :client_name
Servicio: :job_service_name
Propiedad: :job_full_address
Estado: :job_status

Ver Trabajo: :worker_job_link

Saludos cordiales,
Equipo de Broom Service",

                'message_ru' => "Привет, *:worker_name*

:job_content_txt Пожалуйста, проверьте детали.

Дата/Время: :job_start_date_time
Клиент: :client_name
Услуга: :job_service_name
Собственность: :job_full_address
Статус: :job_status

Просмотреть работу: :worker_job_link

С уважением,
Команда Broom Service",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE,
                'description' => 'Send job reminder to worker on new job assign',
                'message_en' => "Hello, *:worker_name*

Just a friendly reminder that your meeting *:team_name* on *:date* between *:start_time* to *:end_time* has been scheduled.

Accept/Reject :worker_hearing

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *:worker_name*

רק תזכורת ידידותית לכך שהפגישה שלך *:team_name* ב-*:date* בין *:start_time* עד *:end_time* נקבעה.

קבל/דחה :שמוע_עובד

בברכה,
ברום סרוויס צוות",

                'message_spa' => "Hola, *:worker_name*

Solo un recordatorio amistoso de que su reunión *:team_name* para el *:date* entre *:start_time* y *:end_time* ha sido programada.

Aceptar/Rechazar :worker_hearing

Saludos cordiales,
Equipo de Broom Service",

                'message_ru' => "Привет, *:worker_name*

Просто дружеское напоминание, что ваша встреча *:team_name* на *:date* между *:start_time* и *:end_time* запланирована.

Принять/Отклонить :worker_hearing

С уважением,
Команда Broom Service",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_UNASSIGNED,
                'description' => 'Send job reminder to worker on new job assign',
                'message_en' => "Hello, *:old_worker_name*

You have been unassigned from a job. Please check the details.

Date: :old_job_start_date
Client: :client_name
Service: :old_worker_service_name
Start Time: :old_job_start_time

Best regards,
Broom Service Team",

                'message_heb' => "שלום, *:old_worker_name*

הוסרת ממשימה. אנא בדוק את הפרטים.

תאריך: :old_job_start_date
לקוח: :client_name
שירות: :old_worker_service_name
זמן התחלה: :old_job_start_time

בברכה,
ברום סרוויס צוות",

                'message_spa' => "Hola, *:old_worker_name*

Tu trabajo ha sido cancelado. Por favor, revisa los detalles.

Fecha: :old_job_start_date
Cliente: :client_name
Servicio: :old_worker_service_name
Hora de Inicio: :old_job_start_time

Saludos cordiales,
Equipo de Broom Service",

                'message_ru' => "Привет, *:old_worker_name*

Ваша работа была отменена. Пожалуйста, проверьте детали.

Дата: :old_job_start_date
Клиент: :client_name
Услуга: :old_worker_service_name
Время начала: :old_job_start_time

С уважением,
Команда Broom Service",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION,
                'description' => 'Send job reminder to client on job cancel',
                'message_en' => "Hello, *:property_person_name*

The service has been canceled. Please check the details.

Date/Time: :job_start_date_time
Client: :client_name
Service: :job_service_name
Comment: :comment

View Job :client_view_job_link

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:property_person_name*

השירות בוטל. אנא בדוק את הפרטים.

תאריך/שעה: :job_start_date_time
לקוח: :client_name
שירות: :job_service_name
הערה: :comment

צפה בעבודה :client_view_job_link

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::ADMIN_JOB_STATUS_NOTIFICATION,
                'description' => 'Send job reminder to admin on job cancel',
                'message_en' => "Hello, *Team*


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

העבודה בוטלה. אנא בדוק את הפרטים.

תאריך/שעה: :job_start_date_time
לקוח: :client_name
עובד: :worker_name
שירות: :job_service_name
סטטוס: :job_status
הערה: :comment

צפה בעבודה :team_job_link

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_REVIEWED,
                'description' => 'Client review message template',
                'message_en' => "Hello, *Team*


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

:client_name נתן דירוג של :rating עבור עבודה בתאריך :job_start_date_time.

-: :review

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_CHANGED_JOB_SCHEDULE,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *Team*


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

הלקוח :client_name שינה את לוח הזמנים לעבודה בתאריך :job_start_date_time.

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],



            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_COMMENTED,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *Team*


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

הלקוח :client_name השאיר תגובה לעבודה בתאריך :job_start_date_time.

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::ADMIN_COMMENTED,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *Team*


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

:admin_name השאיר תגובה עבור עבודה בתאריך :job_start_date_time.

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *Team*


Best regards,
Broom Service Team",

                'message_heb' => "שלום, *צוות*

ליד חדש התקבל עם הפרטים הבאים:
שם: :client_name
איש קשר: :client_phone_number
שירות שהתבקש:
דוא'ל: :client_email
כתובת: :client_address
הגיע מ: :came_from

אנא פנו בהקדם האפשרי.

צפה בפרטי הליד: :lead_detail_url
התקשר לליד כעת: :client_phone_number

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::UNANSWERED_LEAD,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:client_name*

Thank you for reaching out to us at Broom Service. We wanted to let you know that we tried to contact you but were unable to reach you. We are here and available to assist you from Sunday to Thursday, between 8:00 AM and 4:00 PM.
Alternatively, we would be happy to know when it would be convenient for you to have us call you during our business hours.

We look forward to assisting you.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

בהמשך לפנייתך אלינו בברום סרוויס, רצינו ליידע אותך שניסינו ליצור איתך קשר ולא הצלחנו להשיג אותך. אנו כאן וזמינים לעמוד לשירותך בימים א'-ה' בין השעות 8:00 ל-16:00.
לחלופין, נשמח לדעת מתי יהיה נוח לך שנתקשר אליך במהלך שעות הפעילות שלנו.

נשמח לעמוד לשירותך.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::INQUIRY_RESPONSE,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:client_name*

Thank you for your interest in Broom Service. We have reviewed your inquiry, and unfortunately, we do not provide services in your area or offer the specific service you are looking for.

Our service areas include:
- Tel Aviv
- Ramat Gan
- Givatayim
- Kiryat Ono
- Ganei Tikva
- Ramat Hasharon
- Kfar Shmaryahu
- Rishpon
- Herzliya

If you need our services in the future or if you are in one of these areas, we would be happy to assist you.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

תודה על התעניינותך בשירותי ברום סרוויס. בדקנו את פנייתך, ולצערנו, אנו לא מספקים שירותים באזור מגוריך או את השירות המסוים שאתה מחפש.

אזורי השירות שלנו כוללים:
- תל אביב
- רמת גן
- גבעתיים
- קריית אונו
- גני תקווה
- רמת השרון
- כפר שמריהו
- רשפון
- הרצליה

אם בעתיד תצטרך את שירותינו או אם אתה נמצא באחד מהאזורים הללו, נשמח לעמוד לשירותך.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::PAST,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:client_name*

At Broom Service, we want to thank you for the trust you placed in us in the past and remind you that we are always here for you.

If you would like to reconnect and enjoy our professional and high-quality cleaning services, we are at your service. We would be happy to talk with you and tailor our services to your unique needs.
Additionally, we would like to offer you a 20% discount on your next visit.

Feel free to contact us anytime.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

אנו בברום סרוויס רוצים להודות לכם על האמון שהענקתם לנו בעבר ולהזכיר לכם שאנו תמיד כאן בשבילכם.

אם ברצונכם לחדש את הקשר וליהנות משירותי ניקיון מקצועיים ואיכותיים, אנו כאן לשירותכם. נשמח לשוחח איתכם ולהתאים את השירות לצרכים הייחודיים שלכם.
בנוסף, נשמח להציע לכם הנחה של 20% על הביקור הבא שתזמינו.

אל תהססו ליצור קשר איתנו בכל עת.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:client_name*

Just a friendly reminder that your scheduled service with Broom Service will take place next week. If you need to make any changes or cancellations, please do so by Wednesday. After Wednesday, any cancellation may incur fees according to our policy.

Change Service Date :client_jobs

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

רק תזכורת ידידותית לכך שהשירות המתוכנן שלך עם ברום סרוויס יתקיים בשבוע הבא. אם יש צורך לבצע שינויים או ביטולים, אנא עשו זאת עד יום רביעי. לאחר יום רביעי, ביטולים עלולים לגרור חיובים בהתאם למדיניות שלנו.

שנה תאריך שירות :client_jobs

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:client_name*,

First of all, thank you for reaching out to us. It was a pleasure to meet and talk with you.

Following our conversation, I am attaching for your review some testimonials from our existing clients, so you can get an idea of the excellent service we provide:
Client Testimonials :testimonials_link

Additionally, I am attaching our Service Brochure for you to review the services we offer.
:broom_brochure

At Broom Service, we are committed to quality, professionalism, and personalized service.

I am here to help and answer any further questions you may have,
I am always at your service.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

ראשית, תודה שפנית אלינו. שמחתי מאוד להכיר ולשוחח איתך.

בהמשך לשיחתנו, מצרפת לעיונך המלצות מלקוחות קיימים שלנו, למען התרשמותך מהשירות המעולה שלנו:
:testimonials_link
המלצות מלקוחות קיימים

כמו כן, מצורף לעיונך ספרון השירותים שלנו כדי להתרשם מהשירותים שאנו מציעים.
:broom_brochure

בברום סרוויס, אנו מתחייבים לאיכות, מקצועיות ושירות אישי.

אני כאן כדי לעזור ולענות על כל שאלה נוספת,
אשמח לעמוד לשירותך תמיד בכל עת.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_APPROVED,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:worker_name*,

Refund Claim Status

Your Refund request has been :refund_status.

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "שלום, *:worker_name*

סטטוס תביעת החזר

בקשת ההחזר שלך הייתה :refund_status.

בברכה,
צוות ברום סרוויס🌹",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_REJECTED,
                'description' => 'Client meeting schedule reminder message template',
                'message_en' => "Hello, *:worker_name*,

Refund Claim Status

Your Refund request has been :refund_status.

Reason for reject: :refund_rejection_comment.

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "שלום, *:worker_name*

סטטוס תביעת החזר

בקשת ההחזר שלך הייתה :refund_status.

סיבה לדחייה: :refund_rejection_comment.

בברכה,
צוות ברום סרוויס🌹",
                'message_spa' => '',
                'message_ru' => "",
            ],

//             [
//                 'key' => WhatsappMessageTemplateEnum::SICK_LEAVE_NOTIFICATION,
//                 'description' => 'Client meeting schedule reminder message template',
//                 'message_en' => "Hello, *:worker_name*,



// Best Regards,
// Broom Service Team 🌹",

//                 'message_heb' => "שלום, *:worker_name*


// בברכה,
// צוות ברום סרוויס🌹
// www.broomservice.co.il
// טלפון: 03-525-70-60
// office@broomservice.co.il",
//                 'message_spa' => '',
//                 'message_ru' => "",
//             ],



//             [
//                 'key' => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST_TEAM,
//                 'description' => 'Client meeting schedule reminder message template',
//                 'message_en' => "Hello, *Team*


// Best regards,
// Broom Service Team",

//                 'message_heb' => "שלום, *צוות*

// הלקוח :client_name עדיין לא שלח תמונות או סרטון של האזור הנדרש לניקוי.

// אנא עקבו אחר הלקוח לבדיקת סטטוס והשלמת הפרטים לצורך מתן הצעת המחיר.

// פרטי הלקוח:
//   • שם:  :client_name
//   • טלפון:  :client_contact

// בברכה,
// ברום סרוויס צוות",
//                 'message_spa' => '',
//                 'message_ru' => "",
//             ],



            [
                'key' => WhatsappMessageTemplateEnum::STOP,
                'description' => 'Team notification if client stop notification',
                'message_en' => "",

                'message_heb' => "שלום, *צוות*

לקוח בשם :client_name ביקש להפסיק לקבל מאיתנו הודעות.
יש לעדכן את המערכת ולהסיר את הלקוח מרשימת התפוצה לאלתר כדי למנוע שליחת הודעות נוספות.

פרטי לקוח:

מספר טלפון: :client_phone_number
דוא'ל: :client_email
קישור להודעה ב-CRM: :client_detail_url
אנא ודא שהבקשה תעובד בהקדם האפשרי ותעודכן לאחר השלמתה.

בברכה,
ברום סרוויס צוות",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_TOMMOROW_MEETINGS,
                'description' => 'Reminder to Team for Tommorow meetings',
                'message_en' => '',
                'message_heb' => "*שלום צוות*,

מחר יש לנו מספר פגישות חשובות עם לקוחות. להלן כל הפרטים:

:all_team_meetings
---

*הערות נוספות*:
- במידה ויש שינויים בלוח הזמנים, יש לעדכן את כולם בהקדם.
- אפשר לכלול קישורים להוספת הפגישות ליומן או קישורים ישירים לפגישות בזום.

בהצלחה לכולם מחר! 📞👥

בברכה,
צוות ברום סרוויס",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_CLIENT_FOR_TOMMOROW_MEETINGS,
                'description' => 'Reminder to Client for Tommorow meeting',
                'message_en' => "Hello, *:client_name*,

This is a friendly reminder about your scheduled meeting with us tomorrow. Here are the details:

- *Date & Time*: :meeting_date_time
- *Location*: :meet_link

Please let us know if you need to make any changes by clicking on one of the options below:

*Accept/Decline*: :meeting_reschedule_link
*Upload Files*: :meeting_file_upload_link

We appreciate your response to ensure everything is set up for your convenience.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

זוהי תזכורת לפגישה שנקבעה איתך למחר. להלן הפרטים:

- *תאריך ושעה*: :meeting_date_time
- *מיקום*: :meet_link

אנא עדכן אותנו אם יש צורך לבצע שינויים על ידי לחיצה על אחת מהאפשרויות הבאות:

קבל/דחה: :meeting_reschedule_link
העלה קובץ: :meeting_file_upload_link

נשמח לקבל את תשובתך כדי שנוכל להיערך בהתאם.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TEAM_ONE_WEEK_BEFORE_WORKER_VISA_RENEWAL,
                'description' => 'Reminder to Team one week before worker visa renewal',
                'message_en' => '',
                'message_heb' => "*שלום צוות*,

זוהי תזכורת שהעובד :worker_name צריך לחדש את הוויזה שלו בתאריך :visa_renewal_date. נא לדאוג לבצע את כל ההתאמות הנדרשות בלוח הזמנים, מכיוון שסביר להניח שהעובד לא יוכל לעבוד ביום זה.

נא לוודא שכל השינויים מתבצעים בהתאם ולהיות במעקב עד לקבלת הוויזה החדשה.

:worker_detail_url

תודה על שיתוף הפעולה!

בברכה,
צוות ברום סרוויס",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_WORKER_ONE_WEEK_BEFORE_HIS_VISA_RENEWAL,
                'description' => '5 PM Daily Reminder to Worker to Confirm Address',
                'message_en' => 'Hello, *:worker_name*,

This is a reminder that your visa is up for renewal on :visa_renewal_date. Please make sure you are prepared to renew your visa on time.

Best of luck!

Best Regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

זוהי תזכורת שהוויזה שלך מתחדשת בתאריך :visa_renewal_date. נא לוודא שאתה מוכן לחידוש הוויזה בזמן.

בהצלחה!

בברכה,
צוות ברום סרוויס  ',
                'message_spa' => 'Hola, *:worker_name*,

Este es un recordatorio de que tu visa necesita ser renovada el :visa_renewal_date. Asegúrate de estar preparado para la renovación a tiempo.

¡Buena suerte!

Saludos cordiales,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

Это напоминание о том, что ваша виза требует продления :visa_renewal_date. Пожалуйста, убедитесь, что вы готовы продлить визу вовремя.

Удачи!

С уважением,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_NOT_IN_SYSTEM_OR_NO_OFFER,
                'description' => 'Client not in system or if in system but no offer',
                'message_en' => '',
                'message_heb' => 'בוקר טוב, מה שלומך?

ראיתי שפנית אלינו בעבר ולא התקדמת לפגישה או קבלת הצעת מחיר, ורציתי להזכיר שאנחנו כאן עבורך – תמיד ובכל עת שתצטרך.

מאות לקוחות שבחרו בנו כבר גילו איך שירותי הניקיון שלנו שדרגו את הבית שלהם ואת איכות החיים, תוך שהם משאירים את כל הדאגות מאחור.

מצרפת כאן לעיונך המלצות מלקוחות קיימים שלנו כדי שתוכלו להתרשם בעצמכם מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

אנחנו מזמינים אותך להצטרף אליהם וליהנות משירות מקצועי, אישי ואיכותי שמבטיח לך שקט נפשי ותוצאה מושלמת בכל פעם.

נשמח לעמוד לשירותך ולענות על כל שאלה או צורך – כל שעליך לעשות הוא לשלוח לנו הודעה, ואנחנו נדאג לכל היתר.

בברכה,
מורן
צוות ברום סרוויס🌹
https://www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::CLIENT_HAS_OFFER_BUT_NO_SIGNED_OR_NO_CONTRACT,
                'description' => 'Client has offer but not signed or not have contract',
                'message_en' => '',
                'message_heb' => 'בוקר טוב, מה שלומך?

שמתי לב שעדיין לא התקדמתם עם הצעת המחיר שנשלחה אליכם מאיתנו.
לגמרי מובן שלפעמים צריך עוד זמן לחשוב או תמריץ קטן כדי לקבל החלטה שתשנה את החיים שלכם. ואני מבטיחה לך – זו לא קלישאה, אלא המציאות של מאות לקוחות מרוצים שמקבלים מאיתנו שירות קבוע כבר שנים רבות.

לקוחותינו כבר קיבלו את ההחלטה ששדרגה את איכות החיים שלהם, שחררה אותם מההתעסקות בניקיון הבית, ופינתה להם זמן אמיתי למה שחשוב באמת.

לכן, אנו מזמינים אתכם לנצל הזדמנות חד-פעמית ולקבל את שירות הניקיון שחיכיתם לו ברמה הגבוהה ביותר:
🔹 ביקור ראשון ללא מע"מ – כך שתוכלו להתרשם בעצמכם מהמקצועיות, האיכות והתוצאה שתשדרג לכם את הבית ואת איכות החיים.
🔹 ללא התעסקות, ללא התחייבות וללא דאגות – רק בית נקי ומזמין!

זו ההזדמנות שלכם להבין בדיוק מה אתם מקבלים בתמורה לכסף שלכם – ולמה מאות לקוחות מרוצים כבר בחרו בנו ועובדים איתנו שנים רבות.

מצרפת כאן לעיונכם המלצות מלקוחות קיימים שלנו כדי שתוכלו להתרשם בעצמכם מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

שימו לב – ההצעה תקפה לזמן מוגבל בלבד!

לפרטים נוספים או להזמנת ביקור ראשון, אתם מוזמנים להשיב להודעה זו או ליצור קשר ישירות איתי.
אשמח לעמוד לשירותכם בכל שאלה.

בברכה,
מורן
צוות ברום סרוויס🌹
https://www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT,
                'description' => 'Send notification to client after stop message',
                'message_en' => 'Hello, *:client_name*

Your request has been processed. You have been unsubscribed, and you will no longer receive notifications from us.
If this was a mistake or you wish to resubscribe, please let us know.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il',
                'message_heb' => "שלום, *:client_name*

הבקשה שלך התקבלה. הסרנו אותך מהרשימה, ולא תקבל יותר הודעות מאיתנו. אם זה נעשה בטעות או אם תרצה להירשם שוב, אנא צור קשר

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-
office@broomservice.co.il",
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS,
                'description' => 'Send notification to client after 3 days without answer',
                'message_en' => "Hello, *:client_name*

We just wanted to remind you that we haven’t been able to reach you regarding your inquiry.
We’d be happy to assist you and provide all the relevant information you need.

Additionally, you are welcome to explore our satisfied customers' experiences to see the excellent service we provide:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

We are available Sunday to Thursday between 8:00 AM and 4:00 PM. You can reach us at: 03-525-70-60.
Please let us know when it would be convenient for us to contact you.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

רק רצינו להזכיר לך שעדיין לא הצלחנו ליצור איתך קשר בהמשך לפנייתך.
נשמח לעמוד לשירותך ולספק את כל המידע הרלוונטי.

בנוסף, תוכלו לקרוא על חוויות של לקוחות מרוצים למען התרשמותך מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

אנחנו זמינים בימים א'-ה' בין השעות 8:00 ל-16:00, וניתן ליצור איתנו קשר בטלפון: 03-525-70-60.
נשמח לדעת מתי יהיה לך נוח שניצור איתך קשר.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_7_DAYS,
                'description' => 'Send notification to client after 7 days without answer',
                'message_en' => "Hello, *:client_name*

Following your inquiry, we haven’t been able to reach you yet.
We’d like to ensure you’ve received all the necessary information regarding your request.

We’re here to assist you if you are still interested in learning more about our services or have any other questions.

We also invite you to read our satisfied customers' reviews to learn more about the excellent service we offer:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

We are available Sunday to Thursday between 8:00 AM and 4:00 PM. You can reach us at: 03-525-70-60.
If you no longer require us to follow up, please let us know so we can close your inquiry.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

בהמשך לפנייתך, לא הצלחנו להשיגך עד כה.
נשמח לוודא שקיבלת מענה לפנייתך.

אנחנו כאן בשבילך במידה ואתה עדיין מעוניין בפרטים על השירות או לכל עניין אחר.

אנו מזמינים אותך לקרוא על חוויות של לקוחות מרוצים למען התרשמותך מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

אנחנו זמינים בימים א'-ה' בין השעות 8:00 ל-16:00, וניתן ליצור איתנו קשר בטלפון: 03-525-70-60.
במידה ואין צורך שנחזור אליך, נשמח לדעת על כך כדי לסגור את הטיפול בפנייתך.
נשמח לעמוד לשירותך.

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_8_DAYS,
                'description' => 'Send notification to client after 8 days without answer',
                'message_en' => "Hello, *:client_name*

Following your inquiry, we haven’t been able to reach you yet.Following your inquiry, we haven’t been able to reach you so far.
We assume you currently don’t require any further information about our services. Therefore, we will close your inquiry in our system.

If you need our assistance or additional information in the future, we’ll be happy to help at any time!
You can reach us Sunday to Thursday between 8:00 AM and 4:00 PM at: 03-525-70-60.

Thank you for contacting us.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

בהמשך לפנייתך אלינו, לא הצלחנו ליצור איתך קשר עד כה. 
אנו מניחים שאין לך צורך נוסף במידע על השירותים שלנו בשלב זה, ולכן נסגור את פנייתך במערכת.

במידה ותזדקק לשירותנו או למידע נוסף בעתיד, נשמח לעמוד לשירותך בכל עת!
תוכל ליצור איתנו קשר בימים א'-ה' בין השעות 8:00 ל-16:00 בטלפון: 03-525-70-60.

תודה שפנית אלינו,

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_CLIENT,
                'description' => 'Send notification to client for reschedule call',
                'message_en' => "Hello, *:client_name*

Following up on our conversation, this is a reminder that we have scheduled to speak again on :reschedule_call_date at :reschedule_call_time.
In the meantime, we invite you to read about the experiences of our satisfied clients to learn more about our excellent service:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

For rescheduling the call or any other inquiries, we are here to assist you.

Best Regards,
Broom Service Team 🌹
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il

If you no longer wish to receive messages from us, please reply with 'STOP' at any time.",

                'message_heb' => "שלום, *:client_name*

בהמשך לשיחתנו, זוהי תזכורת כי קבענו לשוחח שוב ביום :reschedule_call_date בשעה :reschedule_call_time 
בינתיים, אנו מזמינים אותך לקרוא על חוויות של לקוחות מרוצים למען התרשמותך מהשירות המעולה שלנו:
https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl

לשינוי מועד השיחה שנקבעה או לכל עניין אחר, אנו כאן לשירותך.

תודה שפנית אלינו,

בברכה,
צוות ברום סרוויס🌹
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il

אם אינך מעוניין לקבל מאיתנו הודעות נוספות, אנא שלח 'הפסק' בכל עת.",
                'message_spa' => '',
                'message_ru' => "",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_TEAM,
                'description' => 'Send notification to team for reschedule call',
                'message_en' => "",

                'message_heb' => "שלום צוות,

זוהי תזכורת כי היום בשעה :reschedule_call_time מתוכננת שיחה עם :client_name :client_phone_number.
אנא ודאו שאתם מוכנים וזמינים לסייע במידת הצורך.
במידה ויש עדכונים או שינויים, אנא עדכנו את הגורמים הרלוונטיים בהקדם.

בברכה,
צוות ברום סרוויס🌹",
                'message_spa' => '',
                'message_ru' => "",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::NEW_LEAD_FOR_HIRING_TO_TEAM,
                'description' => 'Send notification to team for new lead for hiring',
                'message_en' => "🌟 New Lead for Hiring! 🌟
Contact: :worker_lead_phone  
Status: ✅ Suitable for house cleaning job

Alex, please contact the lead and update the status with:  
1. 'Hire' – If hired  
2. 'No' – If not suitable  
3. 'Unanswered' – If the lead didn’t respond  

⚠️ Please reply within 24 hours with the lead’s number and status.  
Example: +972 52-123-4567 – Hire

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "🌟 ליד חדש להעסקה! 🌟
איש קשר: :worker_lead_phone
סטטוס: ✅ מתאים לעבודת ניקיון הבית

אלכס, אנא צור קשר עם המוביל ועדכן את הסטטוס באמצעות:
1. 'השכרה' - אם התקבל לעבודה
2. 'לא' - אם לא מתאים
3. 'לא נענה' - אם המוביל לא הגיב

⚠️ אנא השב תוך 24 שעות עם מספר הליד והסטטוס.
דוגמה: +972 52-123-4567 - השכרה

בברכה,
צוות שירות מטאטא 🌹",
                'message_spa' => '',
                'message_ru' => "🌟 Новый кандидат для найма! 🌟  
Контакт: worker_lead_phone  
Статус: ✅ Подходит для работы по уборке  

Алекс, пожалуйста, свяжитесь с кандидатом и обновите статус:  
1. 'Hire' – Принят на работу  
2. 'No' – Не подходит  
3. 'Unanswered' – Не ответил  

⚠️ Обновите статус в течение 24 часов с номером кандидата.  
Пример: +972 52-123-4567 – Hire  

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NEW_LEAD_FOR_HIRING_24HOUR_TO_TEAM,
                'description' => 'Send notification to team for new lead for hiring',
                'message_en' => "⏰ No update received for: :worker_lead_phone  

Alex, please provide the status for this lead:  
1. 'Hire'  
2. 'No'  
3. 'Unanswered'  

Thank you! 🌟  

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "⏰ לא התקבל עדכון עבור: :worker_lead_phone

אלכס, אנא ספק את הסטטוס לליד זה:
1. 'השכרה'
2. 'לא'
3. 'ללא מענה'

תודה לך! 🌟

בברכה,
צוות שירות מטאטא 🌹",
                'message_spa' => "⏰ No se recibió ninguna actualización para: :worker_lead_phone

Alex, proporciona el estado de este cliente potencial:
1. 'Contratar'
2. 'No'
3. 'Sin respuesta'

¡Gracias! 🌟

Saludos cordiales,
Equipo de servicio de escobas 🌹",
                'message_ru' => "⏰ Нет обновления для номера: :worker_lead_phone 

Алекс, пожалуйста, обновите статус:  
1. 'Hire'  
2. 'No'  
3. 'Unanswered'  

Спасибо! 🌟

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM,
                'description' => 'new lead hiried to team',
                'message_en' => "🚀 Action Required: New Hire 🚀  
Please proceed to hire the following candidate:  

Contact: :worker_lead_phone  
Status: ✅ Hire confirmed by Alex  

⚠️ Reminder will be sent daily until hiring is completed.  

Thank you for your cooperation! 🌟   

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "🚀 נדרשת פעולה: גיוס חדש 🚀
אנא המשיכו להעסיק את המועמד הבא:

איש קשר: :worker_lead_phone
סטטוס: ✅ השכרה באישור אלכס

⚠️ תזכורת תישלח מדי יום עד להשלמת הגיוס.

תודה על שיתוף הפעולה! 🌟

בברכה,
צוות שירות מטאטא 🌹",

                'message_spa' => "🚀 Acción requerida: Nuevo empleado 🚀
Por favor, proceda a contratar al siguiente candidato:

Contacto: :worker_lead_phone
Estado: ✅ Contratación confirmada por Alex

⚠️ Se enviará un recordatorio todos los días hasta que se complete la contratación.

¡Gracias por su cooperación! 🌟

Saludos cordiales,
Equipo de Broom Service 🌹",

                'message_ru' => "🚀 Требуется действие: Новый сотрудник 🚀  
Пожалуйста, завершите процесс найма кандидата:  

Контакт: :worker_lead_phone 
Статус: ✅ Принят на работу  

⚠️ Напоминание будет отправляться ежедневно, пока процесс не будет завершен.  

Спасибо! 🌟

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],


            [
                'key' => WhatsappMessageTemplateEnum::NEW_LEAD_IN_HIRING_DAILY_REMINDER_TO_TEAM,
                'description' => 'new lead not hiried daily reminder to team',
                'message_en' => "⚠️ Reminder: Please confirm completion of hiring for: :worker_lead_phone.  
Thank you! 😊     

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "⚠️ תזכורת: אנא אשר את השלמת הגיוס עבור: :worker_lead_phone.
תודה לך! 😊

בברכה,
צוות שירות מטאטא 🌹",
                'message_spa' => "⚠️ Recordatorio: Confirme la finalización de la contratación para: :worker_lead_phone.
¡Gracias! 😊

Saludos cordiales,
Broom Service Team 🌹",

                'message_ru' => "⚠️ Напоминание: Пожалуйста, подтвердите завершение найма: +972 52-123-4567.  
Спасибо! 😊  

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED,
                'description' => 'Alex reply lead UNANSWERED to lead',
                'message_en' => "🌟 Hi again!  

Alex, our manager, tried contacting you but couldn’t reach you.  
Please call him back at: +972 52-848-0808.  

We look forward to hearing from you! 😊  

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "🌟 היי שוב!

אלכס, המנהל שלנו, ניסה ליצור איתך קשר אך לא הצליח להשיג אותך.
נא להתקשר אליו בחזרה למספר: +972 52-848-0808.

נשמח לשמוע ממך! 😊

בברכה,
צוות שירות מטאטא 🌹",
                'message_spa' => "🌟 ¡Hola de nuevo!

Alex, nuestro gerente, intentó comunicarse contigo pero no pudo comunicarse contigo.
Por favor, vuelve a llamarlo al: +972 52-848-0808.

¡Esperamos tener noticias tuyas! 😊

Saludos cordiales,
Equipo de Broom Service 🌹",
                'message_ru' => "🌟 Привет снова!  

Алекс, наш менеджер, пытался с вами связаться.  
Пожалуйста, перезвоните ему по номеру: +972 52-848-0808.  

Ждем вашего ответа! 😊  

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::DAILY_REMINDER_TO_LEAD,
                'description' => 'daily reminder to lead',
                'message_en' => "📞 Reminder: Alex is waiting to hear from you.  

Please call him back at: +972 52-848-0808.  

Let’s finalize your job application! 🌟   

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "📞 תזכורת: אלכס מחכה לשמוע ממך.

נא להתקשר אליו בחזרה למספר: +972 52-848-0808.

בואו לסיים את מועמדותכם לעבודה! 🌟

בברכה,
צוות שירות מטאטא 🌹",
                'message_spa' => '',
                'message_ru' => "📞 Напоминание: Пожалуйста, свяжитесь с Алексом по номеру: +972 52-848-0808. 
                 
Давайте завершим вашу заявку! 🌟  

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::FINAL_MESSAGE_IF_NO_TO_LEAD,
                'description' => 'final message to lead',
                'message_en' => "🌟 Thank you for your time!  

Unfortunately, we are unable to move forward with your application at this time.  
If you are interested in future opportunities, feel free to reach out again.  

We wish you all the best! 🌟 

Best Regards,
Broom Service Team 🌹",

                'message_heb' => "🌟 תודה על הזמן שהקדשת!

למרבה הצער, איננו יכולים להתקדם עם הבקשה שלך בשלב זה.
אם אתה מעוניין בהזדמנויות עתידיות, אל תהסס לפנות שוב.

אנו מאחלים לך כל טוב! 🌟

בברכה,
צוות שירות מטאטא 🌹",
                'message_spa' => '',
                'message_ru' => "🌟 Спасибо за ваше время!  

К сожалению, мы не можем продолжить вашу заявку на данный момент.  
Если вас заинтересуют будущие возможности, не стесняйтесь обращаться снова.  

Желаем вам всего наилучшего! 🌟  

С наилучшими пожеланиями,
Команда Broom Service 🌹",
            ],

            [
                'key' => WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_TEAM,
                'description' => 'send worker job cancel by team',
                'message_en' => "Hello :worker_name,

Job is marked as Cancel by admin/team.

Please check the details.

Date/Time: :job_start_date
Client: :client_name
Service: :job_service_name
Start time: :job_start_time
Property: :job_full_address

Check Job Details: :worker_job_link

If you have any questions or concerns please don't hesitate to get in touch with us by replying to this email.

Best Regards,
Broom Service Team",
                'message_heb' => "שלום :worker_name,

המשרה מסומנת כמבוטלת על ידי המנהל/צוות.

אנא בדוק את הפרטים.

תאריך/שעה: :job_start_date
לקוח: :client_name
שירות: :job_service_name
שעת התחלה: :job_start_time
נֶכֶס: :job_full_address

בדוק את פרטי המשרה: :worker_job_link

אם יש לך שאלות או חששות, אל תהסס לפנות אלינו על ידי מענה לדוא'ל זה.

בברכה,
צוות שירות רום",
                'message_spa' => 'Hola :worker_name,

El administrador o el equipo marcaron el trabajo como cancelado.

Verifique los detalles.

Fecha/hora: :job_start_date
Cliente: :client_name
Servicio: :job_service_name
Hora de inicio: :job_start_time
Propiedad: :job_full_address

Verifique los detalles del trabajo: :worker_job_link

Si tiene alguna pregunta o inquietud, no dude en comunicarse con nosotros respondiendo a este correo electrónico.

Atentamente,
Equipo de servicio de escobas',
                'message_ru' => 'Здравствуйте, :worker_name,

Задание отмечено как Отмененное администратором/командой.

Проверьте подробности.

Дата/время: :job_start_date
Клиент: :client_name
Услуга: :job_service_name
Время начала: :job_start_time
Объект: :job_full_address

Проверьте подробности задания: :worker_job_link

Если у вас есть какие-либо вопросы или опасения, не стесняйтесь обращаться к нам, ответив на это письмо.

С наилучшими пожеланиями,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_CLIENT,
                'description' => 'send worker job cancel by client',
                'message_en' => "Hello :worker_name,

Client changed the Job status to Cancel With Cancellation fees :cancellation_fee.

Please check the details.

Date/Time: :job_start_date
Client: :client_name
Service: :job_service_name
Start time: :job_start_time
Property: :job_full_address

Check Job Details: :worker_job_link

If you have any questions or concerns please don't hesitate to get in touch with us by replying to this email.

Best Regards,
Broom Service Team",
                'message_heb' => "שלום :worker_name,

הלקוח שינה את סטטוס המשרה לביטול עם דמי ביטול :cancellation_fee.

אנא בדוק את הפרטים.

תאריך/שעה: :job_start_date
לקוח: :client_name
שירות: :job_service_name
שעת התחלה: :job_start_time
נֶכֶס: :job_full_address

בדוק את פרטי המשרה: :worker_job_link

אם יש לך שאלות או חששות, אל תהסס ליצור איתנו קשר על ידי מענה לדוא'ל זה.

בברכה,
צוות שירות רום",
                'message_spa' => 'Hola :worker_name,

El cliente cambió el estado del trabajo a Cancelar con cargos por cancelación :cancellation_fee.

Verifique los detalles.

Fecha/Hora: :job_start_date
Cliente: :client_name
Servicio: :job_service_name
Hora de inicio: :job_start_time
Propiedad: :job_full_address

Verifique los detalles del trabajo: :worker_job_link

Si tiene alguna pregunta o inquietud, no dude en ponerse en contacto con nosotros respondiendo a este correo electrónico.

Atentamente,
Equipo de servicio de escobas',
                'message_ru' => 'Здравствуйте, :worker_name,

Клиент изменил статус задания на «Отмена с оплатой за отмену» :cancellation_fee.

Проверьте подробности.

Дата/время: :job_start_date
Клиент: :client_name
Услуга: :job_service_name
Время начала: :job_start_time
Недвижимость: :job_full_address

Проверьте подробности задания: :worker_job_link

Если у вас есть какие-либо вопросы или опасения, не стесняйтесь обращаться к нам, ответив на это письмо.

С наилучшими пожеланиями,
Команда Broom Service',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::SEND_WORKER_TO_STOP_TIMER,
                'description' => 'send worker to stop timer',
                'message_en' => "Hello :worker_name,

The job time has been stopped by you. Check the below link and click  *Mark as complete* if you want to complete your job else click on  *Resume timer* to continue job.

Please check the details.

Date/Time: :job_start_date
Client: :client_name
Service: :job_service_name
Start time: :job_start_time
Property: :job_full_address

Check Job Details: :worker_job_link

If you have any questions or concerns please don't hesitate to get in touch with us by replying to this email.

Best Regards,
Broom Service Team",
                'message_heb' => "שלום :worker_name,

זמן העבודה הופסק על ידך. סמן את הקישור למטה ולחץ על *סמן כהשלמה* אם ברצונך להשלים את העבודה שלך אחרת לחץ על *המשך טיימר* כדי להמשיך בעבודה.

אנא בדוק את הפרטים.

תאריך/שעה: :job_start_date
לקוח: :client_name
שירות: :job_service_name
זמן התחלה: :job_start_time
נכס: :job_full_address

בדוק את פרטי המשרה: :worker_job_link

אם יש לך שאלות או חששות, אל תהסס ליצור איתנו קשר על ידי מענה לדוא'ל זה.

בברכה,
צוות שירות רום",
                'message_spa' => 'Hola :worker_name,

Ha detenido el trabajo. Compruebe el siguiente enlace y haga clic en *Marcar como completado* si desea completar su trabajo; de lo contrario, haga clic en *Reanudar temporizador* para continuar con el trabajo.

Compruebe los detalles.

Fecha/Hora: :job_start_date
Cliente: :client_name
Servicio: :job_service_name
Hora de inicio: :job_start_time
Propiedad: :job_full_address

Verifique los detalles del trabajo: :worker_job_link

Si tiene alguna pregunta o inquietud, no dude en ponerse en contacto con nosotros respondiendo a este correo electrónico.

Atentamente,
Equipo de servicio de escobas',
                'message_ru' => 'Здравствуйте, :worker_name,

Вы остановили время выполнения задания. Проверьте ссылку ниже и нажмите *Отметить как выполненное*, если вы хотите завершить задание, в противном случае нажмите *Таймер возобновления*, чтобы продолжить задание.

Проверьте детали.

Дата/время: :job_start_date
Клиент: :client_name
Услуга: :job_service_name
Время начала: :job_start_time
Свойство: :job_full_address

Проверьте детали задания: :worker_job_link

Если у вас есть какие-либо вопросы или опасения, не стесняйтесь обращаться к нам, ответив на это письмо.

С наилучшими пожеланиями,
Команда Broom Service',
            ],

        ];

        foreach ($templates as $key => $template) {
            WhatsappTemplate::updateOrCreate([
                'key' => $template['key'],
            ], $template);
        }
    }
}
