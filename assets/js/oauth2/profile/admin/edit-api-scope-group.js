$(document).ready(function() {

    // scopes

    var scopes = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: ApiScopeGroupUrls.fetchScopes,
            wildcard: '%QUERY%',
            prepare: function (query, settings) {
                settings.url = ApiScopeGroupUrls.fetchScopes+'?filter[]=name=@'+query+'&filter[]=is_assigned_by_groups==1';
                return settings;
            },
            transform: function(input){
                var page = input.data;
                return page;
            }
        }
    });

    $('#scopes').tagsinput({
        itemValue: function(item) {
            return item.id;
        },
        itemText: function(item) {
            return item.name
        },
        freeInput: false,
        allowDuplicates: false,
        typeaheadjs: [
            {
                highlight: true,
                minLength: 1
            },
            {
                name: 'scopes',
                display: function(item) {
                    return item.name;
                },
                templates: {
                    suggestion: function (item) {
                        return '<p>' + item.name + '</p>';
                    }
                },
                source: scopes,
                limit: 10
            }
        ]
    });

    for(var scope of current_scopes)
    {
        $('#scopes').tagsinput('add', scope);
    }

    // users

    var users = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: ApiScopeGroupUrls.fetchUsers,
            wildcard: '%QUERY%',
            prepare: function (query, settings) {
                settings.url = ApiScopeGroupUrls.fetchUsers+'?filter=first_name=@'+query+',last_name=@'+query+',email=@'+query;
                return settings;
            },
            transform: function(input){
                var page = input.data;
                return page;
            }
        }
    });

    $('#users').tagsinput({
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


    for(var user of current_users)
    {
        $('#users').tagsinput('add',user);
    }

    var group_form      = $('#api-scope-group-form');
    var group_validator = group_form.validate({
        rules: {
            "name"   : { required: true, free_text:true,rangelength: [1, 255]},
            "users"  : { required: true },
            "scopes" : { required: true },
        }
    });

    group_form.submit(function( event ) {

        var is_valid = group_form.valid();

        if (is_valid){
            group_validator.resetForm();
            var group = group_form.serializeForm();
            var href = $(this).attr('action');
            $.ajax(
                {
                    type: "PUT",
                    url: href,
                    data: JSON.stringify(group),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    timeout:60000,
                    success: function (data,textStatus,jqXHR) {
                        displaySuccessMessage('Group Saved!.' , group_form);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        ajaxError(jqXHR, textStatus, errorThrown);
                    }
                }
            );
        }
        event.preventDefault();
        return false;
    });

});