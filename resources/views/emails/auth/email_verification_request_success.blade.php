<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>Your email address has been successfully verified.</p>
<br/>
<br/>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>