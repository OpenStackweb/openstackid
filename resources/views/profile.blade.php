@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Account Settings</title>
@stop

@section('content')

    @include('menu')

    <div class="col-md-9 hide" id="sidebar">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12 col-xs-10">
                        Hello, {!! $user->fullname !!}
                        <div>Your OPENID: <a
                                    href="{!! str_replace("%23","#",$openid_url) !!}">{!! str_replace("%23","#",$openid_url) !!}</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <form id="user-form" name="user-form"
                              role="form"
                              autocomplete="off"
                              enctype="multipart/form-data"
                              method="post"
                              style="padding-bottom: 20px"
                              action='{!!URL::action("Api\\UserApiController@updateMe") !!}'>
                            @method('PUT')
                            @csrf
                            <legend><span class="glyphicon glyphicon-info-sign pointable" aria-hidden="true"
                                          title="this information will be public on your profile page"></span>&nbsp;{!! Config::get('app.app_name') !!} Account Settings:
                            </legend>

                            <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <span class="control-label col-md-2">
                                    <img src="{!! $user->pic !!}" class="img-circle" id="img-pic" title="Profile pic">
                                </span>
                                <input type="file" name="pic" id="pic"/>
                            </div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="first_name">First name</label>
                                <input autocomplete="off" class="form-control" type="text" name="first_name" id="first_name"
                                       value="{!! $user->first_name !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="last_name">Last name</label>
                                <input autocomplete="off" class="form-control" type="text" name="last_name" id="last_name"
                                       value="{!! $user->last_name !!}" data-lpignore="true">
                            </div>
                            <div class="clearfix"></div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="email">Email</label>
                                <input class="form-control" type="email" name="email" id="email"
                                       autocomplete="username"
                                       data-lpignore="true"
                                       value="{!! $user->email !!}">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="identifier">Identifier</label>
                                <input autocomplete="off" class="form-control" type="text" name="identifier" id="identifier"
                                       data-lpignore="true"
                                       value="{!! $user->identifier !!}">
                            </div>

                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="second_email">Second Email</label>
                                <input autocomplete="off" class="form-control" type="email" name="second_email" id="second_email"
                                       data-lpignore="true"
                                       value="{!! $user->second_email !!}">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="third_email">Third Email</label>
                                <input autocomplete="off" class="form-control" type="email" name="third_email" id="third_email"
                                       data-lpignore="true"
                                       value="{!! $user->third_email !!}">
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="birthday">Birthday</label>
                                <input class="form-control" type="text" name="birthday" id="birthday"
                                       data-lpignore="true"
                                       autocomplete="off"
                                       @if($user->birthday)
                                       value="{!! $user->birthday->format("m/d/Y") !!}"
                                       @endif
                                />
                            </div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="gender">Gender</label>
                                <select id="gender" class="form-control" name="gender">
                                    <option value="">--SELECT A GENDER --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Prefer not to say">Prefer not to say</option>
                                    <option value="Specify">Let me specify</option>
                                </select>
                                <input class="form-control hide" type="text"
                                       data-lpignore="true"
                                       name="gender_specify" id="gender_specify"
                                       placeholder="Specify your gender"
                                       autocomplete="off"
                                       value="{!! $user->gender_specify !!}">
                            </div>
                            <div class="clearfix"></div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                <label for="bio">Bio</label>
                                <textarea autocomplete="off" class="form-control" name="bio" id="bio" data-lpignore="true">{!! $user->bio !!}</textarea>
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                <label for="bio">Statement of interest</label>
                                <textarea autocomplete="off" class="form-control" name="statement_of_interest"
                                          id="statement_of_interest" data-lpignore="true">{!! $user->statement_of_interest !!}</textarea>
                            </div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-4 col-lg-4">
                                <label for="irc">IRC</label>
                                <input autocomplete="off" class="form-control" type="text" name="irc" id="irc" value="{!! $user->irc !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-4 col-lg-4">
                                <label for="github_user">Github user</label>
                                <input autocomplete="off" class="form-control" type="text" name="github_user" id="github_user"
                                       value="{!! $user->github_user !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-4 col-lg-4">
                                <label for="github_user">Twitter</label>
                                <input autocomplete="off" class="form-control" type="text" name="twitter_name" id="twitter_name"
                                       value="{!! $user->twitter_name !!}" data-lpignore="true">
                            </div>
                            <div class="clearfix"></div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="wechat_user">WEChat user</label>
                                <input autocomplete="off" class="form-control" type="text" name="wechat_user" id="wechat_user"
                                       value="{!! $user->wechat_user !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="linked_in_profile">LinkedIn Profile</label>
                                <input autocomplete="off" class="form-control" type="text" name="linked_in_profile" id="linked_in_profile"
                                       value="{!! $user->linked_in_profile !!}" data-lpignore="true">
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="address1">Address 1</label>
                                <input autocomplete="off" class="form-control" type="text" name="address1" id="address1"
                                       value="{!! $user->address1 !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="address2">Address 2</label>
                                <input autocomplete="off" class="form-control" type="text" name="address2" id="address2"
                                       value="{!! $user->address2 !!}" data-lpignore="true">
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="city">City</label>
                                <input autocomplete="off" class="form-control" type="text" name="city" id="city"
                                       value="{!! $user->city !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="state">State</label>
                                <input autocomplete="off" class="form-control" type="text" name="state" id="state"
                                       value="{!! $user->state !!}" data-lpignore="true">
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="post_code">Post Code</label>
                                <input autocomplete="off" class="form-control" type="text" name="post_code" id="post_code"
                                       value="{!! $user->post_code !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="country_iso_code">Country</label>
                                <select id="country_iso_code" class="form-control" name="country_iso_code"
                                        value="{!! $user->country_iso_code !!}" autofocus>
                                    <option value="">--SELECT A COUNTRY --</option>
                                    @foreach($countries as $country)
                                        <option value="{!! $country->getAlpha2() !!}">{!! $country->getName() !!}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="state">Company</label>
                                <input autocomplete="off" class="form-control" type="text" name="company" id="company"
                                       value="{!! $user->company !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="state">Job Title</label>
                                <input autocomplete="off" class="form-control" type="text" name="job_title" id="job_title"
                                       value="{!! $user->job_title !!}" data-lpignore="true">
                            </div>
                            <div class="clearfix"></div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="city">Phone</label>
                                <input autocomplete="off" class="form-control" type="text" name="phone_number" id="phone_number"
                                       value="{!! $user->phone_number !!}" data-lpignore="true">
                            </div>
                            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                                <label for="language">Language</label>
                                <select id="language" class="form-control" name="language"
                                        value="{!! $user->language !!}">
                                    <option value="">--SELECT A LANGUAGE --</option>
                                    @foreach($languages as $language)
                                        <option value="{!! $language->getAlpha2() !!}">{!! $language->getName() !!}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                <a target="_self" href="#" class="change-password-link">Change Password</a>
                            </div>
                            <div id="password_container">
                                <div class="form-group password-container col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                    <input type="password" class="form-control" id="current_password" name="current_password"
                                           autocomplete="new-password"
                                           placeholder="Current Password"
                                           data-lpignore="true"
                                    >
                                </div>

                                <div class="form-group password-container col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                    <input type="password" class="form-control" id="password" name="password"
                                           autocomplete="new-password"
                                           placeholder="Password"
                                           data-lpignore="true"
                                    >
                                </div>
                                <div class="form-group password-container col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                    <input type="password" class="form-control"
                                           id="password-confirm"
                                           autocomplete="new-password"
                                           name="password_confirmation"
                                           placeholder="Confirm Password"
                                           data-lpignore="true">
                                </div>
                            </div>
                            <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                <label>
                                    <input type="checkbox" id="public_profile_show_fullname" name="public_profile_show_fullname"
                                           @if($user->public_profile_show_fullname)
                                           checked
                                            @endif
                                    />&nbsp;Show Full name on Public Profile?
                                </label>
                            </div>
                            <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                <label>
                                    <input type="checkbox" id="public_profile_show_photo" name="public_profile_show_photo"
                                           @if($user->public_profile_show_photo)
                                           checked
                                            @endif
                                    />&nbsp;Show Pic on Public Profile?
                                </label>
                            </div>
                            <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                                <label>
                                    <input type="checkbox" id="public_profile_show_email" name="public_profile_show_email"
                                           @if($user->public_profile_show_email)
                                           checked
                                            @endif
                                    />&nbsp;Show Email on Public Profile?
                                </label>
                            </div>
                            <button type="submit" class="btn btn-default btn-lg btn-primary">Save</button>
                            <input type="hidden" name="id" id="id" value="{!! $user->id !!}"/>
                        </form>
                    </div>
                </div>
                @if (count($sites)>0)
                    <div class="row">
                        <div id="trusted_sites" class="col-md-12">
                            <legend><span class="glyphicon glyphicon-info-sign pointable" aria-hidden="true"
                                          title="Users can keep track of their trusted sites and manage them"></span>&nbsp;Trusted
                                Sites
                            </legend>
                            <div class="table-responsive">
                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>Realm</th>
                                        <th>Policy</th>
                                        <th>Trusted Data</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($sites as $site)
                                        @if($site->getAuthorizationPolicy()=='AllowForever')
                                            <tr class="success">
                                        @else
                                            <tr class="error">
                                                @endif
                                                <td width="50%">{!! $site->getRealm() !!}</td>
                                                <td width="10%">{!! $site->getAuthorizationPolicy()!!}</td>
                                                <td width="20%">{!! $site->getUITrustedData() !!}</td>
                                                <td width="10%">{!! HTML::link(URL::action("UserController@deleteTrustedSite",["id"=>$site->getId()]),'Delete',array('class'=>'btn btn-default btn-md active btn-delete del-realm','title'=>'Deletes a decision about a particular trusted site,', 'target'=>'_self')) !!}</td>
                                            </tr>
                                            @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if (count($actions)>0)
                    <div class="row">
                        <div id="actions" class="col-md-12">
                            <legend><span class="glyphicon glyphicon-info-sign pointable" aria-hidden="true"
                                          title="Users actions"></span>&nbsp;User Actions
                            </legend>
                            <div class="table-responsive">

                                <table class="table table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th>From Realm</th>
                                        <th>Action</th>
                                        <th>From IP</th>
                                        <th><span class="glyphicon glyphicon-info-sign pointable" aria-hidden="true"
                                                  title="Time is on UTC"></span>&nbsp;When
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($actions as $action)
                                        <tr>
                                            @if(!$action->hasRealm())
                                                <td>Site</td>
                                            @else
                                                <td>{!! $action->getRealm() !!}</td>
                                            @endif
                                            <td>{!! $action->getUserAction() !!}</td>
                                            <td>{!! $action->getFromIp() !!}</td>
                                            <td>{!! $action->getCreatedAt()->format("Y-m-d H:i:s") !!}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">&nbsp;
    </div>
@stop
@section('scripts')
    <script type="application/javascript">
        $(document).ready(function () {
            $('#profile', '#main-menu').addClass('active');
        });
    </script>
@stop

@section('scripts')
    <!-- include summernote css/js -->
    <link href="//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote.css" rel="stylesheet">
    <script src="//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote.js"></script>
    <script type="application/javascript">
        var current_language = '{!!$user->language!!}';
        var current_country  = '{!!$user->country_iso_code!!}';
        var current_gender   = '{!! $user->gender !!}';

    </script>
    {!! HTML::script('assets/simplemde/simplemde.min.js') !!}
    {!! HTML::style('assets/simplemde/simplemde.min.css') !!}
    {!! HTML::script('assets/pwstrength-bootstrap/pwstrength-bootstrap.js') !!}
    {!! HTML::style('assets/chosen-js/chosen.css') !!}
    {!! HTML::script('assets/chosen-js/chosen.jquery.js') !!}
    {!! HTML::script("assets/js/urlfragment.jquery.js") !!}
    {!! HTML::script("assets/moment/min/moment.min.js") !!}
    {!! HTML::script('assets/js/profile.js?v='.rand(1,1000)) !!}
@append