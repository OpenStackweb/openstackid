<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>
    Thank you for your interest in joining the Open Infrastructure community! In order to verify your email,
    please click the verification link: <a href="{!! $verification_link !!}" target="_blank">Verify Email Address.</a>
</p>
<p>
    To edit your profile just click <a href="{!! $bio_link  !!}" target="_blank">here</a>. You may update your photo, add a bio, and other information you wish to share.
</p>
<p>
    If you have questions or concerns, please email <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a>.
</p>
<p>If you did not create an {!! Config::get('app.app_name') !!} account, no further action is required.</p>
<br/>
<br/>
<p>Cheers,<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>