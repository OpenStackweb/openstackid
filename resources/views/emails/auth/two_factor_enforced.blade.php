<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {{ $user_fullname }},</p>
<p>Two-factor authentication (MFA) is now required on your account. You will be asked for a verification code sent to your email each time you sign in.</p>
<p>The next time you sign in, after you enter the verification code, a window will show your recovery codes. They are shown only once, so save them right away and confirm with "I've Saved These Codes". You can also get a new set at any time from the Two-Factor Authentication section of your profile (it asks for your current password):</p>
<p><a href="{{ $profile_url }}">{{ $profile_url }}</a></p>
<p>Keep your recovery codes in a safe place: they let you sign in if you lose access to your second factor.</p>
<br/>
<br/>
<p>Cheers,<br/>Your {{ Config::get('app.tenant_name') }} Support Team</p>
</body>
</html>
