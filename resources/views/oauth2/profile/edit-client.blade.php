@extends('reactapp_layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - OAUTH2 Console - Edit Client</title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! style_to('assets/css/editClient.css') !!}
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
        const entity = {!!$client!!};
        const scopes = {!!$scopes!!};
        const selectedScopes = {!!$selected_scopes!!};
        const supportedSigningAlgorithms = {!!$supportedSigningAlgorithms!!};
        const supportedKeyManagementAlgorithms = {!!$supportedKeyManagementAlgorithms!!};
        const supportedContentEncryptionAlgorithms = {!!$supportedContentEncryptionAlgorithms!!};
        const supportedTokenEndpointAuthMethods = {!!$supportedTokenEndpointAuthMethods!!};
        const supportedJSONWebKeyTypes = {!!$supportedJSONWebKeyTypes!!};

        const appTypes = {!!$app_types!!};
        const clientTypes = {!!$client_types!!};

        const initialValues = {
            ...entity,
            alg: 'none',
            kid: '',
            pem_content: '',
            type: '{!!\jwk\JSONWebKeyTypes::RSA!!}',
            usage: 'sig',
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
            appTypes: appTypes,
            clientTypes: clientTypes,
            fetchAdminUsersURL: '{{URL::action("Api\\UserApiController@getAll")}}',
            initialValues: initialValues,
            menuConfig: menuConfig,
            scopes: scopes,
            selectedScopes: selectedScopes,
            supportedContentEncryptionAlgorithms: supportedContentEncryptionAlgorithms,
            supportedKeyManagementAlgorithms: supportedKeyManagementAlgorithms,
            supportedSigningAlgorithms: supportedSigningAlgorithms,
            supportedTokenEndpointAuthMethods: supportedTokenEndpointAuthMethods,
            supportedJSONWebKeyTypes: supportedJSONWebKeyTypes,
            userName: '{{ Session::has('username') ? Session::get('username') : ""}}',
        }

        window.APP_TYPES = appTypes;
        window.CLIENT_TYPES = clientTypes;
        window.CSFR_TOKEN = document.head.querySelector('meta[name="csrf-token"]').content;

        window.UPDATE_CLIENT_DATA_ENDPOINT = '{!!URL::action("Api\ClientApiController@update",array("id"=>"@client_id"))!!}';

        window.ADD_CLIENT_SCOPE_ENDPOINT = '{!!URL::action("Api\ClientApiController@addAllowedScope",array("id"=>"@client_id","scope_id"=>"@scope_id"))!!}';
        window.REMOVE_CLIENT_SCOPE_ENDPOINT = '{!!URL::action("Api\ClientApiController@removeAllowedScope",array("id"=>"@client_id","scope_id"=>"@scope_id"))!!}';
        window.REGENERATE_CLIENT_SECRET_ENDPOINT = '{!!URL::action("Api\ClientApiController@regenerateClientSecret",array("id"=>"@client_id"))!!}';

        window.GET_ACCESS_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@getAccessTokens",array("id"=>"@client_id"))!!}';
        window.GET_REFRESH_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@getRefreshTokens",array("id"=>"@client_id"))!!}';
        window.REVOKE_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@revokeToken",array("id"=>"@client_id","value"=>"@value","hint"=>"@hint")) !!}';

        window.ADD_PUBLIC_KEY_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_create",array("id"=>"@client_id"))!!}';
        window.GET_PUBLIC_KEYS_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@getAll",array("id"=>"@client_id"))!!}';
        window.REMOVE_PUBLIC_KEY_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_delete",array("id"=>"@client_id", "public_key_id"=>"@public_key_id"))!!}';
    </script>
    {!! script_to('assets/editClient.js') !!}
@append
