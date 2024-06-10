<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title>Job Unassigned</title>
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
		<h1 style="text-align: center;">{{__('mail.worker_new_job.hi')}}, {{ $old_worker['firstname'] }} {{ $old_worker['lastname'] }}</h1>
		<p style="text-align: center;line-height: 30px">{{__('mail.worker_new_job.greetings')}} {{__('mail.worker_new_job.from')}} {{__('mail.worker_new_job.company')}}. {{__('mail.worker_unassigned.you_unassigned_from_job')}} {{__('mail.worker_new_job.please_check')}}</p>
		<table cellpadding="0" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.worker_new_job.date')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.worker_new_job.client')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.worker_new_job.service')}}</th>
					<th style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.worker_new_job.start_time')}}</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ \Carbon\Carbon::parse($old_job['start_date'])->format('M d Y') }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ $job['client']['firstname'] }} {{ $job['client']['lastname'] }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">
						@if($old_worker['lng'] == 'heb')
						{{ $job['jobservice']['heb_name'] }}
						@else
						{{ $job['jobservice']['name'] }}
						@endif
					</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ \Carbon\Carbon::today()->setTimeFromTimeString($old_job['start_time'])->format('H:i') }}</td>
				</tr>
			</tbody>
		</table>
		<p style="margin-top: 40px">{{__('mail.worker_new_job.reply_txt')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.worker_new_job.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.worker_new_job.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.worker_new_job.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>

</html>