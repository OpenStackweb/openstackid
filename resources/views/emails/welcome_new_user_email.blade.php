<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Dear {!!$user_fullname!!},</p>
<p>
    You will use this account to access all {!! Config::get('app.tenant_name') !!} community apps and websites that require an {!! Config::get('app.app_name') !!},
    including the virtual Open Infrastructure Summit. Your user details are associated with your {!! Config::get('app.app_name') !!} and you
    are able to grant access to that information to each app at your discretion.
</p>
@if($verification_link)
<p>
    In order to verify your email, please click the verification link: <a href="{!! $verification_link !!}" target="_blank">Verify Email Address.</a>
</p>
@endif
<p>
    To edit your profile just click <a href="{!! URL::action("UserController@getLogin") !!}">here</a>.
    You may update your photo, add a bio, and other information you wish to share.
</p>
<p>
    Should you have any questions, please email
    <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a> for assistance.
</p>
<br/>
<br/>
<p>Thanks!,<br/>Your {!! Config::get('app.tenant_name') !!} Support Team</p>
</body>
</html>