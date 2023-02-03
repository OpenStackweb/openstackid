@extends('reactapp_layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Request for Permission </title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/consent.css') !!}
@append

@section('header_right')
    @if(Auth::check())
        <div class="row">
            <div class="col-md-6 col-md-offset-8">
                Welcome, <a target="_self"
                            href="{!! URL::action("UserController@getProfile") !!}">{!!Auth::user()->getIdentifier()!!}</a>
            </div>
        </div>
    @endif
@stop

@section('scripts')
    <script>

        let scopes = [];
        let contactEmails = [];

        @foreach($requested_scopes as $scope)
        scopes.push('{!! $scope->getShortDescription() !!}');
        @endforeach

        @foreach($contact_emails as $contact_email)
        contactEmails.push('{!! $contact_email !!}');
        @endforeach

        const disclaimer = '<p class="privacy-policy">** <b>{!!$app_name!!}</b> Application and <b>{!! Config::get('app.tenant_name') !!}</b> will use this information in accordance with their respective <a target="_blank" href="{!!$tos_uri!!}">terms of service</a> and <a target="_blank" href="{!!$policy_uri!!}">privacy policies</a>.</p>';

        let config = {
            requestedScopes: scopes,
            csrfToken: document.head.querySelector('meta[name="csrf-token"]').content,
            userName: '{{ Session::has('username') ? Session::get('username') : ""}}',
            realm: '{{isset($identity_select) ? $realm : ""}}',
            appName: '{!! $app_name !!}',
            appDescription: '{!! $app_description !!}',
            appLogo: '{{$app_logo ?? Config::get("app.logo_url")}}',
            formAction: '{{ URL::action("UserController@postConsent") }}',
            contactEmails: contactEmails,
            redirectURL: '{!! $redirect_to !!}',
            disclaimer: disclaimer,
        }

    </script>
    {!! HTML::script('assets/consent.js') !!}
@append
