<!DOCTYPE html>
<html lang="en">
<head>
    @yield('title')
    <base href="{!! Config::get('app.url') !!}" target="_blank">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{!! Config::get('app.tenant_favicon') !!}"/>
    @yield('meta')
    {!! style_to('assets/css/index.css') !!}
    {!! style_to('assets/css/main.css') !!}
    {!! style_to('assets/bootstrap-tagsinput/bootstrap-tagsinput-typeahead.css') !!}
    {!! style_to('assets/bootstrap-tagsinput/bootstrap-tagsinput.css') !!}
    {!! style_to('assets/sweetalert2/sweetalert2.css') !!}
    @yield('css')
</head>
<body>

<div class="container">
    <header class="row header">
        <div class="col-md-12">
            <p id="logo">
                <a href="/" target="_self"><img alt="{!! Config::get("app.app_name") !!}"
                                                src="{!!  Config::get('app.logo_url') !!}"></a>
            </p>
        </div>
    </header>
    <div class="row" id="main-content">
        @yield('content')
    </div>
    <footer class="row"></footer>
</div>
{!! script_to('assets/index.js') !!}
{!! script_to('assets/js/ajax.utils.js') !!}
{!! script_to('assets/js/jquery.cleanform.js') !!}
{!! script_to('assets/js/jquery.serialize.js') !!}
{!! script_to('assets/js/jquery.validate.additional.custom.methods.js') !!}
{!! script_to('assets/typeahead/typeahead.bundle.js') !!}
{!! script_to('assets/bootstrap-tagsinput/bootstrap-tagsinput.js') !!}
{!! script_to('assets/sweetalert2/sweetalert2.js') !!}
{!! script_to('assets/urijs/URI.min.js') !!}
{!! script_to('assets/js/jquery-ajax-loader.js') !!}
@yield('scripts')
<span class="version hidden">{!! Config::get('app.version') !!}</span>
</body>
</html>