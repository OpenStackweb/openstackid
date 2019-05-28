@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Email Verification Resend Complete !!!</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <p> You successfully requested a new email verification link.</p>
        <p> Please check your inbox and verify your email address.</p>
    </div>
@endsection
