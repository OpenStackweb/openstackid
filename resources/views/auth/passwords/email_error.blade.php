@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} -  Reset your Password Error</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> There was no user with that email address found.</p>
            <p>You can try again <a href="{!!URL::action("Auth\ForgotPasswordController@showLinkRequestForm") !!}">with a different email address</a> or <a href="{!! URL::action("Auth\RegisterController@showRegistrationForm")!!}">Create an {{ Config::get("app.app_name") }}</a>.</p>
        </div>
    </div>
@endsection
