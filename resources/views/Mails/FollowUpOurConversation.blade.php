<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Follow-up on Our Conversation | Broom Service</title>
</head>
<body style="font-family: 'Open Sans', sans-serif; color: #212529; background: #fcfcfc;">
    <div style="max-width: 650px; margin: 0 auto; margin-top: 30px; margin-bottom: 20px; background: #fff; border: 1px solid #e6e8eb; border-radius: 6px; padding: 20px;">
        <table cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="100%">
                    <img src="{{ asset('images/sample.png') }}" style="margin: 0 auto; display: block;">
                </td>
            </tr>
        </table>
        <h1 style="text-align: center;">{{ __('mail.follow_up_conversation.header') }}</h1>
        <p style="text-align: center;">{{ __('mail.follow_up_conversation.greeting', ['name' => $client['firstname']]) }}</p>
        <p style="text-align: center;">{{ __('mail.follow_up_conversation.content') }}</p>
        <p style="text-align: center;">
            {{ __('mail.follow_up_conversation.details1')}}
            <a href="https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl">Client Testimonials</a>
        </p>
        <p style="text-align: center; margin-top: 10px; margin-bottom: 10px;">{{ __('mail.follow_up_conversation.brochure') }}</p>

        <p style="text-align: center;">{{ __('mail.follow_up_conversation.assistance') }}</p>
        <p style="margin-top: 20px;">{{ __('mail.common.dont_hesitate_to_get_in_touch') }}</p>
        <p style="font-weight: 700; margin-bottom: 0;">{{ __('mail.common.regards') }}</p>
        <p style="margin-top: 3px; font-size: 14px; margin-bottom: 3px;">{{ __('mail.common.company') }}</p>
        <p style="margin-top: 3px; font-size: 14px; margin-bottom: 3px;">{{ __('mail.common.tel') }}: 03-525-70-60</p>
        <p style="margin-top: 3px; font-size: 14px; margin-bottom: 3px;">
            <a href="mailto:office@broomservice.co.il">office@broomservice.co.il</a>
        </p>
    </div>
</body>
</html>
