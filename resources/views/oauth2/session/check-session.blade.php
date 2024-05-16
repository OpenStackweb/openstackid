@extends('layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Check Session Frame</title>
@stop
@section('content')
    <div class="container">
    </div>
@stop
@section('scripts')
    {!! script_to('assets/crypto-js/crypto-js.js')!!}
    {!! script_to('assets/jquery-cookie/jquery.cookie.js')!!}
    {!! script_to('assets/js/oauth2/session/check.session.js')!!}
@append