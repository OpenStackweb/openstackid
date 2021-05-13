@extends('auth_layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Sign in </title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/login.css') !!}
@append
@section('content')

@append
@section('scripts')
    <script>

        let authError = '';
        @if(Session::has('flash_notice'))
            authError = '{!! Session::get("flash_notice") !!}';
        @else
            @foreach($errors->all() as $message)
            authError = '{!! $message !!}';
            @endforeach
        @endif

        let config = {
            token :  document.head.querySelector('meta[name="csrf-token"]').content,
            userName:'{{ Session::has('username') ? Session::get('username') : ""}}',
            realm: '{{isset($identity_select) ? $realm : ""}}',
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            formAction: '{{ URL::action("UserController@postLogin") }}',
            accountVerifyAction : '{{URL::action("UserController@getAccount")}}',
            authError: authError,
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            thirdPartyProviders: [
                @foreach($supported_providers as $provider => $label)
                {label: "{{$label}}", name:"{{$provider}}"},
                 @endforeach
            ],
            forgotPasswordAction:'{{ URL::action("Auth\ForgotPasswordController@showLinkRequestForm") }}',
            verifyEmailAction:'{{ URL::action("Auth\EmailVerificationController@showVerificationForm") }}',
            helpAction:'mailto:{!! Config::get("app.help_email") !!}',
            createAccountAction:'{{ URL::action("Auth\RegisterController@showRegistrationForm") }}',
        }

        @if(Session::has('max_login_attempts_2_show_captcha'))
            config.maxLoginAttempts2ShowCaptcha = {{Session::get("max_login_attempts_2_show_captcha")}};
        @endif

       @if(Session::has('login_attempts'))
            config.loginAttempts = {{Session::get("login_attempts")}};
        @endif

        @if(Session::has('user_fullname'))
            config.user_fullname = '{{Session::get("user_fullname")}}';
        @endif

        @if(Session::has('user_pic'))
            config.user_pic = '{{Session::get("user_pic")}}';
        @endif
        @if(Session::has('user_verified'))
            config.user_verified = {{Session::get('user_verified')}};
        @endif

        window.VERIFY_ACCOUNT_ENDPOINT = config.accountVerifyAction;

    </script>
    {!! HTML::script('assets/login.js') !!}
@append