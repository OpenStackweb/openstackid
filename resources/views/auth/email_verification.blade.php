@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Resend Email Verification </title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! style_to('assets/css/emailVerification.css') !!}
@append
@section('content')

@append
@section('scripts')
<script>
        let error = '';
        const initialValues = { email: '{{ $email }}' }
        @if ($errors->any())
            @foreach($errors->all() as $error)
                error += '<p>{!! $error !!}<p/>';
            @endforeach

            initialValues.email = "{{old('email')}}";
        @endif

        let config = {
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            csrfToken :  document.head.querySelector('meta[name="csrf-token"]').content,
            emailVerificationAction: '{{ URL::action("Auth\EmailVerificationController@resend") }}',
            emailVerificationError: error,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            initialValues: initialValues,
            sessionStatus: '{{ session("status") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            submitButtonText: '{{ __("Resend Verification Email") }}',
        }
    </script>
    {!! script_to('assets/emailVerification.js') !!}
@append

