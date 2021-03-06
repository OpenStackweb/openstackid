
function loadClients(){
    $.ajax({
            type: "GET",
            url: clientsUrls.load,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            timeout:60000,
            success: function (page,textStatus,jqXHR) {
                //load data...
                var clients = page.data;
                var template = $('<tbody><tr><td class="admin-app"></td><td class="app-name"></td><td class="client-type"></td><td class="client-active"><input type="checkbox" class="app-active-checkbox"></td><td class="client-locked"><input type="checkbox" disabled="disabled" class="app-locked-checkbox"></td><td class="client-modified"></td><td class="client-modified-by"></td><td class="client-actions">&nbsp;<a target="_self" class="btn btn-default btn-md active edit-client" title="Edits a Registered Application">Edit</a>&nbsp;<a target="_self" class="btn btn-default btn-md active del-client" title="Deletes a Registered Application">Delete</a></td></tr></tbody>');
                var directives = {
                    'tr':{
                        'client<-context':{
                            'td.admin-app':function(arg){
                                return arg.item.is_own?'':'<i title="you have admin rights on this application" class="fa fa-user"></i>';
                            },
                            'td.app-name':'client.app_name',
                            'td.client-type':'client.friendly_application_type',
                            'td.client-modified':'client.updated_at',
                            'td.client-modified-by':'client.modified_by',
                            '.app-active-checkbox@value':'client.id',
                            '.app-active-checkbox@checked':function(arg){
                                return arg.item.active?'true':'';
                            },
                            '.app-active-checkbox@id':function(arg){
                                var client_id = arg.item.id;
                                return 'app-active_'+client_id;
                            },
                            '.app-locked-checkbox@value':'client.id',
                            '.app-locked-checkbox@id':function(arg){
                                var client_id = arg.item.id;
                                return 'app-locked_'+client_id;
                            },
                            '.app-locked-checkbox@checked':function(arg){
                                return arg.item.locked?'true':'';
                            },
                            'a.edit-client@href':function(arg){
                                var client_id = arg.item.id;
                                var href = clientsUrls.edit;
                                return href.replace('@id',client_id);
                            },
                            'a.del-client@href':function(arg){
                                var client_id = arg.item.id;
                                var href = clientsUrls.delete;
                                return href.replace('@id',client_id);
                            },
                            'a.del-client@class+':function(arg)
                            {
                                return arg.item.is_own?'':' hidden';
                            },
                            '.app-active-checkbox@class+':function(arg){
                                return arg.item.is_own?'':' hidden';
                            }
                        }
                    }
                };
                var body = template.render(clients, directives);
                var table = $('<table id="tclients" class="table table-hover table-condensed"><thead><tr><th>&nbsp;</th><th>Application Name</th><th>Type</th><th>Is Active</th><th>Is Locked</th><th>Modified</th><th>Modified By</th><th>&nbsp;</th></tr></thead>'+body.html()+'</table>');
                $('#tclients','#clients').remove();
                $('#clients').append(table);

            },
            error: function (jqXHR, textStatus, errorThrown) {
                ajaxError(jqXHR, textStatus, errorThrown);
            }
     });
}

jQuery(document).ready(function($){

    $('#oauth2-console','#main-menu').addClass('active');

    var application_form      = $('#form-application');
    var application_dialog    = $("#dialog-form-application");

    var application_validator = application_form.validate({
        rules: {
            "app_name"        : {required: true, free_text:true, rangelength: [1, 255]},
            "app_description" : {required: true, free_text:true,rangelength: [1, 512]},
            "website"         : {url:true}
        }
    });

    var users = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: clientsUrls.fetchUsers,
            wildcard: '%QUERY%',
            prepare: function (query, settings) {
                settings.url = clientsUrls.fetchUsers+'?filter=first_name=@'+query+',last_name=@'+query+',email=@'+query;
                return settings;
            },
            transform: function(input){
                var page = input.data;
                return page;
            }
        }
    });

    $('#admin_users').tagsinput({
        itemValue: function(item) {
            return item.id;
        },
        itemText: function(item) {
            return item.first_name + ' ' + item.last_name;
        },
        freeInput: false,
        allowDuplicates: false,
        typeaheadjs: [
            {
                highlight: true,
                minLength: 1
            },
            {
                name: 'users',
                display: function(item) {
                    return item.first_name + ' ' + item.last_name;
                },
                templates: {
                    suggestion: function (item) {
                        return '<p>' + item.first_name + ' ' + item.last_name + '</p>';
                    }
                },
                source: users,
                limit: 10
            }
        ]
    });

    application_dialog.modal({
        show:false,
        backdrop:"static"
    });

    application_dialog.on('hidden.bs.modal', function () {
        application_form.cleanForm();
        application_validator.resetForm();
        $('.add-client').removeAttr('disabled');
    })

    $("body").on('click',".add-client",function(event){
        application_dialog.modal('show');
        $('.add-client').attr('disabled','disabled');
        event.preventDefault();
        return false;
    });

    $("body").on('click',"#save-application",function(event){
        var is_valid        = application_form.valid();
        if (is_valid){
            $('#save-application').attr('disabled','disabled');
            var application         = application_form.serializeForm();
            application.user_id     = userId;
            if( application.admin_users) {
                application.admin_users = application.admin_users.split(",");
                for (var i = 0; i < application.admin_users.length; i++) {
                    application.admin_users[i] = parseInt(application.admin_users[i], 10);
                }
            }
            var link = $(this).attr('href');
            $.ajax({
                type: "POST",
                url: clientsUrls.add,
                data: JSON.stringify(application),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                timeout:60000,
                success: function (data,textStatus,jqXHR) {
                    loadClients();
                    application_dialog.modal('hide');

                    var client_id     = data.client_id;
                    var client_secret = data.client_secret;
                    var credentials_txt = "<p><strong>CLIENT ID</strong></p><p class='formatted-credential'>"+client_id+"</p>";
                    if(client_secret)
                        credentials_txt += "<p><strong>CLIENT SECRET</strong></p><p class='formatted-credential'>"+client_secret+"</p>";

                    swal({
                        title: "Your Client Credentials!",
                        html: credentials_txt,
                        type: "info",
                        customClass: "auto-width"
                    });

                    $('#save-application').removeAttr('disabled');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    ajaxError(jqXHR, textStatus, errorThrown);
                    $('#save-application').removeAttr('disabled');
                }
            });
        }
        event.preventDefault();
        return false;
    });

    $("body").on('click',".del-client",function(event){
        var url = $(this).attr('href');
        swal({
                title: "Are you sure to delete this registered application?",
                text: "This is an non reversible process!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Delete it!",
                closeOnConfirm: true
            }).then(
                function(result){
                    if(!result) return;
                    $.ajax(
                        {
                            type: "DELETE",
                            url: url,
                            contentType: "application/json; charset=utf-8",
                            dataType: "json",
                            timeout:60000,
                            success: function (data,textStatus,jqXHR) {
                                loadClients();
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                ajaxError(jqXHR, textStatus, errorThrown);
                            }
                        }
                    );
                });
        event.preventDefault();
        return false;
    });

    $("body").on('click',".app-active-checkbox",function(event){
        var active    = $(this).is(':checked');
        var client_id = $(this).attr('value');
        var url       = active? clientsUrls.activate : clientsUrls.deactivate;
        url           = url.replace('@id',client_id);
        var verb      = active?'PUT':'DELETE';

        $.ajax(
            {
                type: verb,
                url: url,
                contentType: "application/json; charset=utf-8",
                success: function (data,textStatus,jqXHR) {
                    //load data...
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    ajaxError(jqXHR, textStatus, errorThrown);
                }
            }
        );
    });
});