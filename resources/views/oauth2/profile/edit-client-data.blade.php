<div class="row">

    <div class="col-md-12">

        <h4>Client Credentials&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                          title=""></span></h4>
        <hr/>
        <div class="row">
            <div class="col-md-12">
                <label for="client_id" class="label-client-secret">Client ID</label>
                <div id="client_id" class="input-group">
                    <input type="text" id="client_id_text" class="form-control input-monospace input-sm"
                           data-autoselect=""
                           value="{!! $client->client_id !!}"
                           aria-label="Client Id"
                           readonly="">
                    <div class="input-group-button">
                        <clipboard-copy
                                        for="client_id_text"
                                        aria-label="Copy to clipboard"
                                        class="btn btn-sm"
                                        tabindex="0" role="button">
                            <svg class="octicon octicon-clippy" viewBox="0 0 14 16" version="1.1" width="14" height="16" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2 13h4v1H2v-1zm5-6H2v1h5V7zm2 3V8l-3 3 3 3v-2h5v-2H9zM4.5 9H2v1h2.5V9zM2 12h2.5v-1H2v1zm9 1h1v2c-.02.28-.11.52-.3.7-.19.18-.42.28-.7.3H1c-.55 0-1-.45-1-1V4c0-.55.45-1 1-1h3c0-1.11.89-2 2-2 1.11 0 2 .89 2 2h3c.55 0 1 .45 1 1v5h-1V6H1v9h10v-2zM2 5h8c0-.55-.45-1-1-1H8c-.55 0-1-.45-1-1s-.45-1-1-1-1 .45-1 1-.45 1-1 1H3c-.55 0-1 .45-1 1z"></path>
                            </svg>
                            <span class="notice" hidden>Copied!<span>
                        </clipboard-copy>
                    </div>
                </div>

            </div>
        </div>
        @if($client->client_type == OAuth2\Models\IClient::ClientType_Confidential)

            <div class="row">
                <div class="col-md-12">
                    <label for="client_secret" class="label-client-secret">Client Secret</label>
                    @if ($client->isOwner(Auth::user()))
                        {!! link_to(URL::action("Api\\ClientApiController@regenerateClientSecret",array("id"=>$client->id)),'Regenerate',array('class'=>'btn btn-default btn-xs active regenerate-client-secret','title'=>'Regenerates Client Secret', 'target'=>'_self')) !!}
                    @endif
                    <div id="client_secret" class="input-group">
                         <input type="text" class="form-control input-monospace input-sm"
                                data-autoselect=""
                                value="{!! $client->client_secret !!}"
                                aria-label="Client Secret"
                                id="client_secret_text"
                                readonly="">
                        <div class="input-group-button">
                            <clipboard-copy
                                    for="client_secret_text"
                                    aria-label="Copy to clipboard"
                                    class="btn btn-sm"
                                    tabindex="0" role="button">
                                <svg class="octicon octicon-clippy" viewBox="0 0 14 16" version="1.1" width="14" height="16" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M2 13h4v1H2v-1zm5-6H2v1h5V7zm2 3V8l-3 3 3 3v-2h5v-2H9zM4.5 9H2v1h2.5V9zM2 12h2.5v-1H2v1zm9 1h1v2c-.02.28-.11.52-.3.7-.19.18-.42.28-.7.3H1c-.55 0-1-.45-1-1V4c0-.55.45-1 1-1h3c0-1.11.89-2 2-2 1.11 0 2 .89 2 2h3c.55 0 1 .45 1 1v5h-1V6H1v9h10v-2zM2 5h8c0-.55-.45-1-1-1H8c-.55 0-1-.45-1-1s-.45-1-1-1-1 .45-1 1-.45 1-1 1H3c-.55 0-1 .45-1 1z"></path>
                                </svg>
                                <span class="notice" hidden>Copied!<span>
                            </clipboard-copy>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if($client->canRequestRefreshTokens())
            <div class="row">
                <div class="col-md-12">
                    <label class="label-client-secret">Client Settings</label>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"
                                   @if ($client->use_refresh_token)
                                   checked
                                   @endif
                                   id="use-refresh-token">
                            Use Refresh Tokens
                            &nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle"
                                        aria-hidden="true"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"
                                   @if ($client->rotate_refresh_token)
                                   checked
                                   @endif
                                   id="use-rotate-refresh-token-policy">
                            Use Rotate Refresh Token Policy
                            &nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle"
                                        aria-hidden="true"></span>
                        </label>
                    </div>
                </div>
            </div>
        @endif
        <h4>Client Data</h4>
        <hr/>
        <div class="row">
            <div class="col-md-12">
                <form id="form-application-main-data" name="form-application-main-data">
                     <div class="form-group">
                        <label class="control-label" for="app_name">Application Name&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title="Choose which users would be administrator of this application"></span></label>
                        <input class="form-control"  type="text" name="app_name" id="app_name" value="{!! $client->app_name !!}" />
                    </div>
                    <div class="form-group">
                        <label for="app_description">Application Description&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                        title=""></span></label>
                        <textarea class="form-control" style="resize: none;" rows="4" cols="50" name="app_description"
                                  id="app_description">{!!$client->app_description!!}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="admin_users">Admin Users&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title="Choose which users would be administrator of this application"></span></label>
                        <input type="text" class="form-control" name="admin_users" id="admin_users" @if (!$client->isOwner(Auth::user()))disabled @endif>
                        @if (!$client->isOwner(Auth::user()))
                        <p>** you can not edit admin users because your are not the application owner.</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="website">Application Web Site Url (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                            title="URL of the home page of the Client"></span></label>
                        <input class="form-control" type="text" name="website" id="website"
                               value="{!!$client->website!!}">
                    </div>
                    <div class="form-group">
                        <label for="logo_uri">Application Logo Url (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                         title="URL that references a logo for the Client application"></span></label>
                        <input class="form-control" type="text" name="logo_uri" id="logo_uri"
                               value="{!!$client->logo_uri!!}">
                    </div>
                    <div class="form-group">
                        <label for="tos_uri">Application Term of Service Url (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                                   title="URL that the Relying Party Client provides to the End-User to read about the Relying Party's terms of service"></span></label>
                        <input class="form-control" type="text" name="tos_uri" id="tos_uri"
                               value="{!!$client->tos_uri!!}">
                    </div>
                    <div class="form-group">
                        <label for="policy_uri">Application Policy Url (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                             title="URL that the Relying Party Client provides to the End-User to read about the how the profile data will be used"></span></label>
                        <input class="form-control" type="text" name="policy_uri" id="policy_uri"
                               value="{!!$client->policy_uri!!}">
                    </div>
                    <div class="form-group">
                        <label for="contacts">Contact Emails (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                   title="e-mail addresses of people responsible for this Client"></span></label>
                        <input type="text" name="contacts" id="contacts" value="{!!$client->contacts!!}"
                               style="width: 100%"></input>
                    </div>
                    @if($client->application_type != oauth2\models\IClient::ApplicationType_Service)
                    <div class="form-group">
                        <label for="redirect_uris">Allowed Redirection Uris (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                                  title="Redirection URI values used by the Client."></span></label>
                        <input type="text" name="redirect_uris" id="redirect_uris" value="{!!$client->redirect_uris!!}"
                               style="width: 100%"></input>
                    </div>
                    @endif
                    @if($client->application_type == oauth2\models\IClient::ApplicationType_JS_Client)
                    <div class="form-group">
                        <label for="allowed_origins">Allowed javascript origins (optional)&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true"
                                                                                                title="Allowed js origin URI values used by the Client."></span></label>
                        <input type="text" name="allowed_origins" id="allowed_origins" value="{!!$client->allowed_origins!!}"
                               style="width: 100%"></input>
                    </div>
                    @endif
                    <input type="hidden" id="id" name="id" value="{!!$client->id!!}"/>
                    <input type="hidden" id="application_type" name="application_type"
                           value="{!!$client->application_type!!}"/>
                    <input type="hidden" id="user_id" name="user_id" value="{!!$client->user_id!!}"/>
                    <button type="submit" class="btn btn-default btn-md active btn-save-client-data">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    {!! script_to('assets/clipboard-copy-element/index.umd.js')!!}
    {!! script_to('assets/js/oauth2/profile/edit-client-data.js') !!}'
    <script>
        document.addEventListener('clipboard-copy', function (event) {
            const notice = event.target.querySelector('.notice')
            notice.hidden = false
            setTimeout(function () {
                notice.hidden = true
            }, 1000)
        })
    </script>
@append