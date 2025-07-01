@extends('reactapp_layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Account Settings</title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! style_to('assets/css/profile.css') !!}
@append
@section('scripts')
    <script>
        let trustedSites = [];
        @foreach ($sites as $site)
        trustedSites.push({
            id: "{!! $site->getId() !!}",
            auth_policy: "{!! $site->getAuthorizationPolicy() !!}",
            realm: "{!! $site->getRealm() !!}",
            trusted_data: "{!! $site->getUITrustedData() !!}",
            deleteURL: "{!! URL::action("UserController@deleteTrustedSite",["id"=>$site->getId()]) !!}"
        });
        @endforeach

        const initialValues = {
            ...{!! $user !!},
            has_password_set: parseInt('{{ Auth::user()->hasPasswordSet() }}') === 1 ? true : false,
            password: '',
            password_confirmation: '',
            openid_url: "{!! str_replace("%23","#",$openid_url) !!}",
            trusted_sites: trustedSites,
        };

        if (initialValues.birthday) {
            const birthday = new Date(0);
            birthday.setUTCSeconds(initialValues.birthday);
            initialValues.birthday = birthday.toJSON().slice(0, 10)
        }

        delete initialValues.groups

        Object.keys(initialValues).map(
            (key) => (initialValues[key] === null) ? initialValues[key] = '' : initialValues[key]
        );

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

        const passwordPolicy = {
            min_length: {{ Config::get("auth.password_min_length") }},
            max_length: {{ Config::get("auth.password_max_length") }},
            shape_pattern: '{{ Config::get("auth.password_shape_pattern") }}',
            shape_warning: '{{ Config::get("auth.password_shape_warning") }}'
        }

        let countries = [];
        @foreach($countries as $country)
        countries.push({value: "{!! $country['alpha2'] !!}", text: "{!! $country['name'] !!}"});
        @endforeach

        let languages = [];
        @foreach($languages as $language)
        languages.push({value: "{!! $language->getAlpha2() !!}", text: "{!! $language->getName() !!}"});
        @endforeach

        let config = {
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            countries: countries,
            csrfToken: document.head.querySelector('meta[name="csrf-token"]').content,
            initialValues: initialValues,
            languages: languages,
            menuConfig: menuConfig,
            passwordPolicy: passwordPolicy
        }

        window.GET_USER_ACTIONS_ENDPOINT = '{{URL::action("Api\UserActionApiController@getActionsByCurrentUser")}}';
        window.GET_USER_ACCESS_TOKENS_ENDPOINT = '{{URL::action("Api\ClientApiController@getAccessTokensByCurrentUser")}}';
        window.REVOKE_ACCESS_TOKENS_ENDPOINT = '{!!URL::action("Api\UserApiController@revokeMyToken", ["value"=>"@value", "hint"=>"@hint"])!!}';
        window.SAVE_PROFILE_ENDPOINT = '{!!URL::action("Api\UserApiController@updateMe")!!}';
        window.SAVE_PIC_ENDPOINT = '{!!URL::action("Api\UserApiController@updateMyPic")!!}';
        window.CSFR_TOKEN = document.head.querySelector('meta[name="csrf-token"]').content;
    </script>
    {!! script_to('assets/profile.js') !!}
@append