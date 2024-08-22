<?php
return [
    'meeting' => [
        'subject'     => 'График встречи №:id | Broom Service',
        'resubject'     => 'Перенос встречи №:id | Broom Service',
        'file_subject'     => 'Файлы №:id | Broom Service',
        'file'     => 'Файлы',
        'file_content'     => "Добавлены новые файлы в нижеуказанные детали встречи",
        'appointment' => 'Просто дружеское напоминание, что у вас запланирована встреча',
        'select_preferred_slot' => 'Пожалуйста, выберите предпочитаемое время встречи',
        'with'        => 'с',
        'on'          => 'на',
        'between'     => 'между',
        'to'          => 'до',
        'for'         => 'для',
        'service'     => 'услуга',
        'accept'      => 'Принять',
        'reject'      => 'Отклонить',
        'reschedule'  => 'Перенести',
        'price_offer' => 'Предложение цены',
        'quality_check' => 'Проверка качества',
        'upload_job_description' => 'Загрузить описание работы',
        'address_txt' => 'Адрес',
        'choose_slot' => 'Выберите время',
        'content_with_date_time'     => 'Просто дружеское напоминание, что у вас запланирована встреча с :team_name на :date с :start_time до :end_time по адресу :address для :purpose.',
        'content_without_date_time'     => 'Просто дружеское напоминание, что у вас запланирована встреча с :team_name по адресу :address для :purpose.',
    ],

    'cancel_meeting' => [
    'subject'     => 'Встреча отменена №:id | Broom Service',
    'content'     => 'Просто дружеское напоминание, что ваша встреча на :date с :start_time до :end_time была отменена.',
    ],
    'offer' => [
        'subject'     => 'Получено предложение №:id | Broom Service',
        'content'     => 'Пожалуйста, ознакомьтесь с предложением цены на услугу(и) :service_names. После вашего одобрения будет отправлено соглашение о сотрудничестве, которое вам нужно будет заполнить и подписать. Затем мы сможем приступить к работе.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы увидеть предложение цены.',
        'btn_txt'     => 'Предложение цены',
    ],
    'contract' => [
        'subject'     => 'Контракт для предложения №:id | Broom Service',
        'content'     => 'Прилагается соглашение на подпись в электронном виде. Пожалуйста, добавьте данные вашей кредитной карты в безопасную систему. Будет списан 1 шекель, который будет возвращен после верификации. Данные будут храниться в безопасности. Затем, пожалуйста, подпишите и примите контракт. Если у вас есть вопросы, свяжитесь с нами по телефону: 03-525-70-60 или ответьте на это письмо.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы просмотреть контракт.',
        'btn_txt'     => 'Просмотреть контракт',
    ],
    'form_101' => [
        'subject'     => 'Форма 101 №:id | Broom Service',
        'content'     => 'Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите форму 101, чтобы начать работу.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы заполнить форму 101.',
        'btn_txt'     => 'Форма 101',
    ],

    'job_status' => [
        'subject'     => 'Рабочий изменил статус работы',
        'hi'          => 'Привет',
        'content'     => 'Рабочий изменил статус работы на ',
        'reason'      => 'По причине',
        'btn_txt'     => 'Проверить контракт',
        'thanks_text'   => 'Спасибо',
        'job'         => 'Работа',
        'started_by'  => 'была начата',
        'cancelled'   => 'Отменено',
        'worker_changed'   => 'Рабочий изменен',
        'shift_changed'   => 'Смена изменена',
        'cancellation_fee' => 'Комиссия за отмену'
    ],
    'worker_contract' => [
        'subject'     => 'Форма контракта №:id | Broom Service',
        'content'     => 'Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите форму контракта, чтобы начать работать.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы просмотреть форму контракта.',
        'btn_txt'     => 'Форма контракта',
    ],
    'worker_safe_gear' => [
        'subject'     => 'Форма безопасности и оборудования №:id | Broom Service',
        'content'     => 'Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите форму безопасности и оборудования, чтобы начать работать.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы просмотреть форму безопасности и оборудования.',
        'btn_txt'     => 'Форма безопасности и оборудования',
    ],

   'client_job_status' => [
        'subject'     => 'Клиент отменил работу',
        'hi'          => 'Привет',
        'content'     => 'Клиент изменил статус работы на ',
        'cancellation_fee' => 'С комиссией за отмену ',
        'btn_txt'     => 'Проверить контракт',
        'thanks_text'   => 'Спасибо',
        'job_completed' => 'Услуга, которую вы заказали, была завершена.',
        'review' => 'Оставить отзыв',
        'job_completed_subject'     => 'Услуга завершена | Broom Service',
    ],
    'worker_new_job' => [
        'subject'     => 'Детали работы с',
        'greetings'   => 'Приветствия',
        'from'        => 'от',
        'company'     => 'Broom Service',
        'content'     => 'Назначена новая работа. Пожалуйста, проверьте детали.',
        'please_check' => 'Пожалуйста, проверьте детали.',
        'new_job_assigned' => 'Назначена новая работа.',
        'change_in_job' => 'Изменение в вашей работе.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы проверить контракт.',
        'btn_txt'     => 'Проверить контракт',
        'reply_txt'   => 'Если у вас есть какие-либо вопросы или опасения, не стесняйтесь связаться с нами, ответив на это письмо.',
        'regards'     => 'С уважением',
        'tel'         => 'Телефон',
        'date'        => 'Дата',
        'worker'      => 'Рабочий',
        'client'      => 'Клиент',
        'service'     => 'Услуга',
        'shift'       => 'Смена',
        'status'      => 'Статус',
        'action'      => 'Действие',
        'scheduled'   => 'Запланировано',
        'to'          => 'до',
        'view_job'    => 'Просмотр работы',
        'start_time'  => 'Время начала',
        'property_address_txt'  => 'Адрес объекта'
    ],

   'client_new_job' => [
        'subject'     => 'Детали работы с',
        'content'     => 'Назначена новая работа. Пожалуйста, проверьте детали.',
        'below_txt'   => 'Нажмите кнопку ниже, чтобы проверить контракт.',
        'btn_txt'     => 'Проверить контракт',
        'date'        => 'Дата',
        'client'      => 'Клиент',
        'worker'      => 'Рабочий',
        'service'     => 'Услуга',
        'shift'       => 'Смена',
        'status'      => 'Статус',
        'action'      => 'Действие',
        'scheduled'   => 'Запланировано',
        'to'          => 'до',
        'view_job'    => 'Просмотреть работу',
        'start_time'  => 'Время начала'
    ],
    'worker_unassigned' => [
        'subject'     => 'Работа отменена с',
        'company'     => 'Broom Service',
        'you_unassigned_from_job' => 'Вы были сняты с работы.'
    ],
    'worker_tomorrow_job' => [
        'subject'     => 'Завтрашняя работа | Broom Service',
        'hi'          => 'Привет',
        'greetings'   => 'Приветствия',
        'from'        => 'от',
        'company'     => 'Broom Service',
        'message'     => 'Это уведомление о вашей завтрашней работе.',
        'date'        => 'Дата',
        'worker'      => 'Рабочий',
        'client'      => 'Клиент',
        'service'     => 'Услуга',
        'start_time'  => 'Время начала',
        'property'    => 'Объект',
        'shift'       => 'Смена',
        'status'      => 'Статус',
        'action'      => 'Действие',
        'approve'     => 'Одобрить',
        'reply_txt'   => 'Если у вас есть какие-либо вопросы или опасения, пожалуйста, свяжитесь с нами, ответив на это письмо.',
        'regards'     => 'С уважением',
        'tel'         => 'Телефон',
    ],

    'worker_job' => [
        'shift_changed' => 'Смена работы изменена.',
        'shift_changed_subject' => 'Смена работы изменена | Broom Service',
    ],
    'common' => [
        'salutation' => 'Привет, :name',
        'greetings' => 'Приветствия от Broom Service',
        'dont_hesitate_to_get_in_touch'   => 'Если у вас есть какие-либо вопросы или опасения, пожалуйста, свяжитесь с нами, ответив на это письмо.',
        'regards'     => 'С уважением',
        'company'     => 'Broom Service',
        'tel'         => 'Телефон',
    ],
    'admin' => [
        'form101-signed' => [
            'subject' => 'Форма 101 подписана | Broom Service',
            'message' => ':worker_name завершил подписание формы 101. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.'
        ],
        'safety-and-gear-signed' => [
            'subject' => 'Форма безопасности и снаряжения подписана | Broom Service',
            'message' => ':worker_name завершил подписание формы безопасности и снаряжения. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.'
        ],
        'contract-signed' => [
            'subject' => 'Контрактная форма подписана | Broom Service',
            'message' => ':worker_name завершил подписание контрактной формы. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.'
        ],
        'insurance-signed' => [
            'subject' => 'Страховая форма подписана | Broom Service',
            'message' => ':worker_name завершил подписание страховой формы. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.'
        ],
        'client-payment-failed' => [
            'subject' => 'Платеж клиента не удался | Broom Service',
            'message' => 'Платеж клиента :client_name с картой [**** **** **** :card_number] не удался.'
        ],
    ],

    'client' => [
        'review-request' => [
            'subject' => 'Оценка услуги | Broom Service',
            'message' => 'Пожалуйста, оцените нас и отправьте свой отзыв.',
        ],
        'payment-failed' => [
            'subject' => 'Платеж не удался | Broom Service',
            'message' => 'Ваш платеж с картой [**** **** **** :card_number] не удался. Пожалуйста, добавьте новую карту.'
        ],
    ],
    'worker' => [
        'insurance-form' => [
            'subject' => 'Страховая форма | Broom Service',
            'message' => 'Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите страховую форму, чтобы начать работать.',
            'secondary_message' => 'Нажмите кнопку ниже, чтобы просмотреть страховую форму.',
            'btn_txt' => 'Страховая форма',
        ],
        'form101-signed' => [
            'subject' => 'Форма 101 подписана | Broom Service',
            'message' => 'Вы завершили подписание формы 101. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.',
        ],
        'safety-and-gear-signed' => [
            'subject' => 'Форма безопасности и снаряжения подписана | Broom Service',
            'message' => 'Вы завершили подписание формы безопасности и снаряжения. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.',
        ],
        'contract-signed' => [
            'subject' => 'Контрактная форма подписана | Broom Service',
            'message' => 'Вы завершили подписание контрактной формы. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.',
        ],
        'insurance-signed' => [
            'subject' => 'Страховая форма подписана | Broom Service',
            'message' => 'Вы завершили подписание страховой формы. Пожалуйста, найдите подписанный документ в формате PDF для вашего ознакомления.',
        ],
    ],

    'insurance-form' => [
        'form_name' => 'Страховая форма',
    ],
    'form101' => [
        'form_name' => 'Форма 101',
    ],
    'safety-and-gear-form' => [
        'form_name' => 'Форма безопасности и снаряжения',
    ],
    'contract-form' => [
        'form_name' => 'Контрактная форма',
    ],
    'client_credentials' => [
        'subject' => 'Учетные данные клиента :client_name | Broom Service',
        'content'   => 'Ниже указаны ваши учетные данные для входа.',
        'btn_txt'   => 'Войти',
        'email' => 'Электронная почта',
        'password'  => 'Пароль',
    ],
    'worker_credentials' => [
        'subject' => 'Учетные данные сотрудника :worker_name | Broom Service',
        'content'   => 'Ниже указаны ваши учетные данные для входа.',
        'btn_txt'   => 'Войти',
        'email' => 'Электронная почта',
        'password'  => 'Пароль',
    ],
    'job_common' => [
        'job_details' => 'Детали работы',
        'worker_job_complete_content'  =>  'Работа была завершена :name.',
        'hi'          => 'Здравствуйте',
        'greetings'   => 'Приветствия',
        'from'        => 'от',
        'company'     => 'Broom Service',
        'please_check' => 'Пожалуйста, проверьте детали.',
        'reply_txt'   => 'Если у вас есть вопросы или сомнения, не стесняйтесь связаться с нами, ответив на это письмо.',
        'regards'     => 'С наилучшими пожеланиями',
        'tel'         => 'Телефон',
        'date'        => 'Дата',
        'worker'      => 'Сотрудник',
        'client'      => 'Клиент',
        'service'     => 'Услуга',
        'shift'       => 'Смена',
        'status'      => 'Статус',
        'action'      => 'Действие',
        'scheduled'   => 'Запланировано',
        'to'          => 'до',
        'view_job'    => 'Просмотр работы',
        'start_time'  => 'Время начала',
        'property_address_txt'  => 'Адрес',
        'approve_subject' => 'Работа утверждена | Broom Service',
        'approve_title' => 'Сотрудник утвердил работу',
        'approve_content' => 'Вы утвердили работу.',
        'not_approve_subject' => 'Работа не утверждена | Broom Service',
        'not_approve_title' => 'Сотрудник не утвердил работу',
        'not_approve_content' => 'Вы не утвердили работу.',
        'job_status'  => 'Статус работы',
        'admin_switch_worker_subject' => 'Запрос на смену сотрудника | Broom Service',
        'admin_switch_worker_title' => 'Сотрудник сменен администратором',
        'admin_switch_worker_content'  =>  'Администратор сменил сотрудника с :w1 на :w2.',
        'new_job_title' => 'Новая работа',
        'job_unassigned_title' => 'Работа снята',
        'admin_change_worker_content'  =>  'Сотрудник :workerName снят с работы № :jobId.',
        'worker_job_reminder_subject'   => 'Напоминание о работе | Broom Service',
        'worker_job_reminder_content'   => 'Просто дружеское напоминание, что вы до сих пор не утвердили работу.',
        'worker_job_not_started'   => 'Просто дружеское напоминание, что вы еще не начали работу.',
        'worker_exceed_job_time'   => 'Просто дружеское напоминание, что вы превысили время работы.',
        'worker_job_start_time_content' => 'Вы начали время работы.',
        'extra_amount' => 'Дополнительная сумма',
        'check_job_details' => 'Просмотреть детали работы',
        'mark_as_complete'  =>  'Отметить как завершенное',
        'end_time'  => 'Время окончания',
        'resume_timer'  => 'Возобновить таймер',
    ],

    'forms' => [
        'worker_forms' => 'Формы сотрудника',
        'content'   => 'Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите приведенные ниже формы, чтобы начать работу.',
        'below_txt' => 'Нажмите на кнопку ниже, чтобы заполнить формы.',
        'btn_txt'   => 'Посмотреть формы'
    ],
    'job_nxt_step' => [
        'approved_nxt_step_email_subject' => 'Работа утверждена | Следующий шаг | Broom Service',
        'approved_nxt_step_email_title' => 'Работа утверждена',
        'approved_nxt_step_email_content' => 'Вы утвердили работу. Проверьте приведенную ниже ссылку и нажмите :label, когда вы начнете свою работу.',
        'leaving_for_work_link'  => 'Я выезжаю на работу',
        'opened_nxt_step_email_subject' => 'Работа открыта | Следующий шаг | Broom Service',
        'opened_nxt_step_email_title' => 'Работа открыта',
        'opened_nxt_step_email_content' => 'Вы собираетесь начать свою работу. Проверьте приведенную ниже ссылку и нажмите :l1, когда вы начнете свое рабочее время или, если хотите завершить работу, нажмите :l2.',
        'completed_nxt_step_email_subject' => 'Работа завершена | Следующий шаг | Broom Service',
        'completed_nxt_step_email_title' => 'Работа завершена',
        'completed_nxt_step_email_content' => 'Вы завершили работу # :jobId. Вы получите обратную связь после того, как работа будет рассмотрена клиентом.',
        'start_time_nxt_step_email_subject' => 'Время работы началось | Следующий шаг | Broom Service',
        'start_time_nxt_step_email_title' => 'Время работы началось',
        'start_time_nxt_step_email_content' => 'Время работы началось. Проверьте приведенную ниже ссылку и нажмите :label, когда хотите остановить время работы.',
        'end_time_nxt_step_email_subject' => 'Время работы завершено | Следующий шаг | Broom Service',
        'end_time_nxt_step_email_title' => 'Время работы завершено',
        'end_time_nxt_step_email_content' => 'Время работы было остановлено. Проверьте приведенную ниже ссылку и нажмите :l1, если хотите завершить работу, или нажмите :l2, чтобы продолжить работу.',
    ],

    'wa-message' => [
        'common' => [
            'salutation' => "Привет, *:name*"
        ],
        'client_meeting_reminder' => [
            'header' => "*Напоминание о встрече*",
        ],
        'client_meeting_schedule' => [
            'header' => "*Встреча назначена*",
            'content' => "Просто дружеское напоминание, что у вас запланирована встреча *:date* с *:start_time* до *:end_time* по адресу *:address* для *:purpose*. Нажмите кнопку *Принять/Отклонить* или *Загрузить файл* для принятия, отклонения, переноса встречи или загрузки файлов.",
        ],
        'offer_price' => [
            'header' => "*Предложение от Broom Service*",
            'content' => "Пожалуйста, ознакомьтесь с ценовым предложением на *:service_names*. После вашего одобрения будет отправлено соглашение о сотрудничестве, которое вам нужно будет заполнить и подписать. Затем мы будем готовы начать работу. Нажмите кнопку ниже, чтобы увидеть ценовое предложение.",
        ],
        'contract' => [
            'header' => "*Контракт*",
            'content' => "Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите контрактную форму, чтобы начать работу. Нажмите кнопку ниже, чтобы ознакомиться с контрактом.",
        ],
        'create_job' => [
            'header' => "*Создана новая работа | Broom Service*",
            'content' => "Новая работа была назначена.\n\nДата: :date\nУслуга: :service_name\n\nМы с нетерпением ждем возможности обслужить вас.",
        ],
        'client_job_updated' => [
            'header' => "*Работа завершена | Broom Service*",
            'content' => "Ваша работа завершена.\n\nДата: :date\nУслуга: :service_name\n\nПожалуйста, оцените нас и оставьте свой отзыв.",
        ],
        'delete_meeting' => [
            'header' => "*Встреча отменена*",
            'content' => "Просто дружеское напоминание, что ваша встреча с *:team_name* на *:date* с *:start_time* до *:end_time* была отменена.",
        ],
        'form101' => [
            'header' => "*Форма 101 с Broom Service*",
            'content' => "Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите форму 101, чтобы начать работу.\n\nНажмите кнопку ниже, чтобы заполнить форму 101.",
        ],
        'new_job' => [
            'header' => "*Детали работы с Broom Service*",
            'content' => ":content_txt Пожалуйста, проверьте детали.\n\nДата/Время: :date_time\nКлиент: :client_name\nУслуга: :service_name\nСобственность: :address\nСтатус: :status",
        ],
        'worker_contract' => [
            'header' => "*Контракт с Broom Service*",
            'content' => "Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите контрактную форму, чтобы начать работу. Нажмите кнопку ниже, чтобы ознакомиться с контрактом.",
        ],
        'worker_job_approval' => [
            'header' => "*Работа утверждена | Broom Service*",
            'content' => "Пожалуйста, проверьте детали.\n\nДата/Время: :date_time\nКлиент: :client_name\nРаботник: :worker_name\nУслуга: :service_name\nСобственность: :address",
        ],
        'worker_not_approved_job' => [
            'header' => "*Работа не утверждена | Broom Service*",
            'content' => "Работник еще не утвердил адрес.\n\nДата/Время: :date_time\nКлиент: :client_name\nРаботник: :worker_name\nУслуга: :service_name\nСобственность: :address",
        ],
        'worker_not_left_for_job' => [
            'header' => "*Работник не выехал на работу | Broom Service*",
            'content' => "Работник еще не выехал на работу.\n\nДата/Время: :date_time\nКлиент: :client_name\nРаботник: :worker_name\nУслуга: :service_name\nСобственность: :address",
        ],
        'worker_not_started_job' => [
            'header' => "*Работник не начал работу | Broom Service*",
            'content' => "Работник еще не начал работу.\n\nДата/Время: :date_time\nКлиент: :client_name\nРаботник: :worker_name\nУслуга: :service_name\nСобственность: :address",
        ],
        'worker_not_finished_job_on_time' => [
            'header' => "*Работник не завершил работу вовремя | Broom Service*",
            'content' => "Работник не завершил работу вовремя.\n\nДата/Время: :date_time\nКлиент: :client_name\nРаботник: :worker_name\nУслуга: :service_name\nСобственность: :address",
        ],
        'worker_exceed_job_time' => [
            'header' => "*Работник превысил время работы | Broom Service*",
            'content' => "Работник превысил время работы.\n\nДата/Время: :date_time\nКлиент: :client_name\nРаботник: :worker_name\nУслуга: :service_name\nСобственность: :address",
        ],
        'worker_remind_job' => [
            'header' => "*Информация о вашей работе завтра | Broom Service*",
            'content' => "Это напоминание о вашей работе завтра. Пожалуйста, проверьте детали.\n\nДата: :date\nКлиент: :client_name\nУслуга: :service_name\nСобственность: :address\nВремя начала: :start_time\nСтатус: :status",
        ],
        'worker_unassigned_job' => [
            'header' => "*Работа отменена с Broom Service*",
            'content' => "Ваша работа была отменена. Пожалуйста, проверьте детали.\n\nДата: :date\nКлиент: :client_name\nУслуга: :service_name\nВремя начала: :start_time",
        ],
        'client_job_status_notification' => [
            'header' => "*Работа отменена | Broom Service*",
            'content' => "Услуга была отменена. Пожалуйста, проверьте детали.\n\nДата/Время: :date\nКлиент: :client_name\nУслуга: :service_name\nКомментарий: :comment",
        ],
        'worker_safe_gear'  =>  [
            'header' => "*Безопасность и оборудование | Broom Service*",
            'content' => "Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите форму безопасности и оборудования, чтобы начать работу.\nНажмите кнопку ниже, чтобы ознакомиться с безопасностью и оборудованием.",
        ],
        'client_reschedule_meeting'  =>  [
            'header' => "*Перенос встречи | Broom Service*",
            'content' => "Просто дружеское напоминание о предстоящей встрече с :team_name. Пожалуйста, проверьте детали.\n\nДата/Время: :date\nСобственность: :address\nЦель: :purpose\nСсылка на встречу: :meet_link",
        ],
        'worker_forms'  =>  [
            'header' => "*Формы сотрудника | Broom Service*",
            'content' => "Вы успешно зарегистрировались на нашем портале. Пожалуйста, подпишите приведенные ниже формы, чтобы начать работу.\nНажмите кнопку ниже, чтобы заполнить формы.",
        ],
        'worker_job_opening_notification' => [
            'header' => "*Работник изменил статус работы | Broom Service*",
            'content' => ':client_name теперь направляется на работу.'
        ],
        'worker_job_status_notification' => [
            'header' => "*Работник изменил статус работы | Broom Service*",
            'content' => "Работник изменил статус работы на :status. Пожалуйста, проверьте детали ниже.\n\nДата/Время: :date\nРаботник: :worker_name\nКлиент: :client_name\nУслуга: :service_name\nСтатус: :status",
        ],
        'admin_job_status_notification' => [
            'header' => "*Работа отменена | Broom Service*",
            'content' => "Услуга была отменена. Пожалуйста, проверьте детали.\n\nДата/Время: :date\nРаботник: :worker_name\nКлиент: :client_name\nУслуга: :service_name\nСтатус: :status\nКомментарий: :comment",
        ],
        'admin_reschedule_meeting' => [
            'header' => "*Перенос встречи | Broom Service*",
            'content' => "Просто дружеское напоминание о предстоящей встрече с :client_name. Пожалуйста, проверьте детали.\n\nДата/Время: :date\nСобственность: :address\nЦель: :purpose\nСсылка на встречу: :meet_link"
        ],
        'admin_lead_files' => [
            'header' => "*Файлы | Broom Service*",
            'content' =>  ":client_name добавил новые файлы к указанной встрече. Пожалуйста, проверьте детали.\n\nДата/Время: :date"
        ],
        'worker_changed_availability_affect_job' => [
            'header' => "*Работник изменил доступность | Broom Service*",
            'content' => ":name изменил доступность, что влияет на работу :date.",
        ],
        'worker_form101_signed' => [
            'header' => "*Форма 101 подписана | Broom Service*",
            'content' => ":name подписал форму 101.",
        ],
        'worker_contract_signed' => [
            'header' => "*Контракт подписан | Broom Service*",
            'content' => ":name подписал контракт.",
        ],
        'worker_insurance_signed' => [
            'header' => "*Страховка подписана | Broom Service*",
            'content' => ":name подписал форму страховки.",
        ],
        'worker_safety_gear_signed' => [
            'header' => "*Форма безопасности и оборудования подписана | Broom Service*",
            'content' => ":name подписал форму безопасности и оборудования.",
        ],
        'client_payment_failed' => [
            'header' => "*Ошибка оплаты клиента | Broom Service*",
            'content' => "Оплата клиента :name картой [**** **** **** :card_number] не удалась.",
        ],
        'client_reviewed' => [
            'header' => "*Отзыв клиента | Broom Service*",
            'content' => ":client_name добавил комментарий к работе :date_time.",
        ],
        'client_commented' => [
            'header' => "*Комментарий клиента | Broom Service*",
            'content' => "Клиент :client_name добавил комментарий к работе :date_time.",
        ],
        'admin_commented' => [
            'header' => "*Комментарий администратора | Broom Service*",
            'content' => ":admin_name добавил комментарий к работе :date_time.",
        ],
        'worker_commented' => [
            'header' => "*Комментарий работника | Broom Service*",
            'content' => "Работник :worker_name добавил комментарий к работе :date_time.",
        ],
        'new_lead_arrived' => [
            'header' => "*Новый лид поступил | Broom Service*",
            'content' => "Поступил новый лид (:client_name).",
        ],
        'client_lead_status_changed' => [
            'header' => "*Статус лида изменен | Broom Service*",
            'content' => "Статус :client_name изменен на :new_status.",
        ],
        'worker_leaves_job' => [
            'header' => "*Дата ухода работника с работы | Broom Service*",
            'content' => "Дата ухода работника :name установлена на :date.",
        ],
        'client_changed_job_schedule' => [
            'header' => "*Клиент изменил график работы | Broom Service*",
            'content' => "Клиент :client_name изменил график работы на :date_time.",
        ],
        'order_cancelled' => [
            'header' => "*Заказ отменен | Broom Service*",
            'content' => "Заказ клиента :client_name (:order_id) был отменен.",
        ],
        'payment_paid' => [
            'header' => "*Оплата произведена | Broom Service*",
            'content' => "Клиент :client_name произвел оплату.",
        ],
        'client_invoice_created_and_sent_to_pay' => [
            'header' => "*Счет создан и отправлен | Broom Service*",
            'content' => "Счет (:invoice_id) был создан и отправлен :client_name.",
        ],
        'client_invoice_paid_created_receipt' => [
            'header' => "*Чек создан | Broom Service*",
            'content' => "Чек (:invoice_id) был создан для :client_name.",
        ],
        'order_created_with_extra' => [
            'header' => "*Заказ создан с дополнительной платой | Broom Service*",
            'content' => "Заказ (:order_id) был создан для :client_name с дополнительной платой ₪:extra и общей суммой ₪:total.",
        ],
        'order_created_with_discount' => [
            'header' => "*Заказ создан со скидкой | Broom Service*",
            'content' => "Заказ (:order_id) был создан для :client_name со скидкой ₪:discount и общей суммой ₪:total после скидки.",
        ],
        'lead_need_human_representative' => [
            'header' => "*Требуется человек-менеджер | Broom Service*",
            'content' => ":client_name хочет поговорить с человеком-менеджером."
        ],
        'no_slot_avail_callback' => [
            'header' => "*Нет доступных слотов для встречи | Broom Service*",
            'content' => "Нет доступных слотов для встречи. Пожалуйста, организуйте обратный звонок для :client_name."
        ],
        'button-label' => [
            'accept_reject' => 'Принять/Отклонить',
            'upload_file' => 'Загрузить файл',
            'price_offer' => 'Ценовое предложение',
            'check_contract' => 'Проверить контракт',
            'review' => 'Оставить отзыв',
            'form101' => 'Форма 101',
            'view_job' => 'Просмотреть работу',
            'change_worker' => 'Изменить работника',
            'change_shift' => 'Изменить смену',
            'approve' => 'Одобрить',
            'safety_and_gear' => 'Проверить безопасность и оборудование',
            'check_form' => 'Проверить формы',
            'view_worker' => 'Просмотреть работника',
            'check_file' => 'Проверить файл',
            'view_client' => 'Просмотреть клиента'
        ]
    ],

    
    
   'otp' => [
        'subject' => 'Ваш OTP для входа',
        'body' => 'Ваш OTP (Одноразовый пароль) для входа: :otp',
        'expiration' => 'Пожалуйста, используйте этот OTP для продолжения входа. OTP истечет через 10 минут.',
    ],

];
