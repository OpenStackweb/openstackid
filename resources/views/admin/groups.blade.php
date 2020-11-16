@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Security - Groups</title>
@stop

@section('content')
    @include('menu')
    <legend>Groups</legend>
    <div class="row">
        <div class="col-md-12">
            {!! HTML::link( null,'Add New Group',['class'=>'btn btn-primary btn-md active add-item-button','title'=>'Add a Group']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <input type="text" placeholder="Search By Name/Slug" id="search-term">
            <button id="btn-do-search"><i class="fa fa-search"></i></button>
            <button id="btn-do-search-clear"><i class="fa fa-close"></i></button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table id="table" class="table table-hover table-condensed">
                <thead>
                <tr>
                    <th data-field="id" class="sort-header">Id</th>
                    <th data-field="name" class="sort-header current asc">Name</th>
                    <th data-field="slug" class="sort-header">Slug</th>
                    <th>Active</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody id="body-table">
                </tbody>
            </table>
            <span id="info" class="label hidden label-info">** There are not any Groups.</span>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" id="pager-container">
            <ul id="pager" class="pagination">
            </ul>
        </div>
    </div>
    <div id="dialog-form-add-item" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h3 id="myModalLabel">Register new Group</h3>
                </div>
                <div class="modal-body">
                    @include('admin.add-group-form',[])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button id='save-item' type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
@stop
@section('scripts')

    {!! HTML::script("assets/js/urlfragment.jquery.js") !!}
    {!! HTML::script("assets/moment/min/moment.min.js") !!}
    {!! HTML::script('assets/js/basic-crud.js') !!}
    {!! HTML::script('assets/js/admin/groups.js') !!}

    <script type="application/javascript">
        var urls = {
            add: '{!! URL::action("Api\\GroupApiController@create") !!}',
            load: '{!! URL::action("Api\\GroupApiController@getAll")!!}',
            delete: '{!! URL::action("Api\\GroupApiController@delete",["id"=>"@id"]) !!}',
            edit: '{!! URL::action("AdminController@editGroup",["id"=>"@id"]) !!}',
            fetchUsers: '{!!URL::action("Api\\UserApiController@getAll")!!}',
        };

        var groups = new GroupsCrud(urls, 10);
        groups.init();
    </script>

@append