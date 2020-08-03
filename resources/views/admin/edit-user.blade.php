@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Security - Edit User</title>
@stop

@section('content')
    @include('menu')
    <div class="row">
        <div class="col-xs-12">
            <a href='{!! URL::action("AdminController@listUsers") !!}'><i class="fa fa-chevron-circle-left"></i> Go Back</a>
        </div>
    </div>
    <div class="row">
        <form id="user-form" name="user-form"
              role="form"
              style="padding-top: 20px"
              autocomplete="off"
              enctype="multipart/form-data"
              method="post"
              action='{!!URL::action("Api\\UserApiController@update",["id" => $user->id])!!}'>
              @method('PUT')
              @csrf
            <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <span class="control-label col-md-2">
                                    <img src="{!! $user->pic !!}" class="img-circle" id="img-pic" title="Profile pic">
                                </span>
                <input type="file" name="pic" id="pic"/>
            </div>

            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="first_name">First name</label>
                    <input  autocomplete="off" class="form-control" type="text" name="first_name" id="first_name" value="{!! $user->first_name !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="last_name">Last name</label>
                    <input autocomplete="off" class="form-control" type="text" name="last_name" id="last_name" value="{!! $user->last_name !!}" data-lpignore="true">
                </div>
                <div class="clearfix"></div>

                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="email">Email</label>
                    <input  autocomplete="off" class="form-control" type="email" name="email" id="email" value="{!! $user->email !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="second_email">Second Email</label>
                    <input autocomplete="off" class="form-control" type="email" name="second_email" id="second_email" value="{!! $user->second_email !!}" data-lpignore="true">
                </div>
                <div class="clearfix"></div>

                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="email">Third Email</label>
                    <input autocomplete="off" class="form-control" type="email" name="third_email" id="third_email" value="{!! $user->third_email !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="gender">Gender</label>
                    <select id="gender" class="form-control" name="gender" data-lpignore="true">
                        <option value="">--SELECT A GENDER --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                        <option value="Specify">Let me specify</option>
                    </select>
                    <input class="form-control hide" type="text"
                           name="gender_specify" id="gender_specify"
                           placeholder="Specify your gender"
                           value="{!! $user->gender_specify !!}" data-lpignore="true">
                </div>
                <div class="clearfix"></div>

                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="identifier">Identifier</label>
                    <input autocomplete="off"  class="form-control" type="text" name="identifier" id="identifier" value="{!! $user->identifier !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="birthday">Birthday</label>
                    <input class="form-control" type="text" name="birthday" id="birthday" autocomplete="off" data-lpignore="true"
                           @if($user->birthday)
                           value="{!! $user->birthday->format("m/d/Y") !!}"
                           @endif
                    />
                </div>
                <div class="clearfix"></div>

                <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <label for="bio">Bio</label>
                    <textarea class="form-control"  autocomplete="off" name="bio" id="bio" data-lpignore="true">{!! $user->bio !!}</textarea>
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <label for="bio">Statement of interest</label>
                    <textarea class="form-control" autocomplete="off" name="statement_of_interest" id="statement_of_interest" data-lpignore="true">{!! $user->statement_of_interest !!}</textarea>
                </div>

                <div class="form-group col-xs-10 col-sm-4 col-md-4 col-lg-4">
                    <label for="irc">IRC</label>
                    <input class="form-control" autocomplete="off" type="text" name="irc" id="irc" value="{!! $user->irc !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-4 col-lg-4">
                    <label for="github_user">Github user</label>
                    <input class="form-control" autocomplete="off" type="text" name="github_user" id="github_user" value="{!! $user->github_user !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-4 col-lg-4">
                    <label for="github_user">Twitter</label>
                    <input autocomplete="off" class="form-control" type="text" name="twitter_name" id="twitter_name"
                           value="{!! $user->twitter_name !!}">
                </div>
                <div class="clearfix"></div>

                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="wechat_user">WEChat user</label>
                    <input class="form-control" autocomplete="off" type="text" name="wechat_user" id="wechat_user" value="{!! $user->wechat_user !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="linked_in_profile">LinkedIn Profile</label>
                    <input class="form-control" autocomplete="off" type="text" name="linked_in_profile" id="linked_in_profile" value="{!! $user->linked_in_profile !!}" data-lpignore="true">
                </div>
                <div class="clearfix"></div>
                <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <label class="control-label" for="groups">Groups&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
                    <input type="text" autocomplete="off" class="form-control" name="groups" id="groups" value="" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="address1">Address 1</label>
                    <input class="form-control" autocomplete="off" type="text" name="address1" id="address1" value="{!! $user->address1 !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="address2">Address 2</label>
                    <input class="form-control" autocomplete="off" type="text" name="address2" id="address2" value="{!! $user->address2 !!}" data-lpignore="true">
                </div>
                <div class="clearfix"></div>

                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="city">City</label>
                    <input class="form-control" autocomplete="off" type="text" name="city" id="city" value="{!! $user->city !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="state">State</label>
                    <input class="form-control" autocomplete="off" type="text" name="state" id="state" value="{!! $user->state !!}" data-lpignore="true">
                </div>
                <div class="clearfix"></div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="post_code">Post Code</label>
                    <input class="form-control" autocomplete="off" type="text" name="post_code" id="post_code" value="{!! $user->post_code !!}" data-lpignore="true">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="country_iso_code">Country</label>
                    <select id="country_iso_code" class="form-control" name="country_iso_code" value="{!! $user->country_iso_code !!}" autofocus data-lpignore="true" autocomplete="off">
                        <option value="">--SELECT A COUNTRY --</option>
                        @foreach($countries as $country)
                            <option value="{!! $country->getAlpha2() !!}">{!! $country->getName() !!}</option>
                        @endforeach
                    </select>
                </div>
                <div class="clearfix"></div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="city">Phone</label>
                    <input autocomplete="off" class="form-control" type="text" name="phone_number" id="phone_number"
                           value="{!! $user->phone_number !!}">
                </div>
                <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                    <label for="state">Company</label>
                    <input autocomplete="off" class="form-control" type="text" name="company" id="company"
                           value="{!! $user->company !!}">
                </div>
                <div class="clearfix"></div>
                <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <label for="language">Language</label>
                    <select id="language" class="form-control" name="language" value="{!! $user->language !!}" data-lpignore="true" autocomplete="off">
                        <option value="">--SELECT A LANGUAGE --</option>
                        @foreach($languages as $language)
                            <option value="{!! $language->getAlpha2() !!}">{!! $language->getName() !!}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <a href="#" class="change-password-link">Change Password</a>
                </div>
                <div id="password_container">
                    <div class="form-group password-container col-xs-10 col-sm-4 col-md-12 col-lg-12">
                        <input type="password" class="form-control" id="password" autocomplete="new-password" name="password" placeholder="Password" data-lpignore="true">
                    </div>
                    <div class="form-group password-container col-xs-10 col-sm-4 col-md-12 col-lg-12">
                        <input type="password" class="form-control" id="password-confirm" autocomplete="new-password" name="password_confirmation" placeholder="Confirm Password" data-lpignore="true">
                    </div>
                </div>
                <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <label>
                        <input type="checkbox" id="active" name="active"
                        @if($user->active)
                        checked
                        @endif
                        />&nbsp;Is Active?
                    </label>
                </div>
                <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                    <label>
                        <input type="checkbox" id="email_verified" name="email_verified"
                               @if($user->email_verified)
                               checked
                               @endif
                        />&nbsp;Email Verified?
                    </label>
                </div>
                <div class="col-xs-10 col-sm-4 col-md-12 col-lg-12" style="padding-bottom: 20px">
                    <label for="spam-type">Spam Type</label>
                    <input type="text" readonly class="form-control" id="spam-type" name="spam-type" data-lpignore="true" value="{!! $user->spam_type !!}">
                </div>
                <button style="margin-left: 15px;" type="submit" class="btn btn-default btn-lg btn-primary">Save</button>
                <input type="hidden" name="id" id="id" value="{!! $user->id !!}"/>
            </form>
    </div>
@stop
@section('scripts')
    <!-- include summernote css/js -->
    <link href="//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote.css" rel="stylesheet">
    <script src="//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote.js"></script>

    <script type="application/javascript">

        var urls = {
            fetchGroups: '{!!URL::action("Api\GroupApiController@getAll")!!}',
        };

        var current_language = '{!!$user->language!!}';
        var current_country  = '{!!$user->country_iso_code!!}';
        var current_gender   = '{!!$user->gender !!}';

        var current_groups = [];
        @foreach($user->getGroups() as $group)
        current_groups.push({ "id": {!!$group->id!!} , "name": "{!!$group->name!!}" });
        @endforeach

    </script>
    {!! HTML::script('assets/pwstrength-bootstrap/pwstrength-bootstrap.js') !!}
    {!! HTML::style('assets/chosen-js/chosen.css') !!}
    {!! HTML::script('assets/chosen-js/chosen.jquery.js') !!}
    {!! HTML::script("assets/js/urlfragment.jquery.js") !!}
    {!! HTML::script("assets/moment/min/moment.min.js") !!}
    {!! HTML::script('assets/js/admin/edit-user.js') !!}
@append