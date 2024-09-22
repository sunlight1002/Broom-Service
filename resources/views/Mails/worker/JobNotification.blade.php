<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title>{{ $emailData['emailTitle'] }}</title>
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
		<h1 style="text-align: center;">{{__('mail.job_common.hi')}}, {{ $worker['firstname'] }} {{ $worker['lastname'] }}</h1>
		<p style="text-align: center;line-height: 30px">{{__('mail.job_common.greetings')}} {{__('mail.job_common.from')}} {{__('mail.job_common.company')}}. {!! $emailData['emailContent'] !!} {{ __('mail.job_common.please_check') }}</p>
		@if(isset($emailData['isJobOpen']) && $emailData['isJobOpen'])
			<p style="text-align: center;line-height: 30px">
			<p>{{$emailData['isJobOpen']}}</p>
                <a href='{{ url("worker/jobs/view/".$job["id"] ) }}'> {{__('mail.job_status.job')}} </a> {{__('mail.job_status.started_by')}}  <a href='{{ url("admin/workers/view/".$job["client_id"] ) }}'> {{ $job['worker']['firstname'] }}  {{ $job['worker']['lastname'] }}.</a>
            </p>
		@endif
		<table cellpadding="0" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.job_common.date')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.job_common.client')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.job_common.service')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.job_common.property_address_txt')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.job_common.start_time')}}</th>
					<th width="" style="text-align:left;border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{__('mail.job_common.status')}}</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ \Carbon\Carbon::parse($job['start_date'])->format('M d Y') }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ $job['client']['firstname'] }} {{ $job['client']['lastname'] }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">
						@if($job['worker']['lng'] == 'heb')
							{{ $job['jobservice']['heb_name'].', ' }}
						@else
							{{ $job['jobservice']['name'].', ' }}
						@endif
					</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ isset($job['property_address']) && $job['property_address'] ? $job['property_address']['address_name'] : "-" }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ \Carbon\Carbon::today()->setTimeFromTimeString($job['start_time'])->format('H:i') }}</td>
					<td style="border: 1px solid #dee2e6;font-size: 14px;padding: 8px">{{ ucfirst($job['status']) }}</td>
				</tr>
			</tbody>
		</table>
		<div style="text-align: center;margin-top: 40px;">
			<a href='{{ url("worker/jobs/view/".$job["id"]) }}' style="background: #ef6c6b;color: #fff;border: 1px solid #ef6c6b;font-size: 16px;padding: 8px 20px;border-radius: 8px;cursor: pointer;text-decoration: none;text-align: center;">{{__('mail.job_common.check_job_details')}}</a>
		</div>
		<p style="margin-top: 40px">{{__('mail.job_common.reply_txt')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.job_common.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.job_common.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.job_common.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>

</html>