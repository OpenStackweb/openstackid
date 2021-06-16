@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Reset your Password </title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> Your password has been successfully reset.</p>
            <p> Now you can <a target="_self" href="{!!URL::action("UserController@getLogin") !!}">Login</a> here.</p>
        </div>
    </div>
@endsection
