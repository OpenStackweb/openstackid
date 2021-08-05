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
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you have not entered your first name, last name, company, and country please <a href="{!! URL::action("UserController@getLogin") !!}" target="_blank">do so in your profile now</a>. You may also update your photo, add a bio, and provide other information you may wish to share.</div>
            </td>
        </tr>
        @if($verification_link)
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                    <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">
                        In order to verify your email, please click the verification link: <a href="{!! $verification_link !!}" target="_blank">Verify Email Address.</a>
                    </div>
                </td>
            </tr>
        @endif
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Your {!! Config::get('app.app_name') !!} can be used to access all {!! Config::get('app.tenant_name') !!} apps necessary to interact with the event.  In order for the apps to operate, each one will ask permission for your information or the authority to use your {!! Config::get('app.app_name') !!}.</div>
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