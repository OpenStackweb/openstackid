@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Security - Edit Group</title>
@stop

@section('content')
    @include('menu')
    <div class="row">
        <div class="col-xs-12">
            <a target="_self" href='{!! URL::action("AdminController@listGroups") !!}'><i class="fa fa-chevron-circle-left"></i> Go Back</a>
        </div>
    </div>
    <div class="row">
        <form id="group-form" name="group-form" role="form"
              action='{!!URL::action("Api\\GroupApiController@update",["id" => $group->id])!!}'>
            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                <label for="name">Name</label>
                <input class="form-control" type="text" name="name" id="name" value="{!! $group->name !!}" autocomplete="off" data-lpignore="true">
            </div>
            <div class="form-group col-xs-10 col-sm-4 col-md-6 col-lg-6">
                <label for="slug">Slug</label>
                <input class="form-control" type="text" name="slug" id="slug" value="{!! $group->slug !!}" autocomplete="off" data-lpignore="true">
            </div>
            <div class="clearfix"></div>

            <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                <label>
                    <input type="checkbox" id="active" name="active"
                           @if($group->active)
                           checked
                            @endif
                    >&nbsp;Is Active?
                </label>
            </div>
            <div class="checkbox col-xs-10 col-sm-4 col-md-12 col-lg-12">
                <label>
                    <input type="checkbox" id="default" name="default"
                           @if($group->default)
                           checked
                            @endif
                    >&nbsp;Is Assigned By Default?
                </label>
            </div>

            <div class="col-xs-10 col-sm-4 col-md-12 col-lg-12">
                <h4>Members</h4>
                <div class="row">
                    <div class="col-md-6" id="search-container" style="display: none">
                        <input type="text" placeholder="Filter By Name/Email" id="search-term">
                        <button id="btn-do-search" title="Filter"><i class="fa fa-search"></i></button>
                        <button id="btn-do-search-clear"><i class="fa fa-close"></i></button>
                    </div>
                    <div class="col-md-6">
                        <input type="text" id="add-user" placeholder="Search User By Name/Email" data-placeholder="Search User By Name/Email">
                        <button id="btn-add-user" title="Add new User to Group"><i class="fa fa-plus-circle"></i></button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <table id="table" class="table table-hover table-condensed" style="display: none">
                            <thead>
                            <tr>
                                <th>Id</th>
                                <th>FullName</th>
                                <th>Email</th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody id="body-table">
                            </tbody>
                        </table>
                        <span id="info" class="label label-info" style="display: none">** There are not any Users.</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12" id="pager-container">
                        <ul id="pager" class="pagination">
                        </ul>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-default btn-lg btn-primary">Save</button>
            <input type="hidden" name="id" id="id" value="{!! $group->id !!}"/>
        </form>
    </div>
@stop
@section('scripts')

    {!! HTML::script("assets/js/urlfragment.jquery.js") !!}
    {!! HTML::script("assets/moment/min/moment.min.js") !!}
    {!! HTML::script('assets/js/basic-crud.js') !!}
    {!! HTML::script('assets/js/admin/edit-group.js') !!}
    <style>
        .bootstrap-tagsinput {
            max-width: 90% !important;
        }
        #btn-add-user{
            height: 33px;
            width: 33px;
        }
    </style>
    <script type="application/javascript">
        var urls = {
            add: '{!! URL::action("Api\\GroupApiController@addUserToGroup",["id"=>"$group->id", "user_id" => "@id"]) !!}',
            load: '{!! URL::action("Api\\GroupApiController@getUsersFromGroup",["id"=>$group->id])!!}',
            delete: '{!! URL::action("Api\\GroupApiController@removeUserFromGroup",["id"=>"$group->id", "user_id" => "@id"]) !!}',
            fetchUsers: '{!!URL::action("Api\\UserApiController@getAll")!!}',
        };

        var members = new GroupMembersCrud(urls, 10);
        members.init();
    </script>

@append