@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Set your Password </title>
@append
@section('scripts')
    <script type="application/javascript">
        var email = '';
        @if($email)
            email = '{{$email}}';
        @endif
    </script>
    {!! script_to('assets/js/auth/set_password_success.js') !!}
@append

@section('content')
    <div class="container">
        <div class="well">
            <p> Your password has been successfully established !!!</p>
            @if($redirect_uri)
                <p>Now you will be redirected to <a target="_self" id="redirect_url" name="redirect_url" href="{{$redirect_uri}}">{{$redirect_uri}}</a></p>
            @else
                <p> Now you can <a target="_self" href="{!!URL::action("UserController@getLogin") !!}">Complete your Profile</a> here.</p>
            @endif
        </div>
    </div>
@endsection
