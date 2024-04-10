<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<title>Job Status</title>
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
		<h1 style="text-align: center;">{{__('mail.job_status.hi')}}, {{$admin['name']}} </h1>
        <p>
            <p style="text-align: center;line-height: 30px">
                <a href='{{ url("worker/view-job/".$job["id"] ) }}'> {{__('mail.job_status.job')}} </a> {{__('mail.job_status.started_by')}}  <a href='{{ url("admin/view-worker/".$job["id"] ) }}'> {{ $worker->firstname}}  {{ $worker->lastname}}.</a>
            </p>
            <p style="margin-top: 3px;font-size: 14px;margin-bottom: 3px;">{{__('mail.job_status.thanks_text')}}</p>
		</p>
	</div>
</body>
</html>