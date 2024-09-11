@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get('app.app_name') }} - Reset your Password </title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! style_to('assets/css/forgotPassword.css') !!}
@append
@section('content')

@append
@section('scripts')
    <script>
        let error = '';
        const initialValues = { email: '{!! $email !!}' }
        @if ($errors->any())
            @foreach($errors->all() as $error)
                error += '<p>{!! $error !!}<p/>';
            @endforeach

            initialValues.email = "{{ old('email') }}";
        @endif

        let config = {
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            clientId: '{{ $client_id }}',
            csrfToken :  document.head.querySelector('meta[name="csrf-token"]').content,
            forgotPasswordAction: '{{ URL::action("Auth\ForgotPasswordController@sendResetLinkEmail") }}',
            forgotPasswordError: error,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            initialValues: initialValues,
            redirectUri: '{{ $redirect_uri }}',
            sessionStatus: '{{ session("status") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            submitButtonText: '{{ __("Send Password Reset Link") }}',
        }
    </script>
    {!! script_to('assets/forgotPassword.js') !!}
@append
