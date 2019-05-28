<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!! $user_fullname !!}, your user got blocked
    @if($attempts > 0)
    after {!!$attempts!!} failed login attempts.
    @else
        .
    @endif
</p>
<p>Please send an email to {!!$support_email!!} in order to reactive it again.</p>
<p>Cheers,<br/>{!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>