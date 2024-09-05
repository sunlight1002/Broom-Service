<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>User Data Report</title>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            color: #212529;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 650px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #e6e8eb;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header img {
            display: block;
            margin: 0 auto;
            width: 100px;
            height: auto;
        }

        .header h1 {
            text-align: center;
            color: #007bff;
            margin: 20px 0;
        }

        .content {
            text-align: center;
        }

        .content p {
            line-height: 1.5;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #e6e8eb;
            padding-top: 10px;
        }

        .footer p {
            margin: 5px 0;
            font-size: 14px;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('../images/sample.png') }}" style="margin: 0 auto;display: block">

            <h1>User Data Report</h1>
        </div>
        <p style="text-align: center;line-height: 30px">{{ __('mail.common.greetings') }}</p>
        <div class="content">
            <p>Please find attached the CSV report of the user data.</p>
            <p>The report includes details about the users, their submission status, and related forms.</p>
            <p>If you have any questions or need further assistance, feel free to reach out.</p>
        </div>
        <div class="footer">
            <p>Attached CSV File: {{ $fileName }}</p>
            <p>{{ __('mail.common.dont_hesitate_to_get_in_touch') }}</p>
            <p><strong>{{ __('mail.common.regards') }}</strong></p>
            <p>{{ __('mail.common.company') }}</p>
            <p>{{ __('mail.common.tel') }}: 03-525-70-60</p>
            <p><a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a></p>
        </div>
    </div>
</body>

</html>
