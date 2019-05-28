<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>You are receiving this email because we received a password reset request for your account.</p>
<p>Please click the link below to reset your password.</p>
<p><a href="{!! $reset_link  !!}" target="_blank">Reset your Password.</a></p>
<p>If you did not request a password reset, no further action is required.</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>