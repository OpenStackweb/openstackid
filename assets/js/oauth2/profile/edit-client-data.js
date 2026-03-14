jQuery(document).ready(function($){

    $('#contacts').tagsinput({
        trimValue: true,
        onTagExists: function(item, $tag) {
            $tag.hide().fadeIn();
        },
        allowDuplicates: false
    });

    $('#contacts').on('beforeItemAdd', function(event) {
        // event.item: contains the item
        // event.cancel: set to true to prevent the item getting added
        var regex_email = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/ig;
        var current     = regex_email.test( event.item );
        if(!current)
            event.cancel = true;
    });

    var users = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: dataClientUrls.fetchUsers,
            wildcard: '%QUERY%',
            prepare: function (query, settings) {
                settings.url = dataClientUrls.fetchUsers+'?filter=first_name=@'+query+',last_name=@'+query+',email=@'+query;
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

    for(var user of current_admin_users)
    {
        $('#admin_users').tagsinput('add',user);
    }

    $('#redirect_uris').tagsinput({
        trimValue: true,
        onTagExists: function(item, $tag) {
            $tag.hide().fadeIn();
        },
        allowDuplicates: false
    });

    $('#redirect_uris').on('beforeItemAdd', function(event) {
        var uri       = new URI(event.item);
        var app_type  = $('#application_type').val();
        var valid     = app_type == 'NATIVE' ? true : uri.protocol() === 'https' ;
        var valid     = valid && uri.is('url') && uri.is('absolute') && uri.search() == '' && uri.fragment() == ''
        if(!valid)
            event.cancel = true;
    });

    $('#allowed_origins').tagsinput({
        trimValue: true,
        onTagExists: function(item, $tag) {
            $tag.hide().fadeIn();
        },
        allowDuplicates: false
    });

    $('#allowed_origins').on('beforeItemAdd', function(event) {

        var uri       = new URI(event.item);
        var valid     = uri.is('url') && uri.is('absolute') && uri.protocol() === 'https' && uri.search() == '' && uri.fragment() == '' ;
        if(!valid)
            event.cancel = true;
    });

    $("body").on('click',".regenerate-client-secret",function(event){
        var link = $(this).attr('href');
        swal({
            title: "Are you sure?",
            text: "Regenerating client secret would invalidate all current tokens!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, Regenerate it!",
            closeOnConfirm: true
        }).then(
        function(result){
            if(!result) return;
            $.ajax(
                {
                    type: "PUT",
                    url: link,
                    dataType: "json",
                    timeout:60000,
                    success: function (data,textStatus,jqXHR) {
                        //load data...
                        $('#client_secret > :input').val(data.client_secret);
                        //$('#client_secret_expiration_date').text(data.new_expiration_date.date);
                        //clean token UI
                        $('#table-access-tokens').remove();
                        $('#table-refresh-tokens').remove();
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

    $("body").on('click',"#use-refresh-token",function(event){
        var use_refresh_token  = $(this).is(':checked');
        $.ajax(
            {
                type: "PUT",
                url: decodeURI(dataClientUrls.refresh).replace('@use_refresh_token', use_refresh_token),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                timeout:60000,
                success: function (data,textStatus,jqXHR) {
                    //load data...
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    ajaxError(jqXHR, textStatus, errorThrown);
                }
            }
        );
    });

    $("body").on('click',"#use-rotate-refresh-token-policy",function(event){
        var rotate_refresh_token  = $(this).is(':checked');
        $.ajax(
            {
                type: "PUT",
                url: decodeURI(dataClientUrls.rotate).replace('@rotate_refresh_token', rotate_refresh_token),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                timeout:60000,
                success: function (data,textStatus,jqXHR) {
                    //load data...
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    ajaxError(jqXHR, textStatus, errorThrown);
                }
            }
        );
    });

    var form = $('#form-application-main-data');

    var validator = form.validate({
        rules: {
            app_name : {required: true, free_text:true, rangelength: [1, 255]},
            app_description : {required: true, free_text:true,rangelength: [1, 512]},
            website: {url: true},
            logo_uri: {url: true},
            tos_uri: {url: true},
            policy_uri: {url: true},
        }
    });

    form.submit(function(e){
        var is_valid = $(this).valid();
        if (is_valid) {
            $('.btn-save-client-data').attr('disabled','disabled');
            var application = $(this).serializeForm();
            if(is_mine) {
                var admin_users = application.admin_users;
                delete application.admin_users;
                if (admin_users != '') {
                    admin_users = admin_users.split(",");
                    for (var i = 0; i < admin_users.length; i++) {
                        admin_users[i] = parseInt(admin_users[i], 10);
                    }
                    application.admin_users = admin_users;
                }
            }
            $.ajax(
                {
                    type: "PUT",
                    url: dataClientUrls.update,
                    data: JSON.stringify(application),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    timeout: 60000,
                    success: function (data, textStatus, jqXHR) {
                        $('.btn-save-client-data').removeAttr('disabled');
                        displaySuccessMessage('Data saved successfully.', form);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        $('.btn-save-client-data').removeAttr('disabled');
                        ajaxError(jqXHR, textStatus, errorThrown);
                    }
                }
            );
        }
        e.preventDefault();
        return false;
    });

});