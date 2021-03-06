@extends('layout')

@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!} - OAUTH2 Console - Edit Client</title>
@stop
@section('css')
    {!! HTML::style('assets/css/edit-client.css') !!}
@append
@section('scripts')
    {!! HTML::script("assets/moment/min/moment.min.js") !!}
    <script type="application/javascript">

        var dataClientUrls =
        {
            refresh: '{!!URL::action("Api\\ClientApiController@setRefreshTokenClient",array("id"=>$client->id, "use_refresh_token" => "@use_refresh_token"))!!}',
            rotate: '{!!URL::action("Api\\ClientApiController@setRotateRefreshTokenPolicy",array("id"=>$client->id, 'rotate_refresh_token'=>'@rotate_refresh_token'))!!}',
            update: '{!!URL::action("Api\\ClientApiController@update",array("id"=>$client->id))!!}',
            add_public_key: '{!!URL::action("Api\\ClientPublicKeyApiController@_create",array("id"=>$client->id))!!}',
            get_public_keys: '{!!URL::action("Api\\ClientPublicKeyApiController@getAll",array("id"=>$client->id))!!}',
            delete_public_key: '{!!URL::action("Api\\ClientPublicKeyApiController@_delete",array("id" => $client->id, 'public_key_id'=> '@public_key_id'))!!}',
            update_public_key: '{!!URL::action("Api\\ClientPublicKeyApiController@_update",array("id" => $client->id, 'public_key_id'=> '@public_key_id'))!!}',
            fetchUsers: '{!!URL::action("Api\\UserApiController@getAll")!!}',
        };

        var oauth2_supported_algorithms =
        {
            sig_algorihtms:
            {
                mac: {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_signing_algorithms_hmac_sha2)!!},
                rsa: {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_signing_algorithms_rsa)!!}
            },
            key_management_algorihtms: {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_key_management_algorithms)!!},
            content_encryption_algorihtms:  {!!Utils\ArrayUtils::toJson(OAuth2\OAuth2Protocol::$supported_content_encryption_algorithms)!!}
        };

        var current_admin_users  = [];
        var is_mine = {!! $client->isOwner(Auth::user())? 1:0 !!}

        @foreach($client->getAdminUsers() as $user)
        current_admin_users.push({
            "id": {!!$user->getId()!!} ,
            "value": "{!! $user->getFullName() !!}",
            "first_name": "{!! $user->first_name !!}",
            "last_name": "{!! $user->last_name !!}"
        });
        @endforeach

        $(document).ready(function () {
            $('.panel-collapse').collapse('hide');
            location.hash && $(location.hash + '.collapse').collapse('show');

            $(document).on('click', '.head-button', function(e){
                $('.panel-collapse').collapse('hide');
                window.location.hash = $(this).attr('href');
            });
        });
    </script>
@append
@section('content')
@include('menu')
<legend>
    <span aria-hidden="true" class="glyphicon glyphicon-info-sign pointable"
          title="OAuth 2.0 allows users to share specific data with you (for example, contact lists) while keeping their usernames, passwords, and other information private.">

    </span>&nbsp;{!!$client->getFriendlyApplicationType()!!} - Client # {!! $client->id !!}
</legend>
<div class="row">
    <div style="padding-left:15px" class="col-md-2 clear-padding"><strong>Created By:&nbsp;</strong></div><div class="col-md-10 clear-padding">{!! $client->getOwnerNice() !!}</div>
</div>
<div class="row">
    <div style="padding-left:15px" class="col-md-2 clear-padding"><strong>Edited By</strong>:&nbsp;</div><div class="col-md-10 clear-padding">{!! $client->getEditedByNice() !!}</div>
</div>
@if($errors->any())
<div class="errors">
    <ul>
        @foreach($errors->all() as $error)
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {!! $error !!}
        </div>
        @endforeach
    </ul>
</div>
@endif

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    <!-- main data -->
    <div class="panel panel-default" style="padding-bottom:0px">
        <div class="panel-heading" role="tab" id="main_data_heading" style="margin-bottom:0px">
            <h4 class="panel-title">
                <a target="_self" role="button" class="head-button" data-toggle="collapse" data-parent="#accordion" href="#main_data" aria-expanded="true" aria-controls="main_data">
                    OAuth 2.0 Client Data
                </a>
            </h4>
        </div>
        <div id="main_data" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="main_data_heading">
            <div class="panel-body">
                @include('oauth2.profile.edit-client-data', array('access_tokens' => $access_tokens, 'refresh_tokens' => $refresh_tokens,'client' => $client))
            </div>
        </div>
    </div>
    <!-- scopes -->
    <div class="panel panel-default" style="padding-bottom:0px">
        <div class="panel-heading" role="tab" id="allowed_scopes_heading" style="margin-bottom:0px">
            <h4 class="panel-title">
                <a target="_self" class="collapsed head-button" role="button" data-toggle="collapse" data-parent="#accordion" href="#allowed_scopes" aria-expanded="false" aria-controls="allowed_scopes">
                    Application Allowed Scopes
                </a>
            </h4>
        </div>
        <div id="allowed_scopes" class="panel-collapse collapse" role="tabpanel" aria-labelledby="allowed_scopes_heading">
            <div class="panel-body">
                @include('oauth2.profile.edit-client-scopes',array('access_tokens' => $access_tokens, 'refresh_tokens' => $refresh_tokens,'client'=>$client) )
            </div>
        </div>
    </div>
    <!-- grants -->
    <div class="panel panel-default" style="padding-bottom:0px">
        <div class="panel-heading" role="tab" id="grants_heading" style="margin-bottom:0px">
            <h4 class="panel-title">
                <a target="_self" class="collapsed head-button" role="button" data-toggle="collapse" data-parent="#accordion" href="#grants" aria-expanded="false" aria-controls="grants">
                    Application Grants
                </a>
            </h4>
        </div>
        <div id="grants" class="panel-collapse collapse" role="tabpanel" aria-labelledby="grants_heading">
            <div class="panel-body">
                @include('oauth2.profile.edit-client-tokens',array('access_tokens' => $access_tokens, 'refresh_tokens' => $refresh_tokens,'client'=>$client) )
            </div>
        </div>
    </div>
    <!-- security settings -->
    <div class="panel panel-default" style="padding-bottom:0px">
        <div class="panel-heading" role="tab" id="security_heading" style="margin-bottom:0px">
            <h4 class="panel-title">
                <a target="_self" class="collapsed head-button" role="button" data-toggle="collapse" data-parent="#accordion" href="#security" aria-expanded="false" aria-controls="security">
                    Security Settings
                </a>
            </h4>
        </div>
        <div id="security" class="panel-collapse collapse" role="tabpanel" aria-labelledby="security_heading">
            <div class="panel-body">
                @include('oauth2.profile.edit-client-security-main-settings',array('client' => $client) )
                <hr/>
                @include('oauth2.profile.edit-client-public-keys',array('client' => $client) )
                <hr/>
                @include('oauth2.profile.edit-client-security-logout',array('client' => $client) )
            </div>
        </div>
    </div>
</div>
@stop