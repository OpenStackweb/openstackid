<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>Your email address has been successfully verified.</p>
<p>If you need further assistance, please email <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a></p>
<br/>
<br/>
<p>Cheers,<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>