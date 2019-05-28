@extends('layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Check Session Frame</title>
@stop
@section('content')
    <div class="container">
    </div>
@stop
@section('scripts')
    {!! HTML::script('assets/crypto-js/crypto-js.js')!!}
    {!! HTML::script('assets/jquery-cookie/jquery.cookie.js')!!}
    {!! HTML::script('assets/js/oauth2/session/check.session.js')!!}
@append