<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>Thank you for registering for a new {!! Config::get('app.app_name') !!}. You will use this account to access virtual events and other apps.</p>
<p>When using your {!! Config::get('app.app_name') !!} to log in to {!! Config::get('app.tenant_name') !!} virtual events and applications, you will be asked to "accept" permissions so the application can use your information to function accordingly.</p>
<p>Should you have any questions, please email <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a>for assistance.</p>
<br/>
<br/>
<p>Thanks!<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>