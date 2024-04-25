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
	<title>Metting Files</title>
</head>
<body style="font-family: 'Open Sans', sans-serif;color: #212529;background: #fcfcfc;">


	<div style="max-width: 650px;margin: 0 auto;margin-top: 30px;margin-bottom: 20px;background: #fff;border: 1px solid #e6e8eb;border-radius: 6px;padding: 20px;">
		<table cellpadding="0" cellspacing="0" width="100%" >
			<tr>
				<td width="100%">
					<img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block">
				</td>
			</tr>
		</table>
		<h1 style="text-align: center;">{{__('mail.meeting.hi')}}, {{$name}}</h1>
       
		<p style="text-align: center;">{{__('mail.meeting.greetings')}} {{__('mail.meeting.from')}} {{__('mail.meeting.company')}}. {{__('mail.meeting.file')}}
		
		<p style="text-align: center;">{{$client['firstname']}} {{$client['lastname']}} {{__('mail.meeting.file_content')}}</p>
		
		<p> Metting 
		@if($start_date)
		{{__('mail.meeting.on')}}       <span style="color:#0130c6;font-weight:700;">{{ \Carbon\Carbon::parse($start_date)->format('d-m-Y')}}</span>
		{{__('mail.meeting.between')}}  <span style="color:#0130c6;font-weight:700;">{{date("H:i", strtotime($start_time))}}</span>
		{{__('mail.meeting.to')}}       <span style="color:#0130c6;font-weight:700;">{{date("H:i", strtotime($end_time))}}</span>
		@endif		
		</p>
	
		<p style="margin-top: 20px">{{__('mail.meeting.below_line')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.meeting.best_regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.meeting.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.meeting.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
</body>
</html>