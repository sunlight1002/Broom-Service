<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.hearing.subject') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
</head>

<body style="font-family: 'Open Sans', sans-serif; color: #212529; background: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 650px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); padding: 30px;">

        <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="100%">
                    <img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block">
                </td>
            </tr>
        </table>

        <h2 style="text-align: center; color: #333; font-weight: 600;">
            {{ __('mail.hearing.salutation', ['worker_name' => ($data['worker']['firstname'] ?? '') . ' ' . ($data['worker']['lastname'] ?? '')]) }}
        </h2>

        <p style="text-align: center; font-size: 16px; line-height: 1.6; color: #444;">
            {{ __('mail.hearing.body', [
                'team_name' => $data['team']['name'] ?? '-',
                'date' => \Carbon\Carbon::parse($data['start_date'])->format('d-m-Y'),
                'start_time' => \Carbon\Carbon::parse($data['start_time'])->format('H:i'),
                'end_time' => \Carbon\Carbon::parse($data['end_time'])->format('H:i')
            ]) }}
        </p>

        <!-- <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">

        <table style="width: 100%; font-size: 15px; color: #333;">
            <tr>
                <td style="padding: 10px 0;"><strong>{{ __('Purpose') }}:</strong></td>
                <td style="padding: 10px 0;">{{ $data['purpose'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0;"><strong>{{ __('Team') }}:</strong></td>
                <td style="padding: 10px 0;">{{ $data['team'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0;"><strong>{{ __('Date') }}:</strong></td>
                <td style="padding: 10px 0;">{{ \Carbon\Carbon::parse($data['start_date'])->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0;"><strong>{{ __('Time') }}:</strong></td>
                <td style="padding: 10px 0;">
                    {{ \Carbon\Carbon::parse($data['start_time'])->format('H:i') }} - 
                    {{ \Carbon\Carbon::parse($data['end_time'])->format('H:i') }}
                </td>
            </tr>
        </table> -->

        <p style="font-size: 14px; margin-top: 30px; text-align: left; color: black;">
            {{ __('mail.hearing.regards') }}
        </p>

        <p style="font-size: 14px; text-align: left; color: black;">
            {{ __('mail.hearing.signature') }}
        </p>
    </div>
</body>

</html>