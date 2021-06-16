@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Email Verification Complete !!!</title>
@append
@section('scripts')
    {!! HTML::script('assets/js/auth/email-verification-complete.js') !!}
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <p> You successfully Verified Your Email Address !!!</p>
        <p> Now proceed to <a target="_self" href="{!! URL::action("UserController@getLogin") !!}">Login</a>.</p>
    </div>
@endsection
