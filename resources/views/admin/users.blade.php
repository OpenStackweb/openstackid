@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Security - Users</title>
@stop

@section('content')
    @include('menu')
    <legend>Users</legend>
    <div class="row">
       <div class="col-md-12">
           {!! HTML()->a( null,'Add New User',['class'=>'btn btn-primary btn-md active add-item-button','title'=>'Add an user', 'target'=>'_self']) !!}
       </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <input type="text" placeholder="Search By Name/Email" id="search-term">
            <button id="btn-do-search"><i class="fa fa-search"></i></button>
            <button id="btn-do-search-clear"><i class="fa fa-close"></i></button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table id="table" class="table table-hover table-condensed">
                <thead>
                <tr>
                    <th data-field="identifier" class="sort-header">Identifier</th>
                    <th data-field="first_name" class="sort-header">First Name</th>
                    <th data-field="last_name" class="sort-header">Surname</th>
                    <th data-field="email" class="sort-header current asc">Email</th>
                    <th>Active</th>
                    <th data-field="last_login_date" class="sort-header">Last Login Date</th>
                    <th data-field="spam_type" class="sort-header">Spam Type</th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody id="body-table">
                </tbody>
            </table>
            <span id="info" class="label hidden label-info">** There are not any Users.</span>
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
                    <h3 id="myModalLabel">Register new User</h3>
                </div>
                <div class="modal-body">
                    @include('admin.add-user-form',[])
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

    <script type="text/javascript" src="{{ asset('assets/pwstrength-bootstrap/pwstrength-bootstrap.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/urlfragment.jquery.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/moment/min/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/basic-crud.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/admin/users.js') }}"></script>

    <script type="application/javascript">
        var urls = {
            add: '{!! URL::action("Api\\UserApiController@create") !!}',
            load: '{!! URL::action("Api\\UserApiController@getAll")!!}',
            delete: '{!! URL::action("Api\\UserApiController@delete",["id"=>"@id"]) !!}',
            unlock: '{!! URL::action("Api\\UserApiController@unlock",["id"=>"@id"]) !!}',
            lock: '{!! URL::action("Api\\UserApiController@lock",["id"=>"@id"]) !!}',
            edit: '{!! URL::action("AdminController@editUser",["user_id"=>"@id"]) !!}',
            fetchGroups: '{!!URL::action("Api\GroupApiController@getAll")!!}',
        };

        var user = new UsersCrud(urls, 10);
        user.init();
    </script>

@append