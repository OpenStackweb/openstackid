@extends('reactapp_layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!}</title>
@stop
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
@append
@section('css')
    <link href="{{ asset('assets/css/home.css') }}" rel="stylesheet">
@append

@section('scripts')
    <script>
        let config = {
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true : false,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            tenantName: '{{ Config::get("app.tenant_name") }}',
            info: "{!! html_entity_decode(Config::get("app.homepage_info")) !!}",
            signInUrl: '{!! URL::action("UserController@getLogin") !!}',
            signUpUrl: '{!! URL::action("Auth\RegisterController@showRegistrationForm") !!}',
        }
    </script>
    <script type="text/javascript" src="{{ asset('assets/home.js') }}"></script>
@append