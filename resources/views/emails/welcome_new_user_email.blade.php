<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>Thank you for registering with {!! Config::get('app.app_name') !!} !</p>
<p>You will receive an email verification message shortly, which will allow you to activate your account.</p>
<br/>
<br/>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>