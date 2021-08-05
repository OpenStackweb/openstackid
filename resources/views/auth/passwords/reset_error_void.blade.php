@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Reset your Password - Void Request</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> Your password reset link has expired!</p>
            <p> Please request a new one  <a target="_self" href="{!!URL::action("Auth\ForgotPasswordController@showLinkRequestForm", ['email' => $email]) !!}">here</a>.</p>
        </div>
    </div>
@endsection
