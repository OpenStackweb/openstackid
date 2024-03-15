@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Reset Your Password </title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/resetPassword.css') !!}
@append
@section('content')

@append
@section('scripts')
    <script>
        let error = '';
        const initialValues = {
            email: '{!! $email ?? '' !!}',
            password: '',
            password_confirmation: '',
        }
        const passwordPolicy = {
            min_length: {{ Config::get("auth.password_min_length") }},
            max_length: {{ Config::get("auth.password_max_length") }},
            shape_pattern: '{{ Config::get("auth.password_shape_pattern") }}',
            shape_warning: '{{ Config::get("auth.password_shape_warning") }}'
        }
        @if ($errors->any())
                @foreach($errors->all() as $error)
            error += '<p>{!! $error !!}<p/>';
        @endforeach

            initialValues.email = "{{old('email')}}";
        @endif

        let config = {
            appLogo: '{{  Config::get("app.logo_url") }}',
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            csrfToken :  document.head.querySelector('meta[name="csrf-token"]').content,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            initialValues: initialValues,
            token: '{!! $token ?? '' !!}',
            passwordPolicy: passwordPolicy,
            resetPasswordAction: '{{ URL::action("Auth\ResetPasswordController@reset") }}',
            resetPasswordError: error,
            sessionStatus: '{{ session("status") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            submitButtonText: '{{ __("Password Reset") }}',
        }
    </script>
    {!! HTML::script('assets/resetPassword.js') !!}
@append


