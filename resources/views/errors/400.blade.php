@extends('layout')
@section('content')
    <h1>{!! Config::get('app.app_name') !!} - 400</h1>
    <div class="container">
        <p>
            400. That’s an error.
        </p>
        <p>
            <b>{!! $error !!}</b>
        </p>
        <p>
            {!! $error_description !!}
        </p>
    </div>
@stop