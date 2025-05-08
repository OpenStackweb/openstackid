@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Sign Complete !!!</title>
@append
@section('scripts')
    {!! script_to('assets/js/auth/registration_success.js') !!}
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <p class="text-center">Almost done!</p>
            <p class="text-center">Final step: You will receive an email shortly asking you to verify your email address
                by clicking a link.</p>
            <p class="text-center">Thank you for creating an {!! Config::get("app.app_name") !!}.</p>
            @if($redirect_uri)
                <p>Now you will be redirected to <a id="redirect_url" name="redirect_url"
                                                    href="{{$redirect_uri}}">{{$redirect_uri}}</a></p>
            @endif
        </div>
    </div>
@endsection
