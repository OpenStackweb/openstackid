@extends('layout')
@section('title')
    <title>Welcome to OpenStackId - Sign Up </title>
@append
@section('scripts')
    {!! HTML::style('assets/css/auth/email.css') !!}
    {!! HTML::script('assets/js/auth/send_password_link.js') !!}
    <script type="application/javascript">
        var verifyCaptchaCallback = function(response) {
            $('#g_recaptcha_hidden').val(response);
            $('#g_recaptcha_hidden').valid();
        };
    </script>
@append

@section('content')
    <div class="container">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{ session('status') }}
            </div>
            @if($redirect_uri)
                <p>Now you will be redirected to <a id="redirect_url" name="redirect_url" href="{{$redirect_uri}}">{{$redirect_uri}}</a></p>
            @endif
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="signup-form">
            <form id="form-send-password-reset-link" method="POST" action="{{ URL::action('Auth\ForgotPasswordController@sendResetLinkEmail') }}">
                @csrf
                <h2>Forgot Password?</h2>
                <p class="hint-text">You can reset your password here.</p>
                <div class="form-group">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required="required"  autocomplete="username">
                </div>
                <div class="form-group">
                    <div class="recaptcha-container" >
                        {!! Recaptcha::render(['id'=>'captcha', 'class'=>'input-block-level', 'callback'=>'verifyCaptchaCallback']) !!}
                        <input type="hidden"name="g_recaptcha_hidden" id="g_recaptcha_hidden">
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Send Password Reset Link') }}</button>
                </div>
                @if($redirect_uri)
                    <input type="hidden" id="redirect_uri" name="redirect_uri" value="{{$redirect_uri}}"/>
                @endif
                @if($client_id)
                    <input type="hidden" id="client_id" name="client_id" value="{{$client_id}}"/>
                @endif
            </form>
        </div>
    </div>
@endsection
