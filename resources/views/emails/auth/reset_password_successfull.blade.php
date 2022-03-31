@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%">
        <tbody>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">The password was reset for {{Config::get('app.name')}} <a href="#" style="text-decoration:none !important;color:black !important; cursor:default !important; font-weight:bold">{!!$user_email!!}</a>.</div>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">If you did not reset your password, please email <a href="mailto:{{Config::get('app.help_email')}}" style="text-decoration:none !important">{{Config::get('app.help_email')}}</a>.</div>
            </td>
        </tr>
        </tbody>
    </table>
@stop