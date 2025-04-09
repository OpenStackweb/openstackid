@extends('layout')
@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!} - My Account</title>
@stop
@section('content')
@include('menu')

<legend>Authorized Access to your {!! Config::get('app.app_name') !!} Account</legend>

<h2>Connected Sites, Apps, and Services</h2>
<p>
    You have granted the following services access to your {!! Config::get('app.app_name') !!} Account: <br>
</p>
<h4>Online Access&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"></span></h4>
<hr/>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <table id="table-access-tokens" class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>Application Type</th>
                        <th>Issued</th>
                        <th>Application Name</th>
                        <th>Granted Scopes</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody id="body-access-tokens">
                    @foreach($access_tokens as $access_token)
                        <tr id="{!!$access_token->value!!}">
                            <td>{!!$access_token->getClient()->getFriendlyApplicationType()!!}</td>
                            <td>{!!$access_token->created_at->format("Y-m-d H:i:s")!!}</td>
                            <td>{!!$access_token->getClient()->app_name!!}</td>
                            <td>{!!$access_token->scope!!}</td>
                            <td>{!! HTML::link(URL::action("Api\\UserApiController@revokeMyToken",array("id"=>$user_id,"value"=>$access_token->value, "hint"=>'access-token')),'Revoke Access',array('data-value' => $access_token->value,'data-hint'=>'access-token','class'=>'btn btn-default btn-md active btn-delete revoke-token','title'=>'Revoke Access Token', 'target'=>'_self')) !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <span id="info-access-tokens" class="label label-info">** There are not currently access tokens issued for this user.</span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <ul class="pagination" id="access_token_paginator">
                    <?php for($i = 0 ; $i < $access_tokens_pages ; $i++){  ?>
                    <li <?php if($i == 0) echo "class='active'" ?>><a target="_self" class="access_token_page" href="#" data-page-nbr="{!! $i+1 !!}">{!! $i+1 !!}</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>



<h4>Offline Access&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title="In some cases, your application may need to access an API when the user is not present. Examples of this include backup services and applications that make blogger posts exactly at 8am on Monday morning. This style of access is called offline, and web server applications may request offline access from a user. The normal and default style of access is called online. "></span></h4>
<hr/>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <table id="table-refresh-tokens" class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>Application Type</th>
                        <th>Issued</th>
                        <th>Application Name</th>
                        <th>Granted Scopes</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody id="body-refresh-tokens">
                    @foreach($refresh_tokens as $refresh_token)
                        <tr id="{!!$refresh_token->value!!}">
                            <td>{!!$refresh_token->getClient()->getFriendlyApplicationType()!!}</td>
                            <td>{!!$refresh_token->created_at->format("Y-m-d H:i:s")!!}</td>
                            <td>{!!$refresh_token->getClient()->app_name!!}</td>
                            <td>{!!$refresh_token->scope!!}</td>
                            <td>{!! HTML()->a(URL::action("Api\\UserApiController@revokeMyToken",array("id" => $user_id,"value" => $refresh_token->value, "hint" => 'refresh-token')),'Revoke Access',array('data-value' => $refresh_token->value,'data-hint' => 'refresh_token','class' => 'btn btn-default btn-md active btn-delete revoke-token','title' => 'Revoke Access Token', 'target'=>'_self')) !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <span id="info-refresh-tokens" class="label label-info">** There are not currently refresh tokens issued for this user.</span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <ul class="pagination" id="refresh_token_paginator">
                    <?php for($i = 0 ; $i < $refresh_tokens_pages ; $i++){  ?>
                    <li <?php if($i == 0) echo "class='active'" ?> ><a target="_self" class="refresh_token_page" href="#" data-page-nbr="{!! $i+1 !!}">{!! $i+1 !!}</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>

@stop
@section('scripts')
<script type="application/javascript">
        var TokensUrls = {
            AccessTokenUrls: {
                get: '{!! URL::action("Api\\ClientApiController@getAccessTokensByCurrentUser", [] )!!}',
                delete: '{!! URL::action("Api\\UserApiController@revokeMyToken",array("value" => -1, "hint" =>"access-token")) !!}'
            },
            RefreshTokenUrl: {
                get: '{!! URL::action("Api\\ClientApiController@getRefreshTokensByCurrentUser", [] )!!}',
                delete: '{!! URL::action("Api\\UserApiController@revokeMyToken", array("value" => -1, "hint" => "refresh-token")) !!}'
            }
        };
</script>
<script type="text/javascript" src="{{ asset('assets/js/oauth2/profile/edit-user-grants.js') }}"></script>
@append