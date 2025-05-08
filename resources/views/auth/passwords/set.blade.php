@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Set your new password </title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! style_to('assets/css/setPassword.css') !!}
@append
@section('content')

@append
@section('scripts')
    <script>
        let error = '';
        const initialValues = {
            email: '{{ $email }}',
            first_name: '{{ $first_name }}',
            last_name: '{{ $last_name }}',
            company: '{{ $company }}',
            country_iso_code: '',
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
        initialValues.company = "{{old('company')}}";
        initialValues.first_name = "{{old('first_name')}}";
        initialValues.last_name = "{{old('last_name')}}";
            initialValues.country_iso_code = "{{old('country_iso_code')}}";
        @endif

        let countries = [];
        @foreach($countries as $country)
            countries.push({ value: "{!! $country->getAlpha2() !!}", text: "{!! $country->getName() !!}" });
        @endforeach

        let config = {
            appLogo: '{{  Config::get("app.logo_url") }}',
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            clientId: '{{ $client_id }}',
            countries: countries,
            csrfToken :  document.head.querySelector('meta[name="csrf-token"]').content,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            initialValues: initialValues,
            passwordPolicy: passwordPolicy,
            redirectUri: '{{ $redirect_uri }}',
            setPasswordAction:'{{ URL::action("Auth\PasswordSetController@setPassword") }}',
            setPasswordError: error,
            sessionStatus: '{{ session("status") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            submitButtonText: '{{ __("Set Your Password") }}',
            token: '{{ $token }}'
        }
    </script>
    {!! script_to('assets/setPassword.js') !!}
@append

