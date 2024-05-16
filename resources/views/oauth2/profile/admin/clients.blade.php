@extends('layout')

@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!} - Server Admin - OAUTH2 - Clients</title>
@stop

@section('content')
@include('menu')
<legend>Locked OAUTH2 Clients</legend>
<div class="row-fluid">
    <div class="span12">

        <table id='clients-table' class="table table-hover table-condensed">
            <thead>
            <tr>
                <th>Client</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody id="body-locked-clients">
              @foreach($clients->getItems() as $client)
              <tr id="{!!$client->id!!}">
                  <td>
                      <div style="min-width: 500px">
                      {!! $client->getApplicationName() !!}
                      </div>
                  </td>
                  <td>
                      {!! link_to(URL::action("Api\ClientApiController@unlock",array("id"=>$client->id)),'Unlock',array('class'=>'btn btn-primary unlock-client','data-client-id'=>$client->id,'title'=>'Unlocks given client', 'target'=>'_self')) !!}
                  </td>
              </tr>
              @endforeach
            </tbody>
        </table>

        <span id="clients-info" class="label label-info">** There are not any locked OAUTH2 Client.</span>

    </div>
</div>
@stop
@section('scripts')
    {!! script_to('assets/js/oauth2/profile/admin/clients.js') !!}
@stop