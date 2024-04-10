<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title>Change Worker Request</title>
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
		<h1 style="text-align: center;">{{__('mail.change_worker_request.hi')}}, {{ $admin['name'] }}</h1>
		<p style="text-align: center;line-height: 30px">{{__('mail.change_worker_request.greetings')}} {{__('mail.change_worker_request.from')}} {{__('mail.change_worker_request.company')}}. {{__('mail.change_worker_request.content')}} {{__('mail.change_worker_request.please_check')}}</p>
		<table cellpadding="0" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.change_worker_request.date')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.change_worker_request.client')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.change_worker_request.service')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.change_worker_request.property')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.change_worker_request.worker')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.change_worker_request.shift')}}</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ \Carbon\Carbon::parse($job['start_date'])->format('M d Y') }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ $job['client']['firstname'] }} {{ $job['client']['lastname'] }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">
						{{ $job['jobservice']['name'] }}
					</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ $job['property_address']['address_name'] }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ @if($job['worker']) $job['worker']['firstname'] }} {{ $job['worker']['lastname'] }} @else NA @endif</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ $job['shifts'] }}</td>
				</tr>
			</tbody>
		</table>
		<p style="margin-top: 40px">{{__('mail.change_worker_request.reply_txt')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.change_worker_request.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.change_worker_request.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.change_worker_request.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>

</html>