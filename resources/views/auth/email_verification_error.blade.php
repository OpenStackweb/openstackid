@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Email Verification Error !!!</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <p> There had been an error on your email verification process. </p>
        <p> Please try again later.</p>
    </div>
@endsection
