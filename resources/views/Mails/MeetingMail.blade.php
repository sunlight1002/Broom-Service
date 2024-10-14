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
	<style>
		body {
			margin: 0;
			padding: 0;
			font-size: 16px;
			line-height: 1.5;
			background-color: #fcfcfc;
			color: #212529;
		}

		table {
			border-spacing: 0;
			border-collapse: collapse;
			width: 100%;
		}

		.container {
			max-width: 650px;
			width: 100%;
			margin: 0 auto;
			background: #fff;
			border: 1px solid #e6e8eb;
			border-radius: 6px;
			padding: 20px;
		}

		/* Responsive for mobile devices */
		@media only screen and (max-width: 600px) {
			.container {
				width: 100%;
				padding: 10px;
			}

			h1 {
				font-size: 22px;
			}

			p {
				font-size: 14px;
			}

			.action-button {
				font-size: 14px;
				padding: 10px 16px;
				margin-bottom: 10px;
				width: 100%;
			}
		}

		/* Buttons styling */
		.action-button {
			background: #187ddb;
			color: #fff;
			border: 1px solid #187ddb;
			font-size: 16px;
			padding: 8px 20px;
			border-radius: 8px;
			cursor: pointer;
			text-decoration: none;
			text-align: center;
			display: inline-block;
			margin-right: 10px;
		}

		.action-button.red {
			background: red;
			border-color: red;
		}

		.action-button.orange {
			background: #de9400;
			border-color: #de9400;
		}

		.action-button.black {
			background: #151021;
			border-color: #151021;
		}

		.center {
			text-align: center;
		}

		/* Hebrew support */
		@if($client['lng'] == 'heb')
			body {
				font-family: 'Noto Sans Hebrew', sans-serif;
				direction: rtl;
			}
		@else
			body {
				font-family: 'Open Sans', sans-serif;
			}
		@endif
	</style>
</head>
<body>

	<div class="container">
		<table cellpadding="0" cellspacing="0" width="100%" >
			<tr>
				<td width="100%">
					<img src="{{ asset('images/sample.png') }}" style="margin: 0 auto;display: block;max-width: 100%;">
				</td>
			</tr>
		</table>

		<h1 class="center">{{__('mail.common.salutation', ['name' => $client['firstname'] . ' ' . $client['lastname']])}}</h1>
		<p class="center">{{__('mail.common.greetings')}}</p>

		@php
			$purpose_txt = '';
			if($purpose == 'Price offer') {
				$purpose_txt = __('mail.meeting.price_offer');
			} elseif($purpose == "Quality check") {
				$purpose_txt = __('mail.meeting.quality_check');
			} else {
				$purpose_txt = $purpose;
			}
		@endphp

		@if($start_date)
		<p class="center">{{__('mail.meeting.content_with_date_time', [
			'team_name' => $client['lng'] == 'heb' ? $team['heb_name'] : $team['name'],
			'date' => \Carbon\Carbon::parse($start_date)->format('d-m-Y'),
			'start_time' => date("H:i", strtotime($start_time)),
			'end_time' => date("H:i", strtotime($end_time)),
			'address' => isset($property_address) ? $property_address['address_name'] : 'NA',
			'purpose' => $purpose_txt
		])}} </p>
		@else
		<p class="center">{{__('mail.meeting.content_without_date_time', [
			'team_name' => $client['lng'] == 'heb' ? $team['heb_name'] : $team['name'],
			'address' => isset($property_address) ? $property_address['address_name'] : 'NA',
			'purpose' => $purpose_txt
		])}} </p>
		@endif

		@if(!empty($meet_link))
		<p class="center">{{$meet_link}}</p>
		@endif

		<div class="center">
			<a href='{{ url("thankyou/".base64_encode($id)."/accept")}}' style="margin-top: 5px; color: white;" target='_blank' class="action-button">{{__('mail.meeting.accept')}}</a>
			<a href='{{ url("thankyou/".base64_encode($id)."/reject")}}' style="margin-top: 5px; color: white;" class="action-button red">{{__('mail.meeting.reject')}}</a>

			@if($start_date)
			<a href='{{ url("meeting-status/".base64_encode($id)."/reschedule")}}' style="margin-top: 5px; color: white;" target='_blank' class="action-button orange">{{__('mail.meeting.reschedule')}}</a>
			@endif

			<a href='{{ url("meeting-files/".base64_encode($id))}}' style="margin-top: 5px; color: white;" target='_blank' class="action-button black">{{__('mail.meeting.upload_job_description')}}</a>
		</div>

		<p style="margin-top: 20px">{{__('mail.common.dont_hesitate_to_get_in_touch')}}</p>
		<p style="font-weight: 700;margin-bottom: 0;">{{__('mail.common.regards')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.common.company')}}</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px">{{__('mail.common.tel')}}: 03-525-70-60</p>
		<p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px"><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
	</div>
	
</body>
</html>
