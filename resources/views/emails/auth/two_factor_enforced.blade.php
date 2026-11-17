<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {{ $user_fullname }},</p>
<p>Two-factor authentication (MFA) is now required on your account. You will be asked for a verification code sent to your email each time you sign in.</p>
<p>To get your recovery codes, sign in, open the Two-Factor Authentication section of your profile and use Regenerate Codes (it asks for your current password). The codes are shown only once, so save them right away:</p>
<p><a href="{{ $profile_url }}">{{ $profile_url }}</a></p>
<p>Keep your recovery codes in a safe place: they let you sign in if you lose access to your second factor.</p>
<br/>
<br/>
<p>Cheers,<br/>Your {{ Config::get('app.tenant_name') }} Support Team</p>
</body>
</html>
