@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Set your new password </title>
@append
@section('scripts')
    {!! HTML::style('assets/chosen-js/chosen.css') !!}
    {!! HTML::style('assets/css/auth/email.css') !!}
    {!! HTML::script('assets/chosen-js/chosen.jquery.js') !!}
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
                  target="_self"
                  action="{{ URL::action('Auth\PasswordSetController@setPassword') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <h2>Set your Password</h2>
                <p class="hint-text">You can set your password here.</p>
                <div class="form-group">
                    <input type="email" value="{{$email}}" class="form-control" id="email" name="email"
                           placeholder="Email" required="required" readonly="true" autocomplete="username">
                </div>
                <div class="form-group">
                    <input type="text" value="{{$first_name}}" class="form-control" id="first_name" name="first_name"
                           placeholder="First Name" required="required" autocomplete="none">
                </div>
                <div class="form-group">
                    <input type="text" value="{{$last_name}}" class="form-control" id="last_name" name="last_name"
                           placeholder="Last Name" required="required" autocomplete="none">
                </div>
                <div class="form-group">
                    <input type="text" value="{{$company}}" class="form-control" id="company" name="company"
                           placeholder="Company" required="required" autocomplete="none">
                </div>
                <div class="form-group">
                    <select id="country_iso_code" class="form-control{{ $errors->has('country_iso_code') ? ' is-invalid' : '' }}" name="country_iso_code" value="{{ old('country_iso_code') }}" required autofocus autocomplete="off" data-lpignore="true">
                        <option value="">--SELECT A COUNTRY --</option>
                        @foreach($countries as $country)
                            <option value="{!! $country->getAlpha2() !!}">{!! $country->getName() !!}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('country_iso_code'))
                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('country_iso_code') }}</strong>
                                    </span>
                    @endif

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
