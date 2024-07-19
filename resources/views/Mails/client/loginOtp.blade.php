<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title> Otp for login </title>
</head>
<body @if($client->lng =='heb') style="font-family: 'Noto Sans Hebrew', sans-serif;color: #212529;background: #fcfcfc; direction:rtl" @else style="font-family: 'Open Sans', sans-serif;color: #212529;background: #fcfcfc;" @endif>
	<div style="max-width: 650px;margin: 0 auto;margin-top: 30px;margin-bottom: 20px;background: #fff;border: 1px solid #e6e8eb;border-radius: 6px;padding: 20px;">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%">
					<img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block">
				</td>
			</tr>
		</table>
		<h1 style="text-align: center;">{{__('mail.common.salutation', ['name' => $client->firstname])}}</h1>
		<p style="text-align: center;line-height: 30px">{{ __('mail.otp.body', ['otp' => $otp]) }}</p>
		<p style="text-align: center;line-height: 30px"> {{ __('mail.otp.expiration') }}</p>
		

		<p style="text-align: center;line-height: 30px">{{__('mail.common.greetings')}}</p>
		<p style="margin-top: 40px">{{__('mail.common.dont_hesitate_to_get_in_touch')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.common.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.common.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.common.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>

</html>


