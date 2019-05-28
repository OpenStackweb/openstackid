@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Set your Password Error</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> There was an error on your password setting process.</p>
            <p> Please try it later.</p>
        </div>
    </div>
@endsection
