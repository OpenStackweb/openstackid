@extends('layout')

@section('title')
    <title>Welcome to {!! Config::get('app.app_name') !!} - Logout</title>
@stop

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-sm-6">
                @if(count($clients) > 0)
                <p> Would you like to log out from the following? </p>
                <p>
                    <ul>
                    @foreach ($clients as $client)
                        <li><b>{!!$client->getApplicationName()!!}</b></li>
                    @endforeach
                    </ul>
                 </p>
                @else
                    <p> Would you like to log out? </p>
                @endif
                {!! basic_form_open(URL::action('OAuth2\OAuth2ProviderController@endSession'), 'post', array("autocomplete" => "off")) !!}
                    <fieldset>
                        <input  type="hidden" name="oidc_endsession_consent" id="oidc_endsession_consent" value="1"/>
                        <input  type="hidden" name="id_token_hint" id="id_token_hint" value="{!!$id_token_hint!!}"/>
                        <input  type="hidden" name="post_logout_redirect_uri" id="post_logout_redirect_uri" value="{!!$post_logout_redirect_uri!!}"/>
                        <input  type="hidden" name="state" id="state" value="{!!$state!!}"/>
                        <input  type="hidden" name="client_id" id="client_id" value="{!!$client_id!!}"/>
                        <div class="form-group">
                            {!! submit_button('Yes ',array('id'=>'login','class'=>'btn active btn-primary')) !!}
                            <a target="_self" class="btn btn-danger active"
                               href="{!! URL::action('OAuth2\OAuth2ProviderController@cancelLogout') !!}">No</a>
                        </div>
                    </fieldset>
                {!! form_close !!}
            </div>
        </div>
    </div>
@stop
@section('scripts')
    {!! script_to('assets/crypto-js/crypto-js.js')!!}
    {!! script_to('assets/jquery-cookie/jquery.cookie.js')!!}
@append