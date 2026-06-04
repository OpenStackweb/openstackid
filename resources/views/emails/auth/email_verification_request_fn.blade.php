@extends('emails.email_layout')

@section('content')
    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
        <tbody>
            <tr>
                <td style="padding: 24px 40px 8px;">
                    <h1 style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 500; margin: 0 0 8px; color: #0A0A0A; letter-spacing: -0.01em;">Confirm your email.</h1>
                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 15px; color: #5F5E5A; margin: 0 0 28px; line-height: 1.6;">Verify <span style="font-family: 'Courier New', monospace; font-size: 14px; color: #0A0A0A;">{{ $user_email }}</span> to finish setting up your FNid.</p>
                </td>
            </tr>
            <tr>
                <td style="padding: 0 40px 28px;">
                    <a href="{{ $verification_link }}" target="_blank" style="display: inline-block; background: #0A0A0A; color: #FFFFFF; text-decoration: none; font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 500; padding: 11px 22px; border-radius: 6px;">Verify email &rarr;</a>
                </td>
            </tr>
            <tr>
                <td style="padding: 0 40px 28px;">
                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 13px; color: #888780; margin: 0; line-height: 1.6;">Or paste this link into your browser:<br><span style="font-family: 'Courier New', monospace; font-size: 12px; color: #5F5E5A; word-break: break-all;">{{ $verification_link }}</span></p>
                </td>
            </tr>
            <tr>
                <td style="padding: 0 40px 24px; border-top: 1px solid #E5E4DE;">
                    <p style="font-family: 'Open Sans', Helvetica, Arial, sans-serif; font-size: 13px; color: #5F5E5A; margin: 20px 0 0; line-height: 1.6;">Didn't sign up for an {!! Config::get('app.app_name') !!} account? You can ignore this email &mdash; your account will be inactive without verification.</p>
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