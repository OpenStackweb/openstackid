<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>Please click the link below to verify your email address.</p>
<p><a href="{!! $verification_link  !!}" target="_blank">Verify Email Address.</a></p>
<p>If you did not create an {!! Config::get('app.app_name') !!} account, no further action is required.</p>
<br/>
<br/>
<p>Cheers,<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>