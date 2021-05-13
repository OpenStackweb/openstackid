<!DOCTYPE html>
<html lang="en">
<head>
    @yield('title')
    <base href="{!! Config::get('app.url') !!}" target="_self">
    <meta
            name="viewport"
            content="minimum-scale=1, initial-scale=1, width=device-width"
    />
    <link rel="shortcut icon" href="{!! Config::get('app.tenant_favicon') !!}" />
    @yield('meta')
    @yield('css')
    <!--https://material-ui.com/getting-started/installation/-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
    <style type="text/css">
        #logo a {
            background: url("{!!  Config::get('app.logo_url') !!}") no-repeat scroll left center rgba(0, 0, 0, 0);
        }
    </style>
</head>
<body>
<div id="root">
@yield('content')
</div>
@yield('scripts')
<span style="display: none">{!! Config::get('app.version') !!}</span>
</body>
</html>