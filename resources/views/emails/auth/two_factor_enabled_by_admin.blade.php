@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%">
        <tbody>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1.4;text-align:center;color:#000000;">Dear {{ $user_fullname }},</div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1.4;text-align:center;color:#000000;">Two-factor authentication was enabled on your {{ Config::get('app.app_name') }} account by an administrator. From now on you will be asked for a verification code sent to <b>{{ $user_email }}</b> when you sign in.</div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1.4;text-align:center;color:#000000;">Please generate your recovery codes from your <a href="{{ $profile_url }}" target="_blank">profile page</a> and keep them in a safe place: they let you sign in if you cannot receive the verification code.</div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1.4;text-align:center;color:#000000;">If you did not expect this change please email <a href="mailto:{{ Config::get('app.help_email') }}">{{ Config::get('app.help_email') }}</a>.</div>
            </td>
        </tr>
        </tbody>
    </table>
@stop
