@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Resend Email Verification </title>
@append

<!-- @section('content')
    <div class="container">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="signup-form">
            <form id="form-verification"
                  target="_self"
                  method="POST" action="{{ URL::action('Auth\EmailVerificationController@resend') }}">
                @csrf
                <h2>{{ __('Email Verification') }}</h2>
                <div class="form-group">
                    <input type="email" class="form-control" id="email"
                           name="email"
                           value="{{$email}}"
                           placeholder="Email" required="required" autocomplete="username">
                </div>
                <div class="form-group">
                    <div class="recaptcha-container">
                        {!! Recaptcha::render(['id'=>'captcha', 'class'=>'input-block-level', 'callback'=>'verifyCaptchaCallback']) !!}
                        <input type="hidden" name="g_recaptcha_hidden" id="g_recaptcha_hidden">
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit"
                            class="btn btn-primary btn-lg btn-block">{{ __('Resend Verification Email') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection -->

@section('css')
    {!! HTML::style('assets/css/emailVerification.css') !!}
@append
@section('content')

@append
@section('scripts')
    <script>

        // var verifyCaptchaCallback = function(response) {
        //     $('#g_recaptcha_hidden').val(response);
        //     $('#g_recaptcha_hidden').valid();
        // };

        let config = {
            //token :  document.head.querySelector('meta[name="csrf-token"]').content,
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
        }
    </script>
    {!! HTML::script('assets/emailVerification.js') !!}
@append

