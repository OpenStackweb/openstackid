@extends('reactapp_layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Security - Edit User</title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    <link href="{{ asset('assets/css/editUser.css') }}" rel="stylesheet"></link>
@append
@section('scripts')
    <script>
        const initialValues = {!! $user !!};
        initialValues.password = '';
        initialValues.password_confirmation = '';

        if (initialValues.birthday) {
            const birthday = new Date(0);
            birthday.setUTCSeconds(initialValues.birthday);
            initialValues.birthday = birthday.toJSON().slice(0, 10)
        }

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
        countries.push({value: "{!! $country->getAlpha2() !!}", text: "{!! $country->getName() !!}"});
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
            fetchGroupsURL: '{!!URL::action("Api\GroupApiController@getAll")!!}',
            initialValues: initialValues,
            languages: languages,
            menuConfig: menuConfig,
            passwordPolicy: passwordPolicy,
            usersListURL: '{!! URL::action("AdminController@listUsers") !!}'
        }

        window.GET_USER_ACTIONS_ENDPOINT = '{{URL::action("Api\UserActionApiController@getActions")}}';
        window.SAVE_PROFILE_ENDPOINT = '{!!URL::action("Api\UserApiController@update",["id" => $user_id])!!}';
        window.SAVE_PIC_ENDPOINT = '{!!URL::action("Api\UserApiController@updatePic",["id" => $user_id])!!}';
    </script>
    <script type="text/javascript" src="{{ asset('assets/editUser.js') }}"></script>
@append