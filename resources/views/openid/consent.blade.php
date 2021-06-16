@extends('layout')
@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!} - Consent </title>
@append

@section('header_right')
@if(Auth::check())
Welcome, <a target="_self" href="{!! URL::action("UserController@getProfile") !!}">{!!Auth::user()->getIdentifier()!!}</a>
@endif
@append

@section('content')
<div class="container">
    <h4>{!! Config::get('app.app_name') !!} - Openid verification</h4>
    {!! Form::open(array('url' => URL::action("UserController@postConsent"),'id'=>'authorization_form', 'method' => 'post',  "autocomplete" => "off", "target" => "_self")) !!}
    <legend>
        Sign in to <b>{!! $realm !!}</b> using your {{ Config::get('app.app_name') }}
    </legend>
    <p>A site identifying itself as <b>{!! $realm !!}</b></p>
    <p>has asked us for confirmation that <a href="{!! str_replace("%23","#",$openid_url) !!}" target='_blank'>{!! str_replace("%23","#",$openid_url) !!}</a> is your identity URL</p>
    @foreach ($views as $partial)
        @include($partial->getName(),$partial->getData())
    @endforeach
    <div class="form-group">
        <div class="radio">
            <label>
            {!! Form::radio('trust[]', 'AllowOnce','true',array('id'=>'allow_once','class'=>'input-block-level')) !!}
            Allow Once
            </label>
        </div>
        <div class="radio">
            <label>
            {!! Form::radio('trust[]', 'AllowForever','',array('id'=>'allow_forever','class'=>'input-block-level')) !!}
            Allow Forever
            </label>
         </div>
        <div class="radio">
            <label>
            {!! Form::radio('trust[]', 'DenyOnce','',array('id'=>'deny_once','class'=>'input-block-level')) !!}
            Deny Once
            </label>
        </div>
        <div class="radio">
            <label>
            {!! Form::radio('trust[]', 'DenyForever','',array('id'=>'deny_forever','class'=>'input-block-level')) !!}
            Deny Forever
            </label>
        </div>
    </div>
    {!! Form::submit('Ok',array("id" => "send_authorization", 'class' => 'btn btn-default btn-lg active btn-consent-action')) !!}
    {!! Form::button('Cancel',array('id' => 'cancel_authorization', 'class' => 'btn active btn-lg btn-danger cancel_authorization btn-consent-action')) !!}
    {!! Form::close() !!}
</div>
@append
@section('scripts')
{!! HTML::script('assets/js/openid/consent.js') !!}
@append