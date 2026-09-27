@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td style="padding: 24px 40px 8px;">
                    <h1 style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 500; margin: 0 0 8px; color: #0A0A0A; letter-spacing: -0.01em;">Your FNid is ready.</h1>
                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 15px; color: #5F5E5A; margin: 0 0 24px; line-height: 1.6;">One identity, every FNTECH app &mdash; for this event and the next.</p>
                </td>
            </tr>
            <tr>
                <td style="padding: 0 40px 32px;">
                    <div style="background: #F5F4EF; border-radius: 6px; padding: 14px 16px;">
                        <span style="font-family: 'Courier New', monospace; font-size: 14px; color: #0A0A0A; word-break: break-all;">{{ $user_email }}</span>
                    </div>
                </td>
            </tr>
            @if(!empty($reset_password_link))
            <tr>
                <td style="padding: 0 40px 32px;">
                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                        <tbody>
                            <tr>
                                <td style="vertical-align: top; width: 32px; padding-right: 10px;">
                                    <div style="width: 22px; height: 22px; border-radius: 50%; background: #E6F1FB; color: #0C64C8; font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 500; text-align: center; line-height: 22px;">1</div>
                                </td>
                                <td>
                                    <div style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 500; color: #0A0A0A; margin-bottom: 4px;">Set your password</div>
                                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 14px; color: #5F5E5A; margin: 0 0 12px; line-height: 1.6;">Link expires in {!! $reset_password_link_lifetime !!} minutes.</p>
                                    <a href="{!! $reset_password_link !!}" target="_blank" style="display: inline-block; background: #0A0A0A; color: #FFFFFF; text-decoration: none; font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; padding: 9px 18px; border-radius: 6px;">Set password &rarr;</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            @endif
            @if(!$user_is_complete)
            <tr>
                <td style="padding: 0 40px 32px;">
                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                        <tbody>
                            <tr>
                                <td style="vertical-align: top; width: 32px; padding-right: 10px;">
                                    <div style="width: 22px; height: 22px; border-radius: 50%; background: #E6F1FB; color: #0C64C8; font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 500; text-align: center; line-height: 22px;">{{ !empty($reset_password_link) ? '2' : '1' }}</div>
                                </td>
                                <td>
                                    <div style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 500; color: #0A0A0A; margin-bottom: 4px;">Complete your profile</div>
                                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 14px; color: #5F5E5A; margin: 0 0 12px; line-height: 1.6;">Add your name, company, and country. Photo and bio optional.</p>
                                    <a href="{!! $bio_link !!}" target="_blank" style="display: inline-block; background: transparent; color: #0A0A0A; text-decoration: none; font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 500; padding: 8px 17px; border-radius: 6px; border: 1px solid #B4B2A9;">Open profile &rarr;</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            @endif
            <tr>
                <td style="padding: 0 40px 24px; border-top: 1px solid #E5E4DE;">
                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 13px; color: #5F5E5A; margin: 20px 0 0; line-height: 1.6;">You stay in control. Each {!! Config::get('app.tenant_name') !!} app will ask before accessing your information or acting on your behalf.</p>
                </td>
            </tr>
            <tr>
                <td style="background: #F5F4EF; padding: 20px 40px; font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 13px; color: #5F5E5A; text-align: center;">
                    Questions? <a href="mailto:{!! Config::get('app.help_email') !!}" style="color: #0C64C8; text-decoration: none;">{!! Config::get('app.help_email') !!}</a>
                </td>
            </tr>
        </tbody>
    </table>
@stop