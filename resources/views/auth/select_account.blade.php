@extends('auth_layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Select your Account </title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/select_account.css') !!}
@append
@section('content')

@append
@section('scripts')
    <script>
        let config = {
            token :  document.head.querySelector('meta[name="csrf-token"]').content,
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            removeFormerAccountAction : '{{URL::action("UserController@removeFormerAccount")}}',
            accounts:[
                    @foreach($accounts as $email => $account)
                        {
                            username: "{{$email}}",
                            full_name:"{{$account['user_fullname']}}",
                            pic:"{{$account['user_pic']}}"
                        },
                    @endforeach
            ],
        };

        window.REMOVE_FORMER_ACCOUNT_ENDPOINT = config.removeFormerAccountAction;

    </script>
    {!! HTML::script('assets/select_account.js') !!}
@append