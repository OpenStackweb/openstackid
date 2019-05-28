// constructor
function GroupMembersCrud(urls, perPage) {
    var actions = '<a class="btn btn-default btn-md active delete-item">Remove</a>';
    this.urls = urls;
    var _this = this;
    var templatePage = $('<tbody><tr>' +
        '<td class="user-id"></td>' +
        '<td class="user-fullname"></td>' +
        '<td class="user-email"></td>' +
        '<td class="user-actions">&nbsp;' + actions + '</td>' +
        '</tr></tbody>');

    var directivesPage = {
        'tr': {
            'user<-context': {
                'td.user-id': 'user.id',
                'td.user-fullname': function(arg){
                    return arg.item.first_name+' '+arg.item.last_name;
                },
                'td.user-email': 'user.email',
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
        'delete_item.title': 'Are you sure to Remove this User from Group?',
        'delete_item.text': 'This is an non reversible process!',
    });
}

GroupMembersCrud.prototype = Object.create(BasicCrud.prototype);
GroupMembersCrud.prototype.constructor = GroupMembersCrud;

GroupMembersCrud.prototype._buildFilters = function () {
    var term = encodeURIComponent(this.searchTerm);
    return 'filter=first_name=@'+term+',last_name=@'+term+',email=@'+term;
};

GroupMembersCrud.prototype.init = function () {
    BasicCrud.prototype.init.call(this);
    var _this = this;
    jQuery(document).ready(function ($) {

        var users = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: urls.fetchUsers,
                wildcard: '%QUERY%',
                prepare: function (query, settings) {
                    settings.url = urls.fetchUsers+'?filter=first_name=@'+query+',last_name=@'+query+',email=@'+query;
                    return settings;
                },
                transform: function(input){
                    var page = input.data;
                    return page;
                }
            }
        });

        $("body").on('click', "#btn-add-user", function (event) {
            var newUserId = $('#add-user').val();
            if(newUserId != '') {
                $('#add-user').val('');
                $('#add-user').tagsinput('removeAll');
                handlePlaceHolder();
                var url = _this.urls.add.replace('@id', newUserId);
                $.ajax(
                    {
                        type: "PUT",
                        url: url,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        timeout: 60000,
                        success: function (data, textStatus, jqXHR) {
                            _this.loadPage();

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


        $('#add-user').tagsinput({
            maxTags: 1,
            itemValue: function(item) {
                return item.id;
            },
            itemText: function(item) {
                return item.first_name + ' ' + item.last_name+' ('+item.email+')' ;
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
                        return item.first_name + ' ' + item.last_name+' ('+item.email+')' ;
                    },
                    templates: {
                        suggestion: function (item) {
                            return '<p>' + item.first_name + ' ' + item.last_name +' ('+item.email+') </p>';
                        }
                    },
                    source: users,
                    limit: 10
                }
            ]
        });

        function handlePlaceHolder()
        {
            if($('#add-user').val())
            {
                $('.bootstrap-tagsinput input').attr('placeholder', '');
            }
            else
            {
                $('.bootstrap-tagsinput input').attr('placeholder',$('#add-user').attr('data-placeholder'));
            }
        }

        $('#add-user').on('itemRemoved', function(event) {
            // event.item: contains the item
            handlePlaceHolder();
        });

        $('#add-user').on('itemAdded', function(event) {
            // event.item: contains the item
            handlePlaceHolder();
        });

        var form = $('#group-form');
        var validator = form.validate({
            rules: {
                "name": {required: true, free_text: true, rangelength: [1, 512]},
                "slug": {required: true, free_text: true, rangelength: [1, 255]},
            }
        });

        form.submit(function( event ) {
            var is_valid = validator.valid();
            if (is_valid){
                $('body').ajax_loader();
                validator.resetForm();
                var group = form.serializeForm();
                var href  = $(this).attr('action');

                $.ajax(
                    {
                        type: "PUT",
                        url: href,
                        data: JSON.stringify(group),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        timeout:60000,
                        success: function (data,textStatus,jqXHR) {
                            $('body').ajax_loader('stop');
                            swal({
                                title: "Success!",
                                type: "success",
                                text: "Group info updated successfully!",
                            });

                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            $('body').ajax_loader('stop');
                            ajaxError(jqXHR, textStatus, errorThrown);
                        }
                    }
                );
            }
            event.preventDefault();
            return false;
        });
    });
};