// constructor
function UsersCrud(urls, perPage) {
    var actions = '<a class="btn btn-default btn-md active edit-item">Edit</a>' +
        '&nbsp;<a class="btn btn-default btn-md active delete-item">Delete</a>';
    this.urls = urls;
    var _this = this;
    var templatePage = $('<tbody><tr>' +
        '<td class="user-identifier"></td>' +
        '<td class="user-fname"></td>' +
        '<td class="user-lname"></td>' +
        '<td class="user-email"></td>' +
        '<td class="user-active"><input type="checkbox" class="user-active-checkbox"></td>' +
        '<td class="user-last-login"></td>' +
        '<td class="user-spam-type"></td>' +
        '<td class="user-actions">&nbsp;' + actions + '</td>' +
        '</tr></tbody>');

    var directivesPage = {
        'tr': {
            'user<-context': {
                'td.user-identifier': 'user.identifier',
                'td.user-fname': 'user.first_name',
                'td.user-lname': 'user.last_name',
                'td.user-email': 'user.email',
                'td.user-spam-type': 'user.spam_type',
                'td.user-last-login': function (arg) {
                    if (arg.item.last_login_date == null) return 'N/A';
                    return moment.unix(arg.item.last_login_date).format();
                },
                '.user-active-checkbox@value': 'user.id',
                '.user-active-checkbox@checked': function (arg) {
                    return arg.item.active ? 'true' : '';
                },
                '.user-active-checkbox@id': function (arg) {
                    var user_id = arg.item.id;
                    return 'user-active_' + user_id;
                },
                'a.edit-item@href': function (arg) {
                    var id = arg.item.id;
                    var href = _this.urls.edit;
                    return href.replace('@id', id);
                },
                'a.delete-item@href': function (arg) {
                    var id = arg.item.id;
                    var href = _this.urls.delete;
                    return href.replace('@id', id);
                },
            }
        }
    };

    // Chain constructor with call
    BasicCrud.call(this, urls, perPage, templatePage, directivesPage, {
        'delete_item.title': 'Are you sure to delete this registered User?',
        'delete_item.text': 'This is an non reversible process!',
    });
}

UsersCrud.prototype = Object.create(BasicCrud.prototype);
UsersCrud.prototype.constructor = UsersCrud;

UsersCrud.prototype._buildFilters = function () {
    var term = encodeURIComponent(this.searchTerm);
    return 'filter=first_name=@'+term+',last_name=@'+term+',email=@'+term;
};

UsersCrud.prototype.init = function () {
    // Chain parent method
    BasicCrud.prototype.init.call(this);
    var _this = this;

    $("body").on('click', ".user-active-checkbox", function (event) {
        var active = $(this).is(':checked');
        var userId = $(this).attr('value');
        var url = active ? _this.urls.unlock : _this.urls.lock;
        url = url.replace('@id', userId);
        var verb = active ? 'PUT' : 'DELETE';

        $.ajax(
            {
                type: verb,
                url: url,
                contentType: "application/json; charset=utf-8",
                success: function (data, textStatus, jqXHR) {
                    _this.loadPage();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    ajaxError(jqXHR, textStatus, errorThrown);
                }
            }
        );
    });

    var options = {};

    options.ui =
        {
            showPopover: false,
            showErrors: true,
            showProgressBar: true,
            showVerdictsInsideProgressBar: true,
        };

    options.rules =
        {
            activated: {
                wordTwoCharacterClasses: true,
                wordRepetitions: true
            }
        };

    $(':password').pwstrength(options);

    var groups = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: _this.urls.fetchGroups,
            wildcard: '%QUERY%',
            prepare: function (query, settings) {
                settings.url = _this.urls.fetchGroups+'?filter[]=name=@'+query+'&filter[]=active==1';
                return settings;
            },
            transform: function(input){
                var page = input.data;
                return page;
            }
        }
    });

    $('#groups').tagsinput({
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
                name: 'groups',
                display: function(item) {
                    return item.name;
                },
                templates: {
                    suggestion: function (item) {
                        return '<p>' + item.name + '</p>';
                    }
                },
                source: groups,
                limit: 10
            }
        ]
    });

    var add_item_form   = $('#form-add-item');
    var add_item_dialog = $("#dialog-form-add-item");

    var validator_add_item = add_item_form.validate({
        rules: {
            "first_name": {required: true, free_text: true, rangelength: [1, 255]},
            "last_name": {required: true, free_text: true, rangelength: [1, 512]},
            "email": {required: true, email: true},
            password: {
                required: true,
                minlength: 8
            },
            "password_confirmation": {
                minlength: 8,
                equalTo: "#password"
            },
        }
    });

    add_item_dialog.modal({
        show: false,
        backdrop: "static"
    });

    add_item_dialog.on('hidden.bs.modal', function () {
        add_item_form.cleanForm();
        validator_add_item.resetForm();
        $('.add-item-button').removeAttr('disabled');
    })

    $("body").on('click', ".add-item-button", function (event) {
        add_item_dialog.modal('show');
        $('.add-item-button').attr('disabled', 'disabled');
        event.preventDefault();
        return false;
    });

    $("body").on('click',"#save-item", function(event){
        var is_valid        = add_item_form.valid();
        if (is_valid){
            var url = $(this).attr('href');
            $('#save-item').attr('disabled','disabled');
            var user = add_item_form.serializeForm();
            var groups = user.groups;
            delete user.groups;

            if(typeof groups != "undefined" && groups != '') {
                user.groups = groups.split(",");
                for (var i = 0; i < user.groups.length; i++) {
                    user.groups[i] = parseInt(user.groups[i], 10);
                }
            }

            $.ajax({
                type: "POST",
                url: _this.urls.add,
                data: JSON.stringify(user),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                timeout:60000,
                success: function (data,textStatus,jqXHR) {
                    _this.loadPage();
                    add_item_dialog.modal('hide');
                    $('#save-item').removeAttr('disabled');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    ajaxError(jqXHR, textStatus, errorThrown);
                    $('#save-item').removeAttr('disabled');
                }
            });
        }
        event.preventDefault();
        return false;
    });

};
