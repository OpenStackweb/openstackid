<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!! $user_fullname !!},</p>
<p>Two-factor authentication (MFA) is now required on your account. You will be asked for a second verification step each time you sign in.</p>
<p>Recovery codes have been set up for your account. Recovery codes are shown only once, so to get a set you can save, sign in, open the Security section of your profile and regenerate them:</p>
<p><a href="{!! $profile_url !!}">{!! $profile_url !!}</a></p>
<p>Keep your recovery codes in a safe place: they let you sign in if you lose access to your second factor.</p>
<br/>
<br/>
<p>Cheers,<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>
