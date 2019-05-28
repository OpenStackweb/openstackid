@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Server Admin - Edit Api Scope Group</title>
@stop

@section('content')
    @include('menu')
    <a href="{!! URL::action("AdminController@listApiScopeGroups") !!}">Go Back</a>
    <legend>Edit Api Scope Group - Id {!! $group->id !!}</legend>
    <div class="row">
        <div class="col-md-12">
            <form id="api-scope-group-form" name="api-scope-group-form" action='{!!URL::action("Api\ApiScopeGroupController@update",['id' => $group->id ])!!}'>

                <div class="form-group">
                    <label class="control-label" for="name">Friendly Name&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
                    <input type="text" class="form-control" name="name" id="name" value="{!! $group->name!!}">
                </div>
                <div class="form-group">
                    <label class="control-label" for="scopes">Scopes&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
                    <input type="text" class="form-control" name="scopes" id="scopes" value="">
                </div>
                <div class="form-group">
                    <label class="control-label" for="users">Users&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
                    <input type="text" class="form-control" name="users" id="users" value="">
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="active" name="active"
                               @if ( $group->active)
                               checked
                                @endif
                        >&nbsp;Active
                    </label>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn btn-default active btn-lg">Save</button>
                    </div>
                </div>
                <input type="hidden" name="id" id="id" value="{!! $group->id !!}"/>
            </form>
        </div>
    </div>

@stop

@section('scripts')
    <script type="application/javascript">
        var group_id = {!! $group->id!!};

        var ApiScopeGroupUrls = {
            get : '{!!URL::action("Api\ApiScopeGroupController@getAll",array("page"=>1,"per_page"=>100))!!}',
            edit : '{!! URL::action("AdminController@editApiScopeGroup",array("id"=>-1)) !!}',
            delete : '{!! URL::action("Api\ApiScopeGroupController@delete",array("id"=>-1)) !!}',
            activate : '{!! URL::action("Api\ApiScopeGroupController@activate",array("id"=>"@id")) !!}',
            deactivate : '{!! URL::action("Api\ApiScopeGroupController@deactivate",array("id"=>"@id")) !!}',
            add : '{!!URL::action("Api\ApiScopeGroupController@create",null)!!}',
            fetchUsers: '{!!URL::action("Api\UserApiController@getAll")!!}',
            fetchScopes: '{!!URL::action("Api\ApiScopeController@getAll")!!}'
        };

        var current_scopes = [];
        var current_users  = [];

        @foreach($group->getScopes() as $scope)
            current_scopes.push({ "id": {!!$scope->id!!} , "name": "{!!$scope->name!!}" });
        @endforeach

        @foreach($group->getUsers() as $user)
            current_users.push({ "id": {!!$user->id!!} ,
                                  "value": "{!! $user->getFullName() !!}",
                                  "first_name": "{!! $user->first_name !!}",
                                  "last_name": "{!! $user->last_name !!}"
            });
        @endforeach

    </script>
    {!! HTML::script('assets/js/oauth2/profile/admin/edit-api-scope-group.js') !!}
@append