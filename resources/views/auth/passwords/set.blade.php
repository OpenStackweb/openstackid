@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Set your new password </title>
@append
@section('scripts')
    {!! HTML::style('assets/css/auth/email.css') !!}
    {!! HTML::script('assets/pwstrength-bootstrap/pwstrength-bootstrap.js') !!}
    {!! HTML::script('assets/js/auth/set_password.js') !!}
    <script type="application/javascript">
        var verifyCaptchaCallback = function (response) {
            $('#g_recaptcha_hidden').val(response);
            $('#g_recaptcha_hidden').valid();
        };
    </script>
@append

@section('content')
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
            <form id="form-password-set" class="form-horizontal" method="POST"
                  action="{{ URL::action('Auth\PasswordSetController@setPassword') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <h2>Set your Password</h2>
                <p class="hint-text">You can set your password here.</p>
                <div class="form-group">
                    <input type="email" value="{{$email}}" class="form-control" id="email" name="email"
                           placeholder="Email" required="required" readonly="true" autocomplete="username">
                </div>
                <div class="form-group password-container">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                           required="required" autocomplete="new-password">
                </div>
                <div class="form-group password-container">
                    <input type="password" class="form-control" id="password-confirm" name="password_confirmation"
                           placeholder="Confirm Password" required="required" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <div class="recaptcha-container">
                        {!! Recaptcha::render(['id'=>'captcha', 'class'=>'input-block-level', 'callback'=>'verifyCaptchaCallback']) !!}
                        <input type="hidden" name="g_recaptcha_hidden" id="g_recaptcha_hidden">
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit"
                            class="btn btn-primary btn-lg btn-block">{{ __('Set Your Password') }}</button>
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
