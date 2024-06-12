<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title>Job Review</title>
</head>

<body style="@if($job['client']['lng'] == 'heb') font-family: 'Noto Sans Hebrew', sans-serif;color: #212529;background: #fcfcfc; direction:rtl @else font-family: 'Open Sans', sans-serif;color: #212529;background: #fcfcfc; @endif">

	<div style="max-width: 650px;margin: 0 auto;margin-top: 30px;margin-bottom: 20px;background: #fff;border: 1px solid #e6e8eb;border-radius: 6px;padding: 20px;">
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="100%">
					<img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block">
				</td>
			</tr>
		</table>
		<h1 style="text-align: center;">{{__('mail.client_new_job.hi')}}, {{ $job['client']['firstname'] }}</h1>

		<p style="text-align: center;line-height: 30px">{{__('mail.common.greetings')}}. {{__('mail.client_job_status.job_completed')}}</p>

		<table cellpadding="0" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.client_new_job.date')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.client_new_job.service')}}</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ \Carbon\Carbon::parse($job['start_date'])->format('M d Y') }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">

						@if($job['client']['lng'] == 'heb')
						{{ $job['jobservice']['heb_name'] }}
						@else
						{{ $job['jobservice']['name'] }}
						@endif

					</td>
				</tr>
			</tbody>
		</table>

		<p style="margin-top: 40px">{{__('mail.client.review-request.message')}}</p>
		<a href='{{ url("client/jobs").'/'.base64_encode($job['id']).'/review' }}' style="background: #ef6c6b;color: #fff;border: 1px solid #ef6c6b;font-size: 16px;padding: 8px 20px;border-radius: 8px;cursor: pointer;text-decoration: none;text-align: center;">{{__('mail.client_job_status.review')}}</a>

		<p style="margin-top: 40px">{{__('mail.common.dont_hesitate_to_get_in_touch')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.common.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.common.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.common.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>

</html>