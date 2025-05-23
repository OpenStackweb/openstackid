@extends('layout')

@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!} - Server Admin - Edit Resource Server</title>
@stop

@section('css')

@append

@section('content')
@include('menu')
<a target="_self" href="{!! URL::action("AdminController@listResourceServers") !!}">Go Back</a>
<legend>Edit Resource Server - Id {!! $resource_server->id !!}</legend>
<div class="row">
    <div class="col-md-12">
        <form id="resource-server-form" name="resource-server-form" action='{!!URL::action("Api\\ApiResourceServerController@update",["id"=> $resource_server->id])!!}'>
             <div class="form-group">
                    <label class="control-label" for="host">Host&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
                    <input type="text" class="form-control" name="host" id="host" value="{!! $resource_server->host !!}">
                </div>

                <div class="form-group">
                    <label class="control-label" for="friendly_name">Friendly Name&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
                    <input type="text" class="form-control" name="friendly_name" id="friendly_name" value="{!! $resource_server->friendly_name !!}">
                </div>

            <div class="form-group">
                <label for="ip">IP Addresses&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                           title=""></span></label>
                <input type="text" name="ips" id="ips" value="{!!$resource_server->ips!!}"
                       style="width: 100%"></input>
            </div>


                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="active" name="active"
                               @if ( $resource_server->active)
                               checked
                                @endif
                        >&nbsp;Active
                    </label>
                </div>

                @if(!is_null($resource_server->getClient()))
                <div class="form-group">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-3">
                                    <label for="client_id" class="label-client-secret">Client ID</label>
                                </div>
                                <div class="col-lg-9">
                                    <span id="client_id">{!! $resource_server->getClient()->client_id !!}</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3">
                                    <label for="client_secret" class="label-client-secret">Client Secret</label>
                                </div>
                                <div class="col-lg-7">
                                    <span id="client_secret">{!! $resource_server->getClient()->client_secret !!}</span>
                                </div>
                                <div class="col-lg-2">
                                    {!! link_to(URL::action("Api\\ApiResourceServerController@regenerateClientSecret",array("id"=> $resource_server->id)),'Regenerate',array('class'=>'btn regenerate-client-secret btn-xs btn-default active btn-delete','title'=>'Regenerates Client Secret', 'target'=>'_self')) !!}
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn btn-default active btn-lg">Save</button>
                    </div>
                </div>
                <input type="hidden" name="id" id="id" value="{!! $resource_server->id !!}"/>

        </form>
    </div>
</div>
<br/>
<legend>Available Apis&nbsp;<span class="glyphicon glyphicon-refresh accordion-toggle refresh-apis" aria-hidden="true" title="Update Apis List"></span></legend>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                {!! link_to(URL::action("Api\\ApiController@create"),'Register API',array('class'=>'btn btn-primary active btn-sm add-api','title'=>'Adds a New API', 'target'=>'_self')) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info" id="info-apis" style="display: none">
                    <strong>There are not any available APIS</strong>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id='table-apis' class="table table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Name</th>
                        <th>Active</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody id="body-apis">
                    @foreach($resource_server->getApis() as $api)
                        <tr>
                            <td><img src="{!! $api->getLogo()!!}"  height="24" width="24" alt="{!! $api->name!!} logo"/></td>
                            <td width="60%">{!! $api->name!!}</td>
                            <td>
                                <input type="checkbox" class="api-active-checkbox" data-api-id="{!!$api->id!!}"
                                       id="resource-server-api-active_{!!$api->id!!}"
                                       @if ( $api->active)
                                       checked
                                       @endif
                                       value="{!!$api->id!!}"/>
                            </td>
                            <td>
                                &nbsp;
                                {!! link_to(URL::action("AdminController@editApi",array("id"=>$api->id)),'Edit',array('class'=>'btn btn-default active edit-api','title'=>'Edits a Registered Resource Server API', 'target'=>'_self')) !!}
                                {!! link_to(URL::action("Api\\ApiController@delete",array("id"=>$api->id)),'Delete',array('class'=>'btn btn-default btn-delete active delete-api','title'=>'Deletes a Registered Resource Server API', 'target'=>'_self'))!!}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('modal', array ('modal_id' => 'dialog-form-api', 'modal_title' => 'Register New Resource Server API', 'modal_save_css_class' => 'save-api', 'modal_save_text' => 'Save', 'modal_form' => 'oauth2.profile.admin.resource-server-api-add-form', 'modal_form_data' => array()))
@stop

@section('scripts')
<script type="application/javascript">

    var resource_server_id = {!! $resource_server->id!!};

	var ApiUrls = {
		get : '{!! URL::action("Api\\ApiController@getAll",array("page"=>1,"per_page"=>100,"filter"=> "resource_server_id==".$resource_server->id)) !!}',
		edit : '{!! URL::action("AdminController@editApi",array("id"=>-1)) !!}',
		delete : '{!! URL::action("Api\\ApiController@delete",array("id"=>-1)) !!}',
		add : '{!!URL::action("Api\\ApiController@create",null)!!}',
		activate: '{!! URL::action("Api\\ApiController@activate",array("id"=>"@id")) !!}',
		deactivate: '{!! URL::action("Api\\ApiController@deactivate",array("id"=>"@id")) !!}'
	};

	var resourceServerMessages = {
		success : '@lang("messages.global_successfully_save_entity", array("entity" => "Resource Server"))'
	};
</script>
{!! script_to('assets/js/oauth2/profile/admin/edit-resource-server.js') !!}
@append