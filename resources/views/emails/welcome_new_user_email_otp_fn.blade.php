@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%">
        <tbody>
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:25px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">
                    Thank you for using a one-time-use code to verify your email address@if(!empty($site_base_url)) on
                    {!! $site_base_url !!}@endif . You now have an {!! Config::get('app.app_name') !!} using the email address <a href="#" style="text-decoration:none !important;color:black !important; cursor:default !important">{!!$user_email!!}</a>.
                </div>
            </td>
        </tr>
        @if(!empty($reset_password_link))
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">In order to login more quickly in the future you can <a href="{!! $reset_password_link !!}" target="_blank">set a password</a> (this link expires in {!! $reset_password_link_lifetime !!} min).</div>
            </td>
        </tr>
        @endif

        @if(!$user_is_complete)
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">You may also <a href="{!! URL::action("UserController@getLogin") !!}" target="_blank">complete your profile</a> by filling in your first name, last name, company, and country so we know what to call you..</div>
            </td>
        </tr>
        @endif
        <tr>
            <td align="center" style="font-size:0px;padding:10px 25px;padding-right:16px;padding-left:25px;word-break:break-word;">
                <div style="font-family:open Sans Helvetica, Arial, sans-serif;font-size:16px;line-height:1;text-align:center;color:#000000;">
                    An {!! Config::get('app.app_name') !!} is a login you can use to access all {!! Config::get('app.tenant_name') !!} apps associated with this event and any other event produced by
                    {!! Config::get('app.tenant_name') !!}. The apps will ask for your permission to access information contained in your profile when you login.
                    Should you have any questions, please email <a href="mailto:{!! Config::get('app.help_email') !!}">{!! Config::get('app.help_email') !!}</a> for assistance.
                </div>
            </td>
        </tr>
        </tbody>
    </table>
@stop