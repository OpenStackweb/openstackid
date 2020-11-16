// constructor
function GroupsCrud(urls, perPage) {
    var actions = '<a class="btn btn-default btn-md active edit-item">Edit</a>' +
        '&nbsp;<a class="btn btn-default btn-md active delete-item">Delete</a>';
    this.urls = urls;
    var _this = this;
    var templatePage = $('<tbody><tr>' +
        '<td class="group-identifier"></td>' +
        '<td class="group-name"></td>' +
        '<td class="group-slug"></td>' +
        '<td class="group-active"><input type="checkbox" class="group-active-checkbox"></td>' +
        '<td class="group-actions">&nbsp;' + actions + '</td>' +
        '</tr></tbody>');

    var directivesPage = {
        'tr': {
            'group<-context': {
                'td.group-identifier': 'group.id',
                'td.group-name': 'group.name',
                'td.group-slug': 'group.slug',
                '.group-active-checkbox@value': 'group.id',
                '.group-active-checkbox@checked': function (arg) {
                    return arg.item.active ? 'true' : '';
                },
                '.group-active-checkbox@id': function (arg) {
                    return 'group-active_' + arg.item.id;
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
        'delete_item.title': 'Are you sure to delete this User Group?',
        'delete_item.text': 'This is an non reversible process!',
    });
}

GroupsCrud.prototype = Object.create(BasicCrud.prototype);
GroupsCrud.prototype.constructor = GroupsCrud;

GroupsCrud.prototype._buildFilters = function () {
    var term = encodeURIComponent(this.searchTerm);
    return 'filter=name=@'+term+',slug=@'+term;
};

GroupsCrud.prototype.init = function () {
    BasicCrud.prototype.init.call(this);
    var _this = this;
    // default sort
    this.orderBy= encodeURI('+name');

    var add_item_form   = $('#form-add-item');
    var add_item_dialog = $("#dialog-form-add-item");

    var validator_add_item = add_item_form.validate({
        rules: {
            "name": {required: true, free_text: true, rangelength: [1, 512]},
            "slug": {required: true, free_text: true, rangelength: [1, 255]},
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
            var group = add_item_form.serializeForm();

            $.ajax({
                type: "POST",
                url: _this.urls.add,
                data: JSON.stringify(group),
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