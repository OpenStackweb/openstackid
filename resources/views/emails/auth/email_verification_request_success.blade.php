@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%">
        <tbody>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Your {!! Config::get('app.app_name') !!}  <b><a href="#" style="text-decoration:none !important;color:black !important; cursor:default !important">{!!$user_email!!}</a></b> has been successfully verified.</div>
            </td>
        </tr>

        @if(!empty($reset_password_link))
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Remember, if you need to set a password, you may do so <a href="{!! $reset_password_link !!}" target="_blank">here</a> (link expires in {!! $reset_password_link_lifetime !!} minutes).</div>
                </td>
            </tr>
        @endif
        @if(!$user_is_complete)
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">You may enter your profile details <a href="{!! URL::action("UserController@getLogin") !!}" target="_blank">here</a>.</div>
            </td>
        </tr>
        @endif
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you need further assistance please email <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a>.</div>
            </td>
        </tr>
        </tbody>
    </table>
@stop