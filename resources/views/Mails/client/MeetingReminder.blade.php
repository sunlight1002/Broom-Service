<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title>Schedule meeting</title>
</head>
@if($client['lng'] == 'heb')
<body style="font-family: 'Noto Sans Hebrew', sans-serif;color: #212529;background: #fcfcfc; direction:rtl">
@else 
<body style="font-family: 'Open Sans', sans-serif;color: #212529;background: #fcfcfc;">
@endif


	<div style="max-width: 650px;margin: 0 auto;margin-top: 30px;margin-bottom: 20px;background: #fff;border: 1px solid #e6e8eb;border-radius: 6px;padding: 20px;">
		<table cellpadding="0" cellspacing="0" width="100%" >
			<tr>
				<td width="100%">
					<img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block">
				</td>
			</tr>
		</table>
		<h1 style="text-align: center;">{{__('mail.common.salutation', ['name' => $client['firstname'] . ' ' . $client['lastname']])}}</h1>

		<p style="text-align: center;">{{__('mail.common.greetings')}}</p>

		<p style="text-align: center;">{{__('mail.meeting.appointment')}}

		@if(!empty($team['name'])) 
		{{__('mail.meeting.with')}}      <span style="color:#0130c6;font-weight:700;">{{ $client['lng'] == 'heb' ? $team['heb_name'] : $team['name']}}</span>
		 @endif

			@if($schedule['start_date'])
			{{__('mail.meeting.on')}}       <span style="color:#0130c6;font-weight:700;">{{ \Carbon\Carbon::parse($schedule['start_date'])->format('d-m-Y')}}</span>
			{{__('mail.meeting.between')}}  <span style="color:#0130c6;font-weight:700;">{{date("H:i", strtotime($schedule['start_time']))}}</span>
			{{__('mail.meeting.to')}}       <span style="color:#0130c6;font-weight:700;">{{date("H:i", strtotime($schedule['end_time']))}}</span>
			@endif

		 @if(isset($schedule['property_address']))
		 {{__('mail.meeting.address_txt')}}       <span style="color:#0130c6;font-weight:700;">{{ isset($schedule['property_address'])?$schedule['property_address']['address_name']:'NA' }}</span>
		 @endif
		
		 @if($schedule['purpose'] != '') 
         {{__('mail.meeting.for')}}  

		 @if($schedule['purpose'] == 'Price offer')   
		 <span style="color:#0130c6;font-weight:700;">{{ __('mail.meeting.price_offer') }}&nbsp;</span></p>
		 @elseif($schedule['purpose'] == "Quality check")
		 <span style="color:#0130c6;font-weight:700;">{{ __('mail.meeting.quality_check') }}&nbsp;</span></p>
		 @else
		 <span style="color:#0130c6;font-weight:700;">{{ $schedule['purpose'] }}&nbsp;</span></p>
		 @endif

		 @endif


		@if(!empty($schedule['meet_link']))
		<p style="text-align: center;">{{ $schedule['meet_link'] }}</p>
		@endif

		<div style="display:flex;justify-content: center">
			<a href='{{ url("thankyou/".base64_encode($schedule["id"])."/accept")}}' target='_blank' style="background: #187ddb;color: #fff;border: 1px solid #187ddb;font-size: 16px;padding: 8px 20px;border-radius: 8px;cursor: pointer;text-decoration: none;text-align: center; margin-right: 10px;">{{__('mail.meeting.accept')}}</a>
			<a href='{{ url("thankyou/".base64_encode($schedule["id"])."/reject")}}' style="background: red;color: #fff;border: 1px solid red;font-size: 16px;padding: 8px 20px;border-radius: 8px;cursor: pointer;text-decoration: none;text-align: center; margin-right: 10px;">{{__('mail.meeting.reject')}}</a>

			@if($schedule['start_date'])
			<a href='{{ url("meeting-status/".base64_encode($schedule["id"])."/reschedule")}}' target='_blank' style="background: #de9400;color: #fff;border: 1px solid #de9400;font-size: 16px;padding: 8px 20px;border-radius: 8px;cursor: pointer;text-decoration: none;text-align: center; margin-right: 10px;">{{__('mail.meeting.reschedule')}}</a>
			@endif

			<a href='{{ url("meeting-files/".base64_encode($schedule["id"]))}}' target='_blank' style="background: #151021;color: #fff;border: 1px solid #151021;font-size: 16px;padding: 8px 20px;border-radius: 8px;cursor: pointer;text-decoration: none;text-align: center;">{{__('mail.meeting.upload_job_description')}}</a>
		</div>
		<p style="margin-top: 20px">{{__('mail.meeting.below_line')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.common.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.common.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.common.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>
</html>