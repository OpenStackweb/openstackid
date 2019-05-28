@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Sign Complete !!!</title>
@append
@section('scripts')
    {!! HTML::script('assets/js/auth/registration_success.js') !!}
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> You successfully registered !!!</p>
            <p> You will be receiving a confirmation email shortly, please check your inbox.</p>
            @if($redirect_uri)
                <p>Now you will be redirected to <a id="redirect_url" name="redirect_url" href="{{$redirect_uri}}">{{$redirect_uri}}</a></p>
            @else
                <p> Meanwhile you could check <a href="{!! URL::action("UserController@getLogin") !!}">Login</a>.</p>
            @endif
        </div>
    </div>
@endsection
