@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get('app.app_name') }} - Sign Up </title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! style_to('assets/css/signup.css') !!}
@append
@section('content')
    
@append
@section('scripts')
    <script>
        let signUpError = '';
        const initialValues = {
            first_name: '{{ $first_name ?? '' }}',
            last_name: '{{ $last_name ?? '' }}',
            email: '{{ $email ?? '' }}',
            country_iso_code: '',
            password: '',
            password_confirmation: '',
            agree_code_of_conduct: false,
        }
        const passwordPolicy = {
            min_length: {{ Config::get("auth.password_min_length") }},
            max_length: {{ Config::get("auth.password_max_length") }},
        }
        @if ($errors->any())
            @foreach($errors->all() as $error)
                signUpError += '<p>{!! $error !!}<p/>';
            @endforeach

            initialValues.first_name = "{{old('first_name')}}";
            initialValues.last_name = "{{old('last_name')}}";
            initialValues.email = "{{old('email')}}";
            initialValues.country_iso_code = "{{old('country_iso_code')}}";
        @endif

        let countries = [];
        @foreach($countries as $country)
            countries.push({ value: "{!! $country->getAlpha2() !!}", text: "{!! $country->getName() !!}" });
        @endforeach

        let config = {
            csrfToken :  document.head.querySelector('meta[name="csrf-token"]').content,
            realm: '{{isset($identity_select) ? $realm : ""}}',
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            clientId: '{{ $client_id }}',
            codeOfConductUrl: '{!! Config::get("app.code_of_conduct_link") !!}',
            countries: countries,
            redirectUri: '{{ $redirect_uri }}',
            signInAction:'{{ URL::action("UserController@getLogin") }}',
            signUpAction: '{{ URL::action("Auth\RegisterController@register") }}',
            signUpError: signUpError,
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            tenantName: '{{ Config::get("app.tenant_name") }}',
            initialValues: initialValues,
            passwordPolicy: passwordPolicy
        }
    </script>
    {!! script_to('assets/signup.js') !!}
@append
