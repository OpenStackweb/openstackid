@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Email Verification Complete</title>
@append

@section('content')
    <div style="background: #FAFAF7; padding: 3rem 1rem; border-radius: 12px; min-height: 520px; width: 100%;">

        <div style="max-width: 480px; margin: 0 auto; text-align: center;">

            <div style="background: #FFFFFF; border-radius: 12px; border: 0.5px solid #E5E4DE; padding: 2.5rem 2rem;">

                <div style="width: 56px; height: 56px; border-radius: 50%; background: #1D9E75; display: inline-flex; align-items: center; justify-content: center; margin: 0 0 1.25rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>

                <h1 style="font-size: 24px; font-weight: 500; margin: 0 0 0.5rem; color: #0A0A0A; letter-spacing: -0.01em;">Email verified.</h1>
                <p style="font-size: 15px; color: #5F5E5A; margin: 0 0 2rem; line-height: 1.6;">Your {{ Config::get("app.app_name") }} account is active and ready to use.</p>

                <div style="margin: 0 0 1rem;">
                    <a href="{!! URL::action('UserController@getLogin') !!}" target="_self"
                       style="display: inline-block; background: #0A0A0A; color: #FFFFFF; text-decoration: none; font-size: 15px; font-weight: 500; padding: 11px 28px; border-radius: 8px;">
                        Continue to sign in &rarr;
                    </a>
                </div>

                <p style="font-size: 13px; color: #888780; margin: 0; line-height: 1.6;">We've also sent next steps to your inbox.</p>

            </div>

            <p style="font-size: 13px; color: #888780; margin: 1.5rem 0 0; line-height: 1.6;">
                Need help? <a href="mailto:{{ Config::get('app.help_email') }}" style="color: #0C64C8; text-decoration: none;">{{ Config::get('app.help_email') }}</a>
            </p>

        </div>

    </div>
@endsection
