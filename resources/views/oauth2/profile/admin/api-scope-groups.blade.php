@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Server Admin - Api Scope Groups</title>
@stop

@section('content')

@include('menu')
<div class="row">
    <div class="row">
        <h4 style="float:left"><span aria-hidden="true" class="glyphicon glyphicon-info-sign pointable" title="Registered Api Scope Groups"></span>&nbsp;Api Scope Groups</h4>
        <div style="position: relative;float:left;">
            <div style="position:absolute;top:13px;margin-left:5px"><span aria-hidden="true" class="glyphicon glyphicon-refresh pointable refresh-groups"title="Update Api Scope Group List"></span></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {!! link_to(URL::action("Api\ApiScopeGroupController@create"),'Add Api Scope Group',array('class'=>'btn active btn-primary add-api-scope-group','title'=>'Adds a New Api Scope Group', 'target'=>'_self')) !!}
        </div>
    </div>

    <table id='table-api-scope-groups' class="table table-hover table-condensed">
        <thead>
        <tr>
            <th>Name</th>
            <th>Active</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="body-api-scope-groups">
        @foreach ($groups->getItems() as $group)
            <tr id="{!! $group->id !!}">
                <td>{!!$group->name!!}</td>
                <td>
                    <input type="checkbox" class="api-scope-group-active-checkbox"
                           id="api-scope-group-active_{!!$group->id!!}"
                           data-group-id="{!!$group->id!!}"
                           @if ( $group->active)
                           checked
                           @endif
                           value="{!!$group->id!!}"/>
                </td>
                <td>
                    &nbsp;
                    {!! link_to(URL::action("AdminController@editApiScopeGroup",array("id"=>$group->id)),'Edit',array('class'=>'btn btn-default active edit-api-scope-group','title'=>'Edit a Registered Api Scope Group', 'target '=>'_self')) !!}
                    {!! link_to(URL::action("Api\ApiScopeGroupController@delete",array("id"=>$group->id)),'Delete',array('class'=>'btn btn-default btn-delete active delete-api-scope-group','title'=>'Deletes a Registered Api Scope Group', 'target'=>'_self')) !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div id="info-api-scope-groups" class="alert alert-danger private-keys-empty-message" role="alert"
         @if($groups->getTotal() > 0)
         style="display: none"
            @endif
    >
        <p>There are not any available Api Scope Groups</p>
    </div>

</div>

@include('modal', array ('modal_id' => 'dialog-form-api-scope-group', 'modal_title' => 'Register New Api Scope Group', 'modal_save_css_class' => 'save-api-scope-group', 'modal_save_text' => 'Save', 'modal_form' => 'oauth2.profile.admin.api-scope-group-add-form', 'modal_form_data' => array()))

@stop

@section('scripts')
    <script type="application/javascript">
        var ApiScopeGroupUrls =
        {
            get : '{!!URL::action("Api\ApiScopeGroupController@getAll",array("page"=>1,"per_page"=>100))!!}',
            edit : '{!! URL::action("AdminController@editApiScopeGroup",array("id"=>-1)) !!}',
            delete : '{!! URL::action("Api\ApiScopeGroupController@delete",array("id"=>-1)) !!}',
            activate : '{!! URL::action("Api\ApiScopeGroupController@activate",array("id"=>"@id")) !!}',
            deactivate : '{!! URL::action("Api\ApiScopeGroupController@deactivate",array("id"=>"@id")) !!}',
            add : '{!!URL::action("Api\ApiScopeGroupController@create",null)!!}',
            fetchUsers: '{!!URL::action("Api\UserApiController@getAll")!!}',
            fetchScopes: '{!!URL::action("Api\ApiScopeController@getAll")!!}'
        };
    </script>
    {!! script_to('assets/js/oauth2/profile/admin/api-scope-groups.js') !!}
@append

@section('css')

@append