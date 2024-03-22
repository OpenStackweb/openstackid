@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%">
        <tbody>
        <tr>
            <td align="center"
                style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">
                    Please use the single-use code below to sign in:
                </div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:24px;font-weight:bold;line-height:1;text-align:center;color:#000000;">{{$otp}}</div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Code is valid for {{$lifetime}} minutes.</div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you didn't request this, you can ignore this email.</div>
            </td>
        </tr>
        @if(!empty($reset_password_link))
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:justify;color:#000000;">
                        In order to login more quickly in the future you can <a href="{!! $reset_password_link !!}" target="_blank">set a password</a> (this link expires in {!! $reset_password_link_lifetime !!} min but you can always use the <a
                                href="{!! URL::action("Auth\ForgotPasswordController@showLinkRequestForm") !!}?email={!! $email !!}"
                                target="_blank">reset your password</a> option to get a new one).
                    </div>
                </td>
            </tr>
        @endif
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Thanks! <br/><br/>{{Config::get('app.tenant_name')}} Support Team</div>
            </td>
        </tr>
        </tbody>
    </table>
@stop