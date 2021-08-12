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
            <p> Thank you for creation an {!! Config::get("app.app_name") !!}. You will be receiving an email to verify your email address and
            then another one confirming you have successfully created your {!! Config::get("app.app_name") !!} after your email is verified.</p>
            @if($redirect_uri)
                <p>Now you will be redirected to <a id="redirect_url" name="redirect_url" href="{{$redirect_uri}}">{{$redirect_uri}}</a></p>
            @else
                <p> Meanwhile you could check <a href="{!! URL::action("UserController@getLogin") !!}">Login</a>.</p>
            @endif
        </div>
    </div>
@endsection
