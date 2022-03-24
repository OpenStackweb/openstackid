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
            userName:'{{ Session::has(\App\Http\Utils\SessionConstants::UserName) ? Session::get(\App\Http\Utils\SessionConstants::UserName) : ""}}',
            realm: '{{isset($identity_select) ? $realm : ""}}',
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            formAction: '{{ URL::action("UserController@postLogin") }}',
            accountVerifyAction : '{{URL::action("UserController@getAccount")}}',
            emitOtpAction : '{{URL::action("UserController@emitOTP")}}',
            authError: authError,
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            flow: 'password',
            thirdPartyProviders: [
                @foreach($supported_providers as $provider => $label)
                {label: "{{$label}}", name:"{{$provider}}"},
                 @endforeach
            ],
            forgotPasswordAction:'{{ URL::action("Auth\ForgotPasswordController@showLinkRequestForm") }}',
            verifyEmailAction:'{{ URL::action("Auth\EmailVerificationController@showVerificationForm") }}',
            helpAction:'mailto:{!! Config::get("app.help_email") !!}',
            createAccountAction:'{{ URL::action("Auth\RegisterController@showRegistrationForm") }}',
            allowNativeAuth: parseInt('{{ Config::get("auth.allows_native_auth", 1) }}') === 1 ? true: false,
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
        }

        @if(Session::has('max_login_attempts_2_show_captcha'))
            config.maxLoginAttempts2ShowCaptcha = {{Session::get("max_login_attempts_2_show_captcha")}};
        @endif

        @if(Session::has('login_attempts'))
            config.loginAttempts = {{Session::get("login_attempts")}};
        @endif

        @if(Session::has('user_fullname'))
            config.user_fullname = '{{Session::get(\App\Http\Utils\SessionConstants::UserFullName)}}';
        @endif

        @if(Session::has(\App\Http\Utils\SessionConstants::UserPic))
            config.user_pic = '{{Session::get(\App\Http\Utils\SessionConstants::UserPic)}}';
        @endif
        @if(Session::has('user_verified'))
            config.user_verified = {{Session::get(\App\Http\Utils\SessionConstants::UserVerified)}};
        @endif
        @if(Session::has('flow'))
            config.flow = '{{Session::get('flow')}}';
        @endif

        window.VERIFY_ACCOUNT_ENDPOINT = config.accountVerifyAction;
        window.EMIT_OTP_ENDPOINT = config.emitOtpAction;
    </script>
    {!! HTML::script('assets/login.js') !!}
@append