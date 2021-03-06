@extends('layout')
@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!}</title>
@stop
@section('meta')
<meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@user", ['identifier'=> $identifier ]) !!}" />
@append
@section('content')
<div class="container">
    @if(Auth::guest() || $another_user)
        @if( $show_fullname === 0 && $show_email===0 && $show_pic === 0)
        <p>This is an OpenID Identity page. This user has chosen not to display any information on this page.</p>
        @else
                @if( $show_fullname )
                <legend>{!! $username !!}</legend>
                @endif
                    @if( $show_pic && !empty($pic))
                        <div class="row">
                            <div class="col-md-4">
                                <img src="{!! $pic !!}" class="img-circle" id="img-pic" title="Profile pic">
                            </div>
                        </div>
                    @endif
                @if( $show_email )
                    <div class="row email-row">
                        <div class="col-md-4">
                            <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
                            <a target="_blank" href="mailto:{!! $email !!}">{!! $email !!}</a>
                        </div>
                    </div>
                @endif
        @endif
    @else
        <div class="row">
            <div class="col-md-12">
                Welcome, {!! $username !!}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <a target="_self" class="btn btn-default btn-sm active" href="{!! URL::action('UserController@getProfile') !!}">Manage account</a>
                <a target="_self" class="btn btn-default btn-sm active" href="{!! URL::action('UserController@logout') !!}">logout</a>
            </div>
        </div>
        @if( $show_fullname === 0 && $show_email===0 && $show_pic === 0)
        <p>
            This is your identity page. You are currently displaying no information on this page. You can display information such as your name, contact info and a photo.
        </p>
        @else
            @if( $show_fullname )
            <legend>{!! $username !!}</legend>
            @endif
                @if( $show_pic && !empty($pic))
                    <div class="row">
                        <div class="col-md-4">
                            <img src="{!! $pic !!}" class="img-circle" id="img-pic" title="Profile pic">
                        </div>
                    </div>
                @endif
            @if( $show_email )
            <div class="row email-row">
                <div class="col-md-4">
                    <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span>
                    <a href="mailto:{!! $email !!}">{!! $email !!}</a>
                </div>
            </div>
            @endif
        @endif
    @endif
</div>
@stop