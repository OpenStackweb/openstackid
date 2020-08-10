@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get('app.app_name') }} - Sign Up </title>
@append
@section('scripts')
    {!! HTML::style('assets/chosen-js/chosen.css') !!}
    {!! HTML::style('assets/css/auth/register.css') !!}
    {!! HTML::script('assets/pwstrength-bootstrap/pwstrength-bootstrap.js') !!}
    {!! HTML::script('assets/chosen-js/chosen.jquery.js') !!}
    {!! HTML::script('assets/js/auth/registration.js') !!}
    <script type="application/javascript">
        var verifyCaptchaCallback = function(response) {
            $('#g_recaptcha_hidden').val(response);
            $('#g_recaptcha_hidden').valid();
        };
    </script>
@append

@section('content')
<div class="container">
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="col-xs-12 col-md-5 col-md-offset-3 signup-form">
        <form id="form-registration" method="POST" autocomplete="off" action="{{ URL::action('Auth\RegisterController@register') }}">
            @csrf
            <h2>Register</h2>
            <p class="hint-text">Create your account. It's free and only takes a minute.</p>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-6">
                        <input autocomplete="off" type="text" class="form-control" name="first_name" placeholder="First Name" required="required" data-lpignore="true"
                               @if($first_name)
                               value="{{$first_name}}"
                               @else
                               value="{{old('first_name')}}"
                               @endif
                        />
                    </div>
                    <div class="col-xs-6">
                        <input autocomplete="off" type="text" class="form-control" name="last_name" placeholder="Last Name" required="required" data-lpignore="true"
                               @if($last_name)
                               value="{{$last_name}}"
                               @else
                               value="{{old('last_name')}}"
                               @endif
                        />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required="required" autocomplete="username" data-lpignore="true"
                       @if($email)
                       value="{{$email}}"
                       @else
                       value="{{old('email')}}"
                       @endif
                />
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
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required="required" autocomplete="new-password" data-lpignore="true">
            </div>
            <div class="form-group password-container">
                <input type="password" class="form-control" id="password-confirm" name="password_confirmation" placeholder="Confirm Password" required="required" autocomplete="new-password" data-lpignore="true">
            </div>
            <div class="form-group">
                <div class="recaptcha-container" >
                    {!! Recaptcha::render(['id'=>'captcha', 'class'=>'input-block-level', 'callback'=>'verifyCaptchaCallback']) !!}
                    <input type="hidden"name="g_recaptcha_hidden" id="g_recaptcha_hidden">
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg btn-block">Register Now</button>
            </div>
            @if($redirect_uri)
                <input type="hidden" id="redirect_uri" name="redirect_uri" value="{{$redirect_uri}}"/>
            @endif
            @if($client_id)
                <input type="hidden" id="client_id" name="client_id" value="{{$client_id}}"/>
            @endif
        </form>
        <div class="text-center">Already have an account? <a href="{!! URL::action('UserController@getLogin') !!}">Sign in</a></div>
    </div>
</div>
@endsection
