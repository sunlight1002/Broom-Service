<!DOCTYPE html>
@php
    \App::setLocale('heb');
@endphp
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>{{ __('mail.stop_insuarance_form_non_israel.subject', ['worker_name' => ($worker['firstname'] ?? ''). ' ' . ($worker['lastname'] ?? '')]) }}</title>
</head>
<body style="font-family: 'Open Sans', sans-serif;color: #212529;background: #fcfcfc;">
    <div style="max-width: 650px;margin: 0 auto;margin-top: 30px;margin-bottom: 20px;background: #fff;border: 1px solid #e6e8eb;border-radius: 6px;padding: 20px;">
        <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="100%">
                    <img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block">
                </td>
            </tr>
        </table>
        <p style="text-align: center;line-height: 30px">{{ __('mail.stop_insuarance_form_non_israel.header') }}</p>
        <p style="text-align: center;line-height: 30px">{{ __('mail.stop_insuarance_form_non_israel.body', [
            'worker_name' => ($worker['firstname'] ?? '') . ' ' . ($worker['lastname'] ?? ''),
            'work_end_date' => $worker['last_work_date'] ?? '',
        ]) }}</p>
        <p style="margin-top: 40px">{{ __('mail.stop_insuarance_form_non_israel.confirmation') }}</p>
        <p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{ __('mail.stop_insuarance_form_non_israel.company') }}</p>
    </div>
</body>
</html>
