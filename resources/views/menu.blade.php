<nav class="navbar navbar-default navbar-static-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">
                    {{ __('Toggle navigation') }}
                </span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#"></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul id='main-menu' class="nav navbar-nav">
                <li id="profile"><a href='{!! URL::action("UserController@getProfile") !!}'>{{ __('Settings') }}</a></li>
                <li id="oauth2-console" class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            {{ __('OAUTH2 Console') }}<b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href='{!!URL::action("AdminController@listOAuth2Clients")!!}'>{{ __('OAUTH2 Applications') }}</a></li>
                            <li><a href='{!!URL::action("AdminController@editIssuedGrants")!!}'>{{ __('Issued OAUTH2 Grants') }}</a></li>
                        </ul>
               </li>
                @if(Auth::user()->isOpenIdServerAdmin() || Auth::user()->isOAuth2ServerAdmin() || Auth::user()->isSuperAdmin())
                    <li id='server-admin' class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            {{ __('Server Administration') }}
                            <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            @if(Auth::user()->isSuperAdmin() || Auth::user()->isOpenIdServerAdmin())
                                <li class="dropdown-header">{{ __('Security') }}</li>
                                @if(Auth::user()->isSuperAdmin())
                                <li><a href='{!!URL::action("AdminController@listUsers")!!}'>{{ __('Users') }}</a></li>
                                <li><a href='{!!URL::action("AdminController@listGroups")!!}'>{{ __('Groups') }}</a></li>
                                @endif
                                <li><a href='{!!URL::action("AdminController@listBannedIPs")!!}'>{{ __('Banned IPs') }}</a></li>
                                <li role="separator" class="divider"></li>
                            @endif
                            @if(Auth::user()->isOAuth2ServerAdmin())
                                <li class="dropdown-header">{{ __('OAUTH2') }}</li>
                                <li><a href='{!!URL::action("AdminController@listServerPrivateKeys")!!}'>{{ __('Private Keys') }}</a></li>
                                <li><a href='{!!URL::action("AdminController@listResourceServers")!!}'>{{ __('Resource Servers') }}</a></li>
                                <li><a href='{!!URL::action("AdminController@listApiScopeGroups")!!}'>{{ __('Api Scope Groups') }}</a></li>
                                <li><a href='{!!URL::action("AdminController@listLockedClients")!!}'>{{ __('Locked Clients') }}</a></li>
                                <li role="separator" class="divider"></li>
                            @endif
                            @if(Auth::user()->isOpenIdServerAdmin())
                                <li class="dropdown-header">{{ __('Server') }}</li>
                                <li><a href='{!!URL::action("AdminController@listServerConfig")!!}'>{{ __('Server Configuration') }}</a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                <li><a title="help" target="_blank" href="mailto:{!! Config::get("app.help_email") !!}">Help</a></li>
                <li><a href='{!! URL::action("UserController@logout") !!}'>Logout</a></li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>