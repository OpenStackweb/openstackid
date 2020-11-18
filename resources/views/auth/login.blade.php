@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Sign in </title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
@append
@section('css')
    {!! HTML::style('assets/css/auth/login.css') !!}
@append
@section('content')
    @if(isset($identity_select))
        <legend style="margin-left: 15px;">
            @if(!$identity_select)
                Sign in to <b>{!! $realm !!}</b> using <b>{!! $identity !!}</b>
            @else
                Sign in to <b>{!! $realm !!} </b> using your {{ Config::get("app.app_name") }}
            @endif
        </legend>
    @endif

    <div id="cookies-disabled-dialog" class="alert alert-warning alert-dismissible" style="display: none;" role="alert">
        <button type="button" class="close" onclick="$('#cookies-disabled-dialog').hide()" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        <strong>Warning!</strong> Cookies are not enabled, please enabled them in order to use {{ Config::get("app.app_name") }}.
    </div>

    <div class="col-md-4" id="sidebar">
        <div class="well">
            {!! Form::open(array('id'=>'login_form','url' => URL::action('UserController@postLogin'), 'method' => 'post',  "autocomplete" => "off")) !!}
            <legend>
                Welcome&nbsp;to&nbsp;{{ Config::get("app.app_name") }}!&nbsp;<span aria-hidden="true" style="font-size: 10pt;"
                                                                 class="glyphicon glyphicon-info-sign pointable"
                                                                 title="Please use your {{ Config::get("app.app_name") }} to log in"></span>
            </legend>
            @if(Config::get("app.app_info"))
                <p class="help-block">
                    {{Config::get("app.app_info")}}
                </p>
            @endif
            <div class="form-group">
                {!! Form::email('username',Session::has('username')? Session::get('username'):null, array
                (
                    'placeholder'  => 'Username',
                    'class'        =>'form-control',
                    'required'     => 'true',
                    'autocomplete' => 'username'
                )) !!}
            </div>
            <div class="form-group">
                <input placeholder="Password" class="form-control" required="true" autocomplete="current-password" name="password" id="password" type="password" value="">
                <span toggle="#password" class="fa fa-fw fa-eye fa-eye-slash field-icon toggle-password" title="Show Password"/>
            </div>
            <div class="form-group">
                @if(Session::has('flash_notice'))
                    <span class="error-message"><i
                                class="fa fa-exclamation-triangle">&nbsp;{!! Session::get('flash_notice') !!}</i></span>
                @else
                    @foreach($errors->all() as $message)
                        <span class="error-message"><i
                                    class="fa fa-exclamation-triangle">&nbsp;{!! $message !!}</i></span>
                    @endforeach
                @endif
            </div>
            @if(Session::has('login_attempts') && Session::has('max_login_attempts_2_show_captcha') && Session::get('login_attempts') > Session::get('max_login_attempts_2_show_captcha'))
                {!! Recaptcha::render(array('id'=>'captcha','class'=>'input-block-level')) !!}
                {!! Form::hidden('login_attempts', Session::get('login_attempts')) !!}
            @else
                {!! Form::hidden('login_attempts', '0') !!}
            @endif

            <div class="checkbox">
                <label class="checkbox">
                    {!! Form::checkbox('remember', '1', false) !!}Remember me
                </label>
            </div>
            <div class="pull-right">
                {!! Form::submit('Sign In',array('id'=>'login','class'=>'btn btn-primary')) !!}
                <a class="btn btn-primary" href="{!! URL::action('UserController@cancelLogin') !!} ">Cancel</a>
            </div>
            <div style="clear:both;padding-top:15px;" class="row">
                <div class="col-md-12">
                    <a title="register new account" target="_blank"
                       href="{!! URL::action("Auth\RegisterController@showRegistrationForm") !!}">Create an
                        {!! Config::get("app.app_name") !!} </a>
                </div>
            </div>
            <div style="clear:both;padding-top:15px;" class="row">
                <div class="col-md-12">
                    <a title="forgot password" target="_blank"
                       href="{!!  URL::action("Auth\ForgotPasswordController@showLinkRequestForm") !!}">Forgot password?</a>
                </div>
            </div>
            <div style="clear:both;padding-top:15px;" class="row">
                <div class="col-md-12">
                    <a title="verify account" target="_blank" href="{!! URL::action("Auth\EmailVerificationController@showVerificationForm") !!}">Verify
                        {!! Config::get("app.app_name") !!}</a>
                </div>
            </div>
            <div style="clear:both;padding-top:15px;" class="row">
                <div class="col-md-12">
                    <a title="help" target="_blank" href="mailto:{!! Config::get("app.help_email") !!}">Help</a>
                </div>
            </div>
            </fieldset>
            {!! Form::close() !!}
        </div>
    </div>
    <div class="col-md-8">
    </div>
@append
@section('scripts')
    {!! HTML::script('assets/js/login.js') !!}
@append