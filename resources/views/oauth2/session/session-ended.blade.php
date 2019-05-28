@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Logout</title>
@stop

@section('content')
    <div class="container">
        <p>Your Session at {!! Config::get('app.app_name') !!} had ended!</p>
    </div>
@stop
@section('scripts')
    {!! HTML::script('assets/crypto-js/crypto-js.js')!!}
    {!! HTML::script('assets/jquery-cookie/jquery.cookie.js')!!}
@append