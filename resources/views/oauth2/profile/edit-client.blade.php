{{--@extends('layout')--}}

{{--@section('title')--}}
{{--<title>Welcome to {!! Config::get('app.app_name') !!} - OAUTH2 Console - Edit Client</title>--}}
{{--@stop--}}
{{--@section('css')--}}
{{--    {!! HTML::style('assets/css/edit-client.css') !!}--}}
{{--@append--}}
{{--@section('scripts')--}}
{{--    {!! HTML::script("assets/moment/min/moment.min.js") !!}--}}
{{--    <script type="application/javascript">--}}

{{--        var dataClientUrls =--}}
{{--        {--}}
{{--            refresh: '{!!URL::action("Api\\ClientApiController@setRefreshTokenClient",array("id"=>$client->id, "use_refresh_token" => "@use_refresh_token"))!!}',--}}
{{--            rotate: '{!!URL::action("Api\\ClientApiController@setRotateRefreshTokenPolicy",array("id"=>$client->id, 'rotate_refresh_token'=>'@rotate_refresh_token'))!!}',--}}
{{--            update: '{!!URL::action("Api\\ClientApiController@update",array("id"=>$client->id))!!}',--}}
{{--            add_public_key: '{!!URL::action("Api\\ClientPublicKeyApiController@_create",array("id"=>$client->id))!!}',--}}
{{--            get_public_keys: '{!!URL::action("Api\\ClientPublicKeyApiController@getAll",array("id"=>$client->id))!!}',--}}
{{--            delete_public_key: '{!!URL::action("Api\\ClientPublicKeyApiController@_delete",array("id" => $client->id, 'public_key_id'=> '@public_key_id'))!!}',--}}
{{--            update_public_key: '{!!URL::action("Api\\ClientPublicKeyApiController@_update",array("id" => $client->id, 'public_key_id'=> '@public_key_id'))!!}',--}}
{{--            fetchUsers: '{!!URL::action("Api\\UserApiController@getAll")!!}',--}}
{{--        };--}}

{{--        var oauth2_supported_algorithms =--}}
{{--        {--}}
{{--            sig_algorihtms:--}}
{{--            {--}}
{{--                mac: {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_signing_algorithms_hmac_sha2)!!},--}}
{{--                rsa: {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_signing_algorithms_rsa)!!}--}}
{{--            },--}}
{{--            key_management_algorihtms: {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_key_management_algorithms)!!},--}}
{{--            content_encryption_algorihtms:  {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_content_encryption_algorithms)!!}--}}
{{--        };--}}

{{--        var current_admin_users  = [];--}}
{{--        var is_mine = {!! $client->isOwner(Auth::user())? 1:0 !!}--}}

{{--        @foreach($client->getAdminUsers() as $user)--}}
{{--        current_admin_users.push({--}}
{{--            "id": {!!$user->getId()!!} ,--}}
{{--            "value": "{!! $user->getFullName() !!}",--}}
{{--            "first_name": "{!! $user->first_name !!}",--}}
{{--            "last_name": "{!! $user->last_name !!}"--}}
{{--        });--}}
{{--        @endforeach--}}

{{--        var scopes = ['openid'];--}}
{{--        @if ($client->use_refresh_token)--}}
{{--        scopes.push('offline_access');--}}
{{--        @endif--}}
{{--        @foreach ($scopes as $scope)--}}
{{--        @if ( in_array($scope->id, $selected_scopes))--}}
{{--            scopes.push('{!!trim($scope->name)!!}');--}}
{{--        @endif--}}
{{--        @endforeach--}}

{{--        $(document).ready(function () {--}}
{{--            $('.panel-collapse').collapse('hide');--}}
{{--            location.hash && $(location.hash + '.collapse').collapse('show');--}}

{{--            $(document).on('click', '.head-button', function(e){--}}
{{--                $('.panel-collapse').collapse('hide');--}}
{{--                window.location.hash = $(this).attr('href');--}}
{{--            });--}}

{{--            $(document).on('click', '.copy-scopes-button', function(e){--}}
{{--                // Copy the text inside the text field--}}

{{--                navigator.clipboard.writeText(scopes.join(' '));--}}
{{--                // Alert the copied text--}}
{{--                alert("Copied Scopes: " + scopes.join(' '));--}}
{{--            });--}}
{{--        });--}}
{{--    </script>--}}
{{--@append--}}
{{--@section('content')--}}
{{--@include('menu')--}}
{{--<legend>--}}
{{--    <span aria-hidden="true" class="glyphicon glyphicon-info-sign pointable"--}}
{{--          title="OAuth 2.0 allows users to share specific data with you (for example, contact lists) while keeping their usernames, passwords, and other information private.">--}}

{{--    </span>&nbsp;{!!$client->getFriendlyApplicationType()!!} - Client # {!! $client->id !!}--}}
{{--</legend>--}}
{{--<div class="row">--}}
{{--    <div style="padding-left:15px" class="col-md-2 clear-padding"><strong>Created By:&nbsp;</strong></div><div class="col-md-10 clear-padding">{!! $client->getOwnerNice() !!}</div>--}}
{{--</div>--}}
{{--<div class="row">--}}
{{--    <div style="padding-left:15px" class="col-md-2 clear-padding"><strong>Edited By</strong>:&nbsp;</div><div class="col-md-10 clear-padding">{!! $client->getEditedByNice() !!}</div>--}}
{{--</div>--}}
{{--@if($errors->any())--}}
{{--<div class="errors">--}}
{{--    <ul>--}}
{{--        @foreach($errors->all() as $error)--}}
{{--        <div class="alert alert-danger alert-dismissible" role="alert">--}}
{{--            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>--}}
{{--            {!! $error !!}--}}
{{--        </div>--}}
{{--        @endforeach--}}
{{--    </ul>--}}
{{--</div>--}}
{{--@endif--}}

{{--<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">--}}
{{--    <!-- main data -->--}}
{{--    <div class="panel panel-default" style="padding-bottom:0px">--}}
{{--        <div class="panel-heading" role="tab" id="main_data_heading" style="margin-bottom:0px">--}}
{{--            <h4 class="panel-title">--}}
{{--                <a target="_self" role="button" class="head-button" data-toggle="collapse" data-parent="#accordion" href="#main_data" aria-expanded="true" aria-controls="main_data">--}}
{{--                    OAuth 2.0 Client Data--}}
{{--                </a>--}}
{{--            </h4>--}}
{{--        </div>--}}
{{--        <div id="main_data" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="main_data_heading">--}}
{{--            <div class="panel-body">--}}
{{--                @include('oauth2.profile.edit-client-data', array('access_tokens' => $access_tokens, 'refresh_tokens' => $refresh_tokens,'client' => $client))--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <!-- scopes -->--}}
{{--    <div class="panel panel-default" style="padding-bottom:0px">--}}
{{--        <div class="panel-heading" role="tab" id="allowed_scopes_heading" style="margin-bottom:0px">--}}
{{--            <h4 class="panel-title">--}}
{{--                <a target="_self" class="collapsed head-button" role="button" data-toggle="collapse" data-parent="#accordion" href="#allowed_scopes" aria-expanded="false" aria-controls="allowed_scopes">--}}
{{--                    Application Allowed Scopes--}}
{{--                </a>--}}
{{--                <i title="Copy Allowed Scopes to Clipboard" class="fa fa-clipboard copy-scopes-button" aria-hidden="true"></i>--}}
{{--            </h4>--}}
{{--        </div>--}}
{{--        <div id="allowed_scopes" class="panel-collapse collapse" role="tabpanel" aria-labelledby="allowed_scopes_heading">--}}
{{--            <div class="panel-body">--}}
{{--                @include('oauth2.profile.edit-client-scopes',array('access_tokens' => $access_tokens, 'refresh_tokens' => $refresh_tokens,'client'=>$client) )--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <!-- grants -->--}}
{{--    <div class="panel panel-default" style="padding-bottom:0px">--}}
{{--        <div class="panel-heading" role="tab" id="grants_heading" style="margin-bottom:0px">--}}
{{--            <h4 class="panel-title">--}}
{{--                <a target="_self" class="collapsed head-button" role="button" data-toggle="collapse" data-parent="#accordion" href="#grants" aria-expanded="false" aria-controls="grants">--}}
{{--                    Application Grants--}}
{{--                </a>--}}
{{--            </h4>--}}
{{--        </div>--}}
{{--        <div id="grants" class="panel-collapse collapse" role="tabpanel" aria-labelledby="grants_heading">--}}
{{--            <div class="panel-body">--}}
{{--                @include('oauth2.profile.edit-client-tokens',array('access_tokens' => $access_tokens, 'refresh_tokens' => $refresh_tokens,'client'=>$client) )--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <!-- security settings -->--}}
{{--    <div class="panel panel-default" style="padding-bottom:0px">--}}
{{--        <div class="panel-heading" role="tab" id="security_heading" style="margin-bottom:0px">--}}
{{--            <h4 class="panel-title">--}}
{{--                <a target="_self" class="collapsed head-button" role="button" data-toggle="collapse" data-parent="#accordion" href="#security" aria-expanded="false" aria-controls="security">--}}
{{--                    Security Settings--}}
{{--                </a>--}}
{{--            </h4>--}}
{{--        </div>--}}
{{--        <div id="security" class="panel-collapse collapse" role="tabpanel" aria-labelledby="security_heading">--}}
{{--            <div class="panel-body">--}}
{{--                @include('oauth2.profile.edit-client-security-main-settings',array('client' => $client) )--}}
{{--                <hr/>--}}
{{--                @include('oauth2.profile.edit-client-public-keys',array('client' => $client) )--}}
{{--                <hr/>--}}
{{--                @include('oauth2.profile.edit-client-security-logout',array('client' => $client) )--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
{{--@stop--}}

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
            alg: 'none',
            allowed_origins: '{!!$client->allowed_origins!!}'.trim().split(','),
            app_active: false,
            app_admin_users: [{fullName: 'Test User', id: 256}],
            app_description: '{!!$client->app_description!!}',
            app_logo_url: '{!!$client->logo_uri!!}',
            app_name: '{!!$client->app_name!!}',
            app_policy_url: '{!!$client->policy_uri!!}',
            app_term_of_service_url: '{!!$client->tos_uri!!}',
            app_web_site_url: '{!!$client->website!!}',
            client_id: '{!!$client->client_id!!}',
            client_secret: '{!!$client->client_secret!!}',
            contact_emails: '{!!$client->contacts!!}'.split(','),
            id_token_encrypted_content_alg: 'none',
            id_token_encrypted_response_alg: 'none',
            id_token_signed_response_alg: 'none',
            jwks_uri: '',
            kid: '',
            default_max_age: {!!$client->default_max_age!!},
            logout_session_required: false,
            logout_uri: '',
            logout_use_iframe: false,
            otp_length: 6,
            otp_lifetime: 600,
            pem_content: '',
            post_logout_redirect_uris: '',
            redirect_uris: '{!!$client->redirect_uris!!}'.trim().split(','),
            rotate_refresh_token: '{!!$client->rotate_refresh_token!!}'.trim() !== '',
            subject_type: 'public',
            token_endpoint_auth_method: 'none',
            token_endpoint_auth_signing_alg: 'none',
            type: '{!!\jwk\JSONWebKeyTypes::RSA!!}',
            usage: 'sig',
            use_refresh_token: '{!!$client->use_refresh_token!!}'.trim() !== '',
            userinfo_encrypted_response_enc: 'none',
            userinfo_encrypted_response_alg: 'none',
            userinfo_signed_response_alg: 'none'
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
            canRequestRefreshTokens: {!!$client->canRequestRefreshTokens()!!},
            clientId: '{!!$client->id!!}',
            clientName: '{!!$client->getFriendlyApplicationType()!!}',
            clientSecret: '{!!$client->client_secret!!}',
            clientType: '{!!$client->client_type!!}',
            clientTypes: ClientTypes,
            csrfToken: document.head.querySelector('meta[name="csrf-token"]').content,
            editorName: '{!!$client->getEditedByNice()!!}',
            fetchAdminUsersURL: '{{URL::action("Api\\UserApiController@getAll")}}',
            initialValues: initialValues,
            isOwner: '{!!$client->isOwner(Auth::user())!!}',
            isClientAllowedToUseTokenEndpointAuth: {!!OAuth2\OAuth2Protocol::isClientAllowedToUseTokenEndpointAuth($client)!!},
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

        window.ADD_CLIENT_SCOPE_ENDPOINT = '{!!URL::action("Api\ClientApiController@addAllowedScope",array("id"=>"@client_id","scope_id"=>"@scope_id"))!!}';
        window.REMOVE_CLIENT_SCOPE_ENDPOINT = '{!!URL::action("Api\ClientApiController@removeAllowedScope",array("id"=>"@client_id","scope_id"=>"@scope_id"))!!}';
        window.REGENERATE_CLIENT_SECRET_ENDPOINT = '{!!URL::action("Api\ClientApiController@regenerateClientSecret",array("id"=>"@client_id"))!!}';

        window.GET_ACCESS_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@getAccessTokens",array("id"=>"@client_id"))!!}';
        window.REVOKE_ACCESS_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@revokeToken",array("id"=>"@client_id","value"=>-1,"hint"=>"access-token")) !!}';
        window.GET_REFRESH_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@getRefreshTokens",array("id"=>"@client_id"))!!}';
        window.REVOKE_REFRESH_TOKENS_ENDPOINT = '{!! URL::action("Api\ClientApiController@revokeToken",array("id"=>"@client_id","value"=>-1,"hint"=>"refresh-token")) !!}';

        window.ADD_PUBLIC_KEY_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_create",array("id"=>"@client_id"))!!}';
        window.GET_PUBLIC_KEYS_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@getAll",array("id"=>"@client_id"))!!}';
        window.REMOVE_PUBLIC_KEY_ENDPOINT = '{!!URL::action("Api\ClientPublicKeyApiController@_delete",array("id"=>"@client_id", "public_key_id"=>"@public_key_id"))!!}';
    </script>
    {!! HTML::script('assets/editClient.js') !!}
@append
