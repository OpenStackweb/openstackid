@extends('reactapp_layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Security - Edit User</title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append
@section('css')
    {!! HTML::style('assets/css/editUser.css') !!}
@append
@section('scripts')
    <script>
        const current_groups = [];
        @foreach($user->getGroups() as $group)
        current_groups.push({"id": {!!$group->id!!}, "name": "{!!$group->name!!}"});
        @endforeach

        const initialValues = {
            address1: '{!! $user->address1 ?? '' !!}',
            address2: '{!! $user->address2 ?? '' !!}',
            bio: {!! json_encode($user->bio) !!},
            birthday: '{!! $user->birthday != null ? $user->birthday->format("Y-m-d") : '' !!}',
            city: '{!! $user->city ?? '' !!}',
            company: '{!! $user->company ?? '' !!}',
            country_iso_code: '{!! $user->country_iso_code ?? '' !!}',
            current_language: '{!!$user->language ?? '' !!}',
            email: '{!! $user->email ?? '' !!}',
            first_name: '{!! $user->first_name ?? '' !!}',
            full_name: '{!! $user->fullname ?? '' !!}',
            gender: '{!! $user->gender ?? '' !!}',
            gender_specify: '{!! $user->gender_specify ?? '' !!}',
            github_user: '{!! $user->github_user ?? '' !!}',
            groups: current_groups,
            has_password_set: parseInt('{{ Auth::user()->hasPasswordSet() }}') === 1 ? true : false,
            id: '{!! $user->id !!}',
            identifier: '{!! $user->identifier ?? '' !!}',
            irc: '{!! $user->irc ?? '' !!}',
            job_title: '{!! $user->job_title ?? '' !!}',
            language: '{!! $user->language ?? '' !!}',
            last_name: '{!! $user->last_name ?? '' !!}',
            linked_in_profile: '{!! $user->linked_in_profile ?? '' !!}',
            password: '',
            password_confirmation: '',
            phone_number: '{!! $user->phone_number ?? '' !!}',
            pic_url: '{!! $user->pic ?? '' !!}',
            post_code: '{!! $user->post_code ?? '' !!}',
            active: parseInt('{!! $user->active !!}') === 1 ? true : false,
            email_verified: parseInt('{!! $user->email_verified !!}') === 1 ? true : false,
            second_email: '{!! $user->second_email ?? '' !!}',
            spam_type: '{!! $user->spam_type ?? '' !!}',
            state: '{!! $user->state ?? '' !!}',
            statement_of_interest: {!! json_encode($user->statement_of_interest) !!},
            third_email: '{!! $user->third_email ?? '' !!}',
            twitter_name: '{!! $user->twitter_name ?? '' !!}',
            wechat_user: '{!! $user->wechat_user ?? '' !!}',
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

        const passwordPolicy = {
            min_length: {{ Config::get("auth.password_min_length") }},
            max_length: {{ Config::get("auth.password_max_length") }},
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
        window.SAVE_PROFILE_ENDPOINT = '{!!URL::action("Api\UserApiController@update",["id" => $user->id])!!}';
    </script>
    {!! HTML::script('assets/editUser.js') !!}
@append