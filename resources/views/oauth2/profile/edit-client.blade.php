@extends('reactapp_layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - OAUTH2 Console - Edit Client</title>
@append
@section('meta')
    <meta http-equiv="X-XRDS-Location" content="{!! URL::action("OpenId\DiscoveryController@idp") !!}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/editClient.css') !!}
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
        let adminUsers = [];
        let scopes = [];
        let selectedScopes = [];
        let supportedSigningAlgorithms = [];
        let supportedKeyManagementAlgorithms = [];
        let supportedContentEncryptionAlgorithms = [];
        let supportedTokenEndpointAuthMethods = [];
        let supportedJSONWebKeyTypes = [];

        const AppTypes = {
            JSClient: '{!! oauth2\models\IClient::ApplicationType_JS_Client !!}',
            Native: '{!! oauth2\models\IClient::ApplicationType_Native !!}',
            Service: '{!! oauth2\models\IClient::ApplicationType_Service !!}',
            WebApp: '{!! oauth2\models\IClient::ApplicationType_Web_App !!}'
        }

        const ClientTypes = {
            Public: '{!! OAuth2\Models\IClient::ClientType_Public !!}',
            Confidential: '{!! OAuth2\Models\IClient::ClientType_Confidential !!}'
        }

        @foreach($client->admin_users as $admin_user)
        adminUsers.push({
            'id': {!! $admin_user->id !!},
            'fullName': '{!! $admin_user->first_name !!} {!! $admin_user->last_name !!}'
        });
        @endforeach

        @foreach($scopes as $scope)
        scopes.push({
            'id': {!! $scope->id !!},
            'name': '{!! $scope->name !!}',
            'description': '{!! $scope->description !!}',
            'api_name': '{!! $scope->getApiName() !!}',
            'api_logo': '{!! $scope->getApiLogo() !!}',
            'api_description': '{!! $scope->getApiDescription() !!}',
        });
        @endforeach

        @foreach($selected_scopes as $selected_scope)
        selectedScopes.push({!! $selected_scope !!});
        @endforeach

        @foreach(OAuth2\OAuth2Protocol::getSigningAlgorithmsPerClientType($client) as $alg)
        supportedSigningAlgorithms.push('{!! $alg !!}');
        @endforeach

        @foreach(OAuth2\OAuth2Protocol::getKeyManagementAlgorithmsPerClientType($client) as $alg)
        supportedKeyManagementAlgorithms.push('{!! $alg !!}');
        @endforeach

        @foreach(OAuth2\OAuth2Protocol::$supported_content_encryption_algorithms as $alg)
        supportedContentEncryptionAlgorithms.push('{!! $alg !!}');
        @endforeach

        @foreach(OAuth2\OAuth2Protocol::getTokenEndpointAuthMethodsPerClientType($client) as $method)
        supportedTokenEndpointAuthMethods.push('{!! $method !!}');
        @endforeach

        @foreach(\jwk\JSONWebKeyTypes::$supported_keys as $type)
        supportedJSONWebKeyTypes.push('{!! $type !!}');
        @endforeach

        const initialValues = {
            active: true,
            admin_users: adminUsers,
            alg: 'none',
            allowed_origins: '{!!$client->allowed_origins!!}'.trim() === '' ? [] : '{!!$client->allowed_origins!!}'.split(','),
            app_active: false,
            app_description: '{!!$client->app_description!!}',
            app_name: '{!!$client->app_name!!}',
            application_type: '{!!$client->application_type!!}',
            client_id: '{!!$client->client_id!!}',
            client_secret: '{!!$client->client_secret!!}',
            contacts: '{!!$client->contacts!!}'.split(','),
            id_token_encrypted_content_alg: '{!!$client->id_token_encrypted_response_enc!!}'.trim(),
            id_token_encrypted_response_alg: '{!!$client->id_token_encrypted_response_alg!!}'.trim(),
            id_token_signed_response_alg: '{!!$client->id_token_signed_response_alg!!}'.trim(),
            jwks_uri: '{!!$client->jwks_uri!!}',
            kid: '',
            default_max_age: {!!$client->default_max_age!!},
            logo_uri: '{!!$client->logo_uri!!}',
            logout_session_required: Boolean({!!$client->logout_session_required!!}),
            logout_uri: '{!!$client->logout_uri!!}',
            logout_use_iframe: Boolean({!!$client->logout_use_iframe!!}),
            otp_enabled: Boolean({!!$client->otp_enabled!!}),
            otp_length: {!!$client->otp_length!!},
            otp_lifetime: {!!$client->otp_lifetime!!},
            pem_content: '',
            policy_uri: '{!!$client->policy_uri!!}',
            post_logout_redirect_uris: '{!!$client->post_logout_redirect_uris!!}'.trim() === '' ? [] : '{!!$client->post_logout_redirect_uris!!}'.split(','),
            redirect_uris: '{!!$client->redirect_uris!!}'.trim() === '' ? [] : '{!!$client->redirect_uris!!}'.split(','),
            rotate_refresh_token: '{!!$client->rotate_refresh_token!!}'.trim() !== '',
            subject_type: '{!!$client->subject_type!!}'.trim(),
            tos_uri: '{!!$client->tos_uri!!}',
            token_endpoint_auth_method: '{!!$client->token_endpoint_auth_method!!}'.trim(),
            token_endpoint_auth_signing_alg: '{!!$client->token_endpoint_auth_signing_alg!!}'.trim(),
            type: '{!!\jwk\JSONWebKeyTypes::RSA!!}',
            usage: 'sig',
            use_refresh_token: '{!!$client->use_refresh_token!!}'.trim() !== '',
            userinfo_encrypted_response_enc: '{!!$client->userinfo_encrypted_response_enc!!}'.trim(),
            userinfo_encrypted_response_alg: '{!!$client->userinfo_encrypted_response_alg!!}'.trim(),
            userinfo_signed_response_alg: '{!!$client->userinfo_signed_response_alg!!}'.trim(),
            website: '{!!$client->website!!}',
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
            appType: '{!!$client->application_type!!}',
            appTypes: AppTypes,
            canRequestRefreshTokens: Boolean({!!$client->canRequestRefreshTokens()!!}),
            clientId: '{!!$client->id!!}',
            clientName: '{!!$client->getFriendlyApplicationType()!!}',
            clientSecret: '{!!$client->client_secret!!}',
            clientType: '{!!$client->client_type!!}',
            clientTypes: ClientTypes,
            csrfToken: document.head.querySelector('meta[name="csrf-token"]').content,
            editorName: '{!!$client->getEditedByNice()!!}',
            fetchAdminUsersURL: '{{URL::action("Api\\UserApiController@getAll")}}',
            initialValues: initialValues,
            isOwner: {!!$client->isOwner(Auth::user())!!} === 1,
            isClientAllowedToUseTokenEndpointAuth: Boolean({!!OAuth2\OAuth2Protocol::isClientAllowedToUseTokenEndpointAuth($client)!!}),
            menuConfig: menuConfig,
            ownerName: '{!!$client->getOwnerNice()!!}',
            scopes: scopes,
            selectedScopes: selectedScopes,
            supportedContentEncryptionAlgorithms: supportedContentEncryptionAlgorithms,
            supportedKeyManagementAlgorithms: supportedKeyManagementAlgorithms,
            supportedSigningAlgorithms: supportedSigningAlgorithms,
            supportedTokenEndpointAuthMethods: supportedTokenEndpointAuthMethods,
            supportedJSONWebKeyTypes: supportedJSONWebKeyTypes,
            userName: '{{ Session::has('username') ? Session::get('username') : ""}}',
        }

        window.UPDATE_CLIENT_DATA_ENDPOINT = '{!!URL::action("Api\ClientApiController@update",array("id"=>"@client_id"))!!}';

        window.ADD_CLIENT_SCOPE_ENDPOINT = '{!!URL::action("Api\ClientApiController@addAllowedScope",array("id"=>"@client_id","scope_id"=>"@scope_id"))!!}';
        window.REMOVE_CLIENT_SCOPE_ENDPOINT = '{!!URL::action("Api\ClientApiController@removeAllowedScope",array("id"=>"@client_id","scope_id"=>"@scope_id"))!!}';
        window.REGENERATE_CLIENT_SECRET_ENDPOINT = '{!!URL::action("Api\ClientApiController@regenerateClientSecret",array("id"=>"@client_id"))!!}';

        window.GET_ACCESS_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@getAccessTokens",array("id"=>"@client_id"))!!}';
        window.GET_REFRESH_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@getRefreshTokens",array("id"=>"@client_id"))!!}';
        window.REVOKE_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@revokeToken",array("id"=>"@client_id","value"=>"@value","hint"=>"@hint")) !!}';

        window.ADD_PUBLIC_KEY_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_create",array("id"=>"@client_id"))!!}';
        window.GET_PUBLIC_KEYS_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_getAll",array("id"=>"@client_id"))!!}';
        window.REMOVE_PUBLIC_KEY_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_delete",array("id"=>"@client_id", "public_key_id"=>"@public_key_id"))!!}';
    </script>
    {!! HTML::script('assets/editClient.js') !!}
@append
