@extends('layout')
@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Server Admin - Edit API Scope</title>
@stop
@section('content')
    @include('menu')
    <a target="_self" href='{!!  URL::action("AdminController@editApi",array("id"=>$scope->api_id)) !!}'>Go Back</a>
    <legend>Edit API Scope - Id {!! $scope->id !!}</legend>
    <div class="row">
        <div class="col-md-6">
            <form id="scope-form" name="scope-form" action='{!!URL::action("Api\\ApiScopeController@update", array("id"=>$scope->id))!!}'>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input class="form-control" type="text" name="name" id="name" value="{!! $scope->name !!}">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                     <textarea class="form-control" style="resize: none;" rows="4" cols="50" name="description"
                                  id="description">{!! $scope->description!!}</textarea>
                </div>
                <div class="form-group">
                    <label for="short_description">Short Description</label>
                     <textarea class="form-control" style="resize: none;" rows="4" cols="50" name="short_description"
                                  id="short_description">{!! $scope->short_description!!}</textarea>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="default"
                               @if ( $scope->default)
                               checked
                               @endif
                               name="default">&nbsp;Default
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="system"
                               @if ( $scope->system)
                               checked
                               @endif
                               name="system">&nbsp;System
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="active"
                               @if ( $scope->active)
                               checked
                               @endif
                               name="active">&nbsp;Active
                    </label>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="assigned_by_groups"
                               @if ( $scope->assigned_by_groups)
                               checked
                               @endif
                               name="assigned_by_groups">&nbsp;Assigned By Groups
                    </label>
                </div>
                <button type="submit" class="btn btn-default active">Save</button>
                <input type="hidden" name="id" id="id" value="{!! $scope->id !!}"/>
            </form>
        </div>
    </div>
@stop

@section('scripts')
    <script type="application/javascript">
        var editScopeMessages = {
            success: '@lang("messages.global_successfully_save_entity", array("entity" => "Scope"))'
        };
    </script>
    {!! script_to('assets/js/oauth2/profile/admin/edit-scope.js') !!}
@append