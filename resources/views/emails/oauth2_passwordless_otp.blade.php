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
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">Thanks! <br/><br/>{{Config::get('app.tenant_name')}} Support Team</div>
            </td>
        </tr>
        </tbody>
    </table>
@stop