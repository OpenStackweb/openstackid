@extends('layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!}</title>
@stop
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}" />
@append
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h1>{{ Config::get('app.app_name') }} Identity Provider</h1>
                <div class="panel">
                    <div class="panel-heading strong">Log in to {{ Config::get('app.tenant_name')   }}</div>
                    <div class="row" style="text-align: center;">
                        <div class="col-md-12">
                            <div class="row" style="padding-top: 5px;padding-bottom: 5px;">
                                <div class="col-md-12">
                                    <a target="_self" href="{!! URL::action("UserController@getLogin") !!}" class="btn btn-default btn-md active">Sign in to your account</a>
                                </div>
                            </div>
                            <div class="row" style="padding-top: 5px;padding-bottom: 5px;">
                                <div class="col-md-12">
                                    <a target="_self" href="{!! URL::action("Auth\RegisterController@showRegistrationForm")  !!}" class="btn btn-default btn-md active">Register for an {{ Config::get('app.app_name') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="margin-top-20"><span class="glyphicon glyphicon-info-sign pointable" aria-hidden="true"
                                                   title=""></span>&nbsp{!! Config::get("app.homepage_info") !!}</p>
                </div>
            </div>
        </div>
    </div>
@stop