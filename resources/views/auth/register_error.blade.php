@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Sign Up Error</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> There was an error on your registration process.</p>
            <p> Please try it later.</p>
            <p> Or go back to <a target="_self" href="{!!URL::action("Auth\RegisterController@showRegistrationForm") !!}">Registration</a>.</p>
        </div>
    </div>
@endsection
