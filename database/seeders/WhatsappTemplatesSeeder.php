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
                'message_en' => 'Hi, *:worker_name*,

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
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM,
                'description' => '6 PM Daily Reminder to Worker to Confirm Address',
                'message_en' => 'Hi, *:worker_name*,

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
            ],

            [
                'key' => WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START,
                'description' => 'Reminder to Worker 1 Hour Before Job Start',
                'message_en' => 'Hi, *:worker_name*,

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
                'message_en' => 'Hi, *:worker_name*,

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
                'message_en' => 'Hi, *:worker_name*,

Your job at *:job_full_address* has started. You have *:job_remaining_hours hours* to complete the service, and it should be finished by *:job_end_time*.

Please review and complete the following tasks:
- *Service:* :job_service_name
- *Special Instructions:* :job_comments

When you’re finished, please confirm:
- *Click Here to Confirm Comments are Done* :worker_job_link
- *Contact Manager* :job_contact_manager_link if you have any issues with the tasks.

Best regards,
Broom Service Team',
                'message_heb' => 'שלום, *:worker_name*,

התחלת את העבודה בכתובת *:job_full_address*. יש לך *:job_remaining_hours שעות* לסיום העבודה, והיא צריכה להסתיים עד *:job_end_time*.

אנא עיין ובצע את המשימות הבאות:
- *שירות:* :job_service_name
- *הוראות מיוחדות:* :job_comments

כשתסיים, נא אשר:
- *לחץ כאן לאישור שהמשימות בוצעו* :worker_job_link
- *צור קשר עם המנהל* :job_contact_manager_link אם יש בעיות בביצוע המשימות.

בברכה,
צוות ברום סרוויס',
                'message_spa' => 'Hola, *:worker_name*,

Su trabajo en *:job_full_address* ha comenzado. Usted tiene *:job_remaining_hours horas* para completar el servicio, y debe terminar antes de *:job_end_time*.

Por favor, revise y complete las siguientes tareas:
- *Servicio:* :job_service_name
- *Instrucciones especiales:* :job_comments

Cuando haya terminado, por favor confirme:
- *Haga clic aquí para confirmar que las tareas están completadas* :worker_job_link
- *Contactar al gerente* :job_contact_manager_link si tiene algún problema con las tareas.

Atentamente,
Equipo de Broom Service',
                'message_ru' => 'Здравствуйте, *:worker_name*,

Ваша работа по адресу *:job_full_address* началась. У вас есть *:job_remaining_hours часа* для завершения работы, и она должна быть завершена к *:job_end_time*.

Пожалуйста, ознакомьтесь и выполните следующие задачи:
- *Услуга:* :job_service_name
- *Особые инструкции:* :job_comments

Когда закончите, пожалуйста, подтвердите:
- *Нажмите здесь, чтобы подтвердить выполнение задач* :worker_job_link
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
                'message_en' => 'Hi, *:client_name*,

We’ve added updates to the tasks on your job for *:job_service_name* scheduled for *:job_start_date_time*. Please review the latest updates and our responses to each task.

- *View Comments and Updates* :client_view_job_link

Best regards,
Broom Service Team',
                'message_heb' => 'שלום, *:client_name*,

הוספנו עדכונים לביצוע המשימות בעבודה שלך לשירות *:job_service_name*, שנקבעה ל-*:job_start_date_time*. אנא עיין בעדכונים האחרונים ובתגובות שלנו לכל משימה.

- *צפה במשימות ובתשובות* :client_view_job_link

בברכה,
צוות ברום סרוויס',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ALL_COMMENTS_COMPLETED,
                'description' => 'Notification to Client - Update on Comment Resolution',
                'message_en' => 'Hi, *:worker_name*,

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
                'message_en' => 'Hi, :worker_name,
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
                'message_en' => 'Hi, *:client_name*

Just a friendly reminder that you have an upcoming appointment on *:meeting_date* between *:meeting_start_time* to *:meeting_end_time* at *:meeting_address* for *:meeting_purpose*. Click the *Accept/Reject* or *Upload file* button for Accept, Reject, Reschedule, and Upload Files.

Accept/Reject: :meeting_reschedule_link

Upload file: :meeting_file_upload_link',
                'message_heb' => 'שלום, *:client_name*

רק תזכורת ידידותית שיש לך פגישה קרובה ב-*:meeting_date* בין *:meeting_start_time* ל-*:meeting_end_time* בכתובת *:meeting_address* עבור *:meeting_purpose*. לחץ על הלחצן *קבל/דחה* או *העלה קובץ* כדי לקבל, לדחות, לתאם מחדש ולהעלות קבצים.

קבל/דחה: :meeting_reschedule_link

העלה קובץ: :meeting_file_upload_link',
                'message_spa' => '',
                'message_ru' => '',
            ],


            [
                'key' => WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST,
                'description' => 'Send message to client for upload files (off site meeting)',
                'message_en' => 'Hi, *:client_name*

To provide you with an accurate quote for the requested services, we kindly ask that you send us a few photos or a video of the area that needs to be cleaned. This will help us better understand your needs and prepare a detailed quote for you.

Please click on blow link and upload the requested files at your earliest convenience.

:meeting_file_upload_link

If you have any questions or need assistance, feel free to reach out to us.

Best Regards,
Broom Service Team
www.broomservice.co.il
Telephone: 03-525-70-60
office@broomservice.co.il',
                'message_heb' => 'שלום, :client_name

כדי לספק לך הצעת מחיר מדויקת לשירותים המבוקשים, נשמח אם תוכל לשלוח לנו כמה תמונות או סרטון של האזור שזקוק לניקיון. כך נוכל להבין טוב יותר את הצרכים שלך ולהכין הצעת מחיר מפורטת עבורך.

אנא לחץ על הקישור למטה והעלה את הקבצים המבוקשים בהקדם האפשרי.

:meeting_file_upload_link

אם יש לך שאלות או שאתה זקוק לעזרה, אנא אל תהסס לפנות אלינו.

בברכה,
צוות ברום סרוויס
www.broomservice.co.il
טלפון: 03-525-70-60
office@broomservice.co.il',
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
                'message_en' => '
Hi, *:client_name*

Just a friendly reminder that your meeting *:meeting_team_member_name* on *:meeting_date* between *:meeting_start_time* to *:meeting_end_time* has been cancelled.',
                'message_heb' => 'שלום, *:client_name*

זוהי תזכורת לכך שהפגישה שלך *:meeting_team_member_name* ב-*:meeting_date* בין *:meeting_start_time* ל-*:meeting_end_time* בוטלה כעת.',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::OFFER_PRICE,
                'description' => 'Client new price offer message template',
                'message_en' => 'Hi, *:client_name*

Please check the price offer for the *:offer_service_names*. After your approval, an engagement agreement will be sent to you which you will need to fill out and sign below then we will be ready to start the work.
Click the below button to see the price offer.

Price Offer: :client_price_offer_link',
                'message_heb' => 'שלום, *:client_name*

מצ"ב הצעת מחיר עבור *:offer_service_names*. לאחר אישורכם, יישלח אליכם הסכם התקשרות אותו תצטרכו למלא ולחתום למטה ואז נהיה מוכנים להתחיל בעבודה.
לחץ על הכפתור למטה כדי לראות את הצעת המחיר.

הצעת מחיר: :client_price_offer_link',
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
                'message_en' => 'Hello :client_name,

Just a reminder that you received a price offer from us on :offer_sent_date.
Please find attached the price offer again for :price_offer_services. Once you confirm, we will send you an engagement agreement to complete and sign.

Click the button below to view the price offer.
If you have any questions or need any assistance, we are here to help.

Click here to view your price offer :client_price_offer_link

Best regards,
Broom Service
📞 03-525-70-60
🌐 www.broomservice.co.il',
                'message_heb' => 'שלום :client_name,

רק תזכורת לכך שקיבלת מאיתנו הצעת מחיר בתאריך :offer_sent_date.
מצ"ב שוב הצעת המחיר לשירות :price_offer_services. לאחר אישורכם, יישלח אליכם הסכם התקשרות למילוי וחתימה.

לחץ על הכפתור למטה כדי לצפות בהצעת המחיר.
אם יש לך שאלות, או לכל עניין אחר, אנו פה לשירותכם.

לחץ כאן לצפייה בהצעת המחיר שלך :client_price_offer_link

בברכה,
ברום סרוויס
📞 03-525-70-60
🌐 www.broomservice.co.il',
                'message_spa' => '',
                'message_ru' => '',
            ],

            [
                'key' => WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED,
                'description' => 'Reminder to Client - Agreement Signature (After 24 Hours, 3 Days, and 7 Days)',
                'message_en' => 'Hello :client_name,

Just a reminder that an engagement agreement was sent to you on :contract_sent_date.
Please find the agreement attached again. Kindly complete all details and sign where required.

Click the button below to view the agreement.
If you have any questions or need assistance, we are here to help.

Click here to view your agreement :client_contract_link

Best regards,
Broom Service
📞 03-525-70-60
🌐 www.broomservice.co.il',
                'message_heb' => 'שלום :client_name,

רק תזכורת לכך שנשלח אליכם הסכם התקשרות בתאריך :contract_sent_date.
מצ"ב שוב הסכם ההתקשרות. נא מלאו את כל הפרטים וחתמו במקומות הנדרשים.

לחץ על הכפתור למטה לצפייה בהסכם.
אם יש לך שאלות, או לכל עניין אחר, אנו פה לשירותכם.

לחץ כאן לצפייה בהסכם שלך :client_contract_link

בברכה,
ברום סרוויס
📞 03-525-70-60
🌐 www.broomservice.co.il',
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
                'message_en' => 'Hi, :client_name,

Just a friendly reminder that we have not yet received the requested photos or video of the area needing cleaning, which are essential to prepare your quote.

Please send the files at your earliest convenience to help us provide an accurate quote and proceed with the service.

If you have any questions or requests, we’re here to assist you.

Click here to upload your photos/video :meeting_uploaded_file_url

Best regards,
Broom Service
📞 03-525-70-60
🌐 http://www.broomservice.co.il',
                'message_heb' => 'שלום, :client_name,

רק תזכורת לכך שעדיין לא קיבלנו ממך תמונות או סרטון לצורך הצעת המחיר.

נא שלחו את התמונות או הסרטון בהקדם כדי שנוכל לספק הצעת מחיר מדויקת ולהתקדם בתהליך.

אם יש לך שאלות או בקשות, אנו פה לשירותך.

לחץ כאן לשליחת התמונות/סרטון :meeting_uploaded_file_url

בברכה,
ברום סרוויס
📞 03-525-70-60
🌐 http://www.broomservice.co.il
',
                'message_spa' => '',
                'message_ru' => '',
            ]
        ];

        foreach ($templates as $key => $template) {
            WhatsappTemplate::updateOrCreate([
                'key' => $template['key'],
            ], $template);
        }
    }
}
