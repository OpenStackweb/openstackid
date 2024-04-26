<!DOCTYPE html>
<html lang="en">
<head>
    @yield('title')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{!! Config::get('app.tenant_favicon') !!}"/>
    @yield('meta')
    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bootstrap-tagsinput/bootstrap-tagsinput-typeahead.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/bootstrap-tagsinput/bootstrap-tagsinput.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/sweetalert2/sweetalert2.css') }}" rel="stylesheet">
    @yield('css')
    <script type="text/javascript" src="{{ asset('assets/__common__.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/index.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/ajax.utils.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery.cleanform.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery.serialize.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery.validate.additional.custom.methods.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/typeahead/typeahead.bundle.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/bootstrap-tagsinput/bootstrap-tagsinput.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/sweetalert2/sweetalert2.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/urijs/URI.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/jquery-ajax-loader.js') }}"></script>
    @yield('scripts')
</head>
<body>
<div class="container">
    <header class="row header">
        <div class="col-md-5">
            <p id="logo">
                <a href="/" target="_self"><img alt="{!! Config::get("app.app_name") !!}"
                                                src="{!!  Config::get('app.logo_url') !!}"></a>
            </p>
        </div>
            <div class="col-md-7">
                @yield('header_right')
            </div>
        </header>
        <div class="row" id="main-content">
            @yield('content')
        </div>
        <footer class="row"></footer>
    </div>
    <span class="version hidden">{!! Config::get('app.version') !!}</span>
</body>
</html>