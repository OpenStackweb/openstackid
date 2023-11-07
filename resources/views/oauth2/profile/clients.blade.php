@extends('reactapp_layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - OAUTH2 Console - Clients</title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/clients.css') !!}
@append

@section('header_right')
    @if(Auth::check())
        <div class="row">
            <div class="col-md-6 col-md-offset-8">
                Welcome, <a target="_self"
                            href="{!! URL::action("UserController@getProfile") !!}">{!!Auth::user()->getIdentifier()!!}</a>
            </div>
        </div>
    @endif
@stop

@section('scripts')
    <script>
        const initialValues = {
            app_name: '',
            app_web_site_url: '',
            app_description: '',
            app_active: false,
            application_type: 'WEB_APPLICATION',
            app_admin_users: []
        }

        const menuConfig = {
            apiScopesAdminURL: '{!!URL::action("AdminController@listApiScopeGroups")!!}',
            bannedIPsAdminURL: '{!!URL::action("AdminController@listBannedIPs")!!}',
            groupsAdminURL: '{!!URL::action("AdminController@listGroups")!!}',
            lockedClientsAdminURL: '{!!URL::action("AdminController@listLockedClients")!!}',
            logoutURL: '{!!URL::action("UserController@logout")!!}',
            oauthAppsURL: '{!!URL::action("AdminController@listOAuth2Clients")!!}',
            oauthGrantsURL: '{!!URL::action("AdminController@editIssuedGrants")!!}',
            resourceServersAdminURL: '{!!URL::action("AdminController@listResourceServers")!!}',
            serverConfigURL: '{!!URL::action("AdminController@listServerConfig")!!}',
            serverPrivateKeysAdminURL: '{!!URL::action("AdminController@listServerPrivateKeys")!!}',
            settingURL: '{!! URL::action("UserController@getProfile") !!}',
            usersAdminURL: '{!!URL::action("AdminController@listUsers")!!}',
            helpMailto: '{!! Config::get("app.help_email") !!}',
            apiScopesAdminText: '{{ __("Api Scope Groups") }}',
            bannedIPsAdminText: '{{ __("Banned IPs") }}',
            groupsAdminText: '{{ __("Groups") }}',
            lockedClientsAdminText: '{{ __("Locked Clients") }}',
            oauthAdminSectionText: '{{ __("OAUTH2") }}',
            oauthAppsText: '{{ __('OAUTH2 Applications') }}',
            oauthConsoleText: '{{ __("OAUTH2 Console") }}',
            oauthGrantsText: '{{ __("Issued OAUTH2 Grants") }}',
            resourceServersAdminText: '{{ __("Resource Servers") }}',
            securitySectionText: '{{ __("Security") }}',
            serverAdminText: '{{ __("Server Administration") }}',
            serverConfigSectionText: '{{ __("Server") }}',
            serverConfigText: '{{ __("Server Configuration") }}',
            serverPrivateKeysAdminText: '{{ __("Private Keys") }}',
            settingsText: '{{ __('Settings') }}',
            usersAdminText: '{{ __("Users") }}',
            isOAuth2ServerAdmin: parseInt('{{ Auth::user()->isOAuth2ServerAdmin() }}') === 1 ? true : false,
            isOpenIdServerAdmin: parseInt('{{ Auth::user()->isOpenIdServerAdmin() }}') === 1 ? true : false,
            isSuperAdmin: parseInt('{{ Auth::user()->isSuperAdmin() }}') === 1 ? true : false
        }

        let config = {
            appName: '{!! Config::get('app.app_name') !!}',
            appDescription: '{!! Config::get('app.app_description') !!}',
            appLogo: '{{$app_logo ?? Config::get("app.logo_url")}}',
            csrfToken: document.head.querySelector('meta[name="csrf-token"]').content,
            editURL: '{{URL::action("AdminController@editRegisteredClient", array("id"=>"@id"))}}',
            fetchAdminUsersURL: '{{URL::action("Api\\UserApiController@getAll")}}',
            initialValues: initialValues,
            menuConfig: menuConfig,
            userName: '{{ Session::has('username') ? Session::get('username') : ""}}',
        }

        window.GET_CLIENTS_ENDPOINT = '{{URL::action("Api\\ClientApiController@getAll")}}';
        window.ADD_CLIENT_ENDPOINT = '{{URL::action("Api\\ClientApiController@create",null)}}';
        window.DELETE_CLIENT_ENDPOINT = '{{URL::action("Api\\ClientApiController@delete",array("id"=>"@id"))}}';
        window.ACTIVATE_CLIENT_ENDPOINT = '{{URL::action("Api\\ClientApiController@activate",array("id"=>"@id"))}}';
        window.DEACTIVATE_CLIENT_ENDPOINT = '{{URL::action("Api\\ClientApiController@deactivate",array("id"=>"@id"))}}';

    </script>
    {!! HTML::script('assets/clients.js') !!}
@append

