@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Social Login Error</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> There was an error on your social login process.</p>
            <p> Please <a href="{!! URL::action('UserController@getLogin') !!}">try it again.</a></p>
        </div>
    </div>
@endsection
