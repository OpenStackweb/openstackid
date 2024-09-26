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
                <div style="font-family: Monaco, monospace,OpenSans, Helvetica;font-size:44px;font-weight:bold;line-height:1;text-align:center;color:#000000;">{{$otp}}</div>
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
                        For faster login in, <a href="{!! $reset_password_link !!}" target="_blank">set up a password now</a> (this link will expire in {!! $reset_password_link_lifetime !!} minutes). You may <a
                                href="{!! URL::action("Auth\ForgotPasswordController@showLinkRequestForm") !!}?email={!! $email !!}"
                                target="_blank">reset your password</a> and/or get a new link at any time.
                    </div>
                </td>
            </tr>
        @endif
        @if(!is_null($client))

            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <hr style="margin: 10px 0;">
                    <div style="font-family: Monaco, monospace,OpenSans, Helvetica;font-size:22px;font-weight:bold;line-height:1;text-align:center;color:#000000;">
                        {!! $client->getApplicationName() !!}
                    </div>
                    <hr style="margin: 10px 0;">
                </td>
            </tr>
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;font-weight:bold;line-height:1;text-align:justify;color:#000000;">
                         This app would like to:
                    </div>
                </td>
            </tr>
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <ul style="list-style-type: none;padding-left: 0;margin-left: 0;">
                        @foreach($client->getClientScopes() as $scope)
                            <li style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:justify;color:#000000;">
                                {!! $scope->getShortDescription() !!}
                            </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            <tr>
                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                    <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:justify;color:#000000;">
                        ** <b>{!! $client->getApplicationName() !!}</b> Application and <b>{!! Config::get("app.tenant_name") !!}</b>  will use this information in accordance with their respective terms of service and privacy policies.
                    </div>
                    <hr style="margin: 20px 0;">
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