@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%">
        <tbody>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Your {!! Config::get('app.app_name') !!}  is: <a href="#" style="text-decoration:none !important;color:black !important; cursor:default !important">{!!$user_email!!}</a></div>
            </td>
        </tr>
        @if(!empty($reset_password_link))
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you did not set a password you can <a href="{!! $reset_password_link !!}" target="_blank">do so now</a> (this link expires in {!! $reset_password_link_lifetime !!} min).</div>
                </td>
            </tr>
        @endif
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">
                    You will use this account to access all {!! Config::get('app.tenant_name') !!} community apps and websites that require an {!! Config::get('app.app_name') !!},
                    including the virtual Open Infrastructure Summit. Your user details are associated with your {!! Config::get('app.app_name') !!} and you
                    are able to grant access to that information to each app at your discretion.
                </div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">
                    To edit your profile just click <a href="{!! URL::action("UserController@getLogin") !!}">here</a>.
                    You may update your photo, add a bio, and other information you wish to share.
                </div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Should you have any questions, please email <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a> for assistance.</div>
            </td>
        </tr>
        </tbody>
    </table>
@stop