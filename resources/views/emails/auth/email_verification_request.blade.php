<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>Please click the link below to verify your email address.</p>
<p><a href="{!! $verification_link  !!}" target="_blank">Verify Email Address.</a></p>
<p>If you did not create an account, no further action is required.</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>