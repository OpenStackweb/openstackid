<!DOCTYPE html>
<html lang="en">
<head>
    @yield('title')
    <base href="{!! Config::get('app.url') !!}" target="_blank">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{!! Config::get('app.tenant_favicon') !!}" />
    @yield('meta')
    {!! HTML::style('assets/css/index.css') !!}
    {!! HTML::style('assets/css/main.css') !!}
    {!! HTML::style('assets/bootstrap-tagsinput/bootstrap-tagsinput-typeahead.css') !!}
    {!! HTML::style('assets/bootstrap-tagsinput/bootstrap-tagsinput.css') !!}
    {!! HTML::style('assets/sweetalert2/sweetalert2.css') !!}
    @yield('css')
</head>
<body>

    <div class="container">
        <header class="row header">
            <div class="col-md-12">
                <p id="logo">
                    <a href="/" target="_self" ><img src="{!!  Config::get('app.logo_url') !!}"></a>
                </p>
            </div>
        </header>
        <div class="row" id="main-content">
            @yield('content')
        </div>
        <footer class="row"></footer>
    </div>
    {!! HTML::script('assets/index.js')!!}
    {!! HTML::script('assets/js/ajax.utils.js')!!}
    {!! HTML::script('assets/js/jquery.cleanform.js')!!}
    {!! HTML::script('assets/js/jquery.serialize.js')!!}
    {!! HTML::script('assets/js/jquery.validate.additional.custom.methods.js')!!}
    {!! HTML::script('assets/typeahead/typeahead.bundle.js')!!}
    {!! HTML::script('assets/bootstrap-tagsinput/bootstrap-tagsinput.js')!!}
    {!! HTML::script('assets/sweetalert2/sweetalert2.js')!!}
    {!! HTML::script('assets/urijs/URI.min.js')!!}
    {!! HTML::script('assets/js/jquery-ajax-loader.js')!!}
    @yield('scripts')
    <span class="version hidden">{!! Config::get('app.version') !!}</span>
</body>
</html>