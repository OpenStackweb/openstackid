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
</head>
<body>
    <div id="root" />
    @yield('scripts')
    <span style="display: none">{!! Config::get('app.version') !!}</span>
</body>
</html>