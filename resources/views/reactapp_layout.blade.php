<!DOCTYPE html>
<html lang="en">
<head>
    @yield('title')
    <base href="{!! Config::get('app.url') !!}" target="_blank">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{!! Config::get('app.tenant_favicon') !!}" />
    @yield('meta')
    @yield('css')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
    <style type="text/css">
        #logo a {
            background: url("{!!  Config::get('app.logo_url') !!}") no-repeat scroll left center rgba(0, 0, 0, 0);
        }

        header {
            display: flex;
        }

        header div {
            margin-left: auto;
            margin-right: 5px;
        }
    </style>
</head>
<body>
<header>
    <div>
        @yield('header_right')
    </div>
</header>
<div id="root"/>
@yield('scripts')
<span style="display: none">{!! Config::get('app.version') !!}</span>
</body>
</html>