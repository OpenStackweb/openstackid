@extends('layout')

@section('title')
<title>Welcome to {!! Config::get('app.app_name') !!} - Server Admin - Banned Ips</title>
@stop

@section('content')
@include('menu')
<legend>Banned Ips</legend>
<div class="row">
    <div class="col-md-12">

        <table id="ips-table" class="table table-hover table-condensed">
            <thead>
            <tr>
                <th>IP Address</th>
                <th>Date</th>
                <th>Hits</th>
                <th>Cause</th>
                <th>User</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody id="body-ips">
            @foreach($page->getItems() as $ip)
            <tr id="{!!$ip->id!!}">
                <td>{!!$ip->ip!!}</td>
                <td>{!!$ip->created_at->format("Y-m-d H:i:s")!!}</td>
                <td>{!!$ip->hits!!}</td>
                <td>{!!$ip->exception_type!!}</td>
                <td>
                    @if($ip->hasUser())
                        {!! $ip->getUser()->getEmail() !!}
                    @else
                    N\A
                    @endif
                </td>
                <td>
                    {!! HTML::link(URL::action("Api\\ApiBannedIPController@delete",array("id"=>$ip->id)),'Revoke',array('data-ip-id'=>$ip->id,'class'=>'btn btn-default btn-md active revoke-ip','title'=>'Revoke given banned ip address')) !!}
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <span id="ips-info" class="label label-info">** There are not any Banned IPs.</span>
    </div>
</div>
@stop

@section('scripts')
{!! HTML::script('assets/js/admin/banned-ips.js') !!}
@append
