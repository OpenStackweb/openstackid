jQuery(document).ready(function ($) {
});

// constructor
function BasicCrud(urls, perPage, templatePage, directivesPage, messages) {
    // settings
    this.urls = urls;
    this.perPage = perPage;
    this.templatePage = templatePage;
    this.directivesPage = directivesPage;
    this.maxItemsPerPage = 10;
    this.messages = messages;
    // state
    this.currentPage = null;
    this.searchTerm = null;
    this.orderBy = null;
    this.orderByDir = null;
}

// methods
BasicCrud.prototype = {
    init: function () {
        var _this = this;
        jQuery(document).ready(function ($) {

            // set current menu item active
            $('#server-admin', '#main-menu').addClass('active');

            $('#btn-do-search-clear').hide();

            var currentTermFromDeepLink = $(window).url_fragment('getParam', 'term');
            if (currentTermFromDeepLink != null) {
                $('#search-term').val(currentTermFromDeepLink);
                $('#btn-do-search-clear').show();
            }

            _this.loadPage();

            $("body").on('click', ".page-link", function (event) {
                _this.currentPage = $(this).attr('data-page');
                _this.loadPage();
                event.preventDefault();
                return false;
            });

            $("body").on('keydown', "#search-term", function (event) {
                if (event.keyCode === 13) {
                    $('#btn-do-search').trigger('click');
                    event.preventDefault();
                    return false;
                }
                return true;
            });

            $("body").on('click', "#btn-do-search", function (event) {
                _this.currentPage = 1;
                _this.searchTerm = $('#search-term').val()
                $('#btn-do-search-clear').show();
                _this.loadPage();
                event.preventDefault();
                return false;
            });

            $("body").on('click', "#btn-do-search-clear", function (event) {
                $('#search-term').val('');
                _this.searchTerm = '';
                $(this).hide();
                _this.loadPage();
                event.preventDefault();
                return false;
            });

            $("body").on('click', 'a.delete-item', function (event) {

                var url = $(this).attr('href');

                swal({
                    title: _this.messages['delete_item.title'],
                    text: _this.messages['delete_item.text'],
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, Delete it!",
                }).then(
                    function (result) {
                        if (result.value) {
                            $.ajax(
                                {
                                    type: "DELETE",
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
                    });
                event.preventDefault();
                return false;
            });
        });
    },
    _buildFilters: function () {

    },
    loadPage: function () {
        var _this = this;
        $('body').ajax_loader();
        var currentPageFromDeepLink = $(window).url_fragment('getParam', 'page');
        var currentTermFromDeepLink = $(window).url_fragment('getParam', 'term');

        if (this.currentPage == null && currentPageFromDeepLink != null) {
            this.currentPage = currentPageFromDeepLink;
        }

        if (this.searchTerm == null && currentTermFromDeepLink != null) {
            this.searchTerm = currentTermFromDeepLink;
        }

        if (this.currentPage == null)
            this.currentPage = 1;

        if (this.searchTerm != null)
            $(window).url_fragment('setParam', 'term', this.searchTerm);

        $(window).url_fragment('setParam', 'page', this.currentPage);

        window.location.hash = $(window).url_fragment('serialize');

        var url = this.urls.load + '?page=' + parseInt(this.currentPage) + '&per_page=' + this.perPage;

        if (this.searchTerm != null && this.searchTerm != '') {

            url += '&' + this._buildFilters();
        }

        if (this.orderBy != null && this.orderBy != '') {
            url += '&order=' + this.orderBy;
        }

        $.ajax({
            type: "GET",
            url: url,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            timeout: 60000,
            success: function (page, textStatus, jqXHR) {
                //load data...
                var items = page.data;
                if (items.length == 0) {
                    $('.label-info').show();
                    $('#table').hide();
                    $('#search-container').hide();
                    $('body').ajax_loader('stop');
                    return;
                }
                $('.label-info').hide();
                $('#table').show();
                $('#search-container').show();
                
                var body = _this.templatePage.render(items, _this.directivesPage);
                $('#body-table', '#table').remove();
                $('#table').append('<tbody id="body-table">' + body.html() + '</tbody>');

                var templatePager = $('<ul>' +
                    '<li><a href="#"></a></li>' +
                    '</ul>');

                var maxPages2Show = 20;
                var totalPages = parseInt(Math.ceil(page.total / _this.perPage));
                if (maxPages2Show > totalPages) maxPages2Show = totalPages;
                var currentPage = page.current_page;
                var currentRelativePage = currentPage % maxPages2Show;
                if (currentRelativePage == 0) currentRelativePage = maxPages2Show;
                var pages = [];

                for (var i = 1; i <= maxPages2Show; i++) {
                    pages.push({
                        nbr: i + (currentPage - currentRelativePage)
                    });
                }

                var directivesPager = {
                    'li': {
                        'i<-context': {
                            '@class': function (arg) {
                                return arg.item.nbr == page.current_page ? "active" : "";
                            },
                            'a': function (arg) {
                                return arg.item.nbr;
                            },
                            'a@class': function (arg) {
                                return "page-link"
                            },
                            'a@data-page': function (arg) {
                                return arg.item.nbr;
                            },
                        }
                    }
                };

                var pager = templatePager.render(pages, directivesPager);

                $('#pager', '#pager-container').remove();
                var prev_item = '';
                var next_item = '';
                if (pages.length > 0) {
                    prev_item = (pages[0].nbr > 1) ? '<li><a href="#" class="page-link" data-page="' + (pages[0].nbr - 1) + '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>' : '';
                    next_item = (pages[pages.length - 1].nbr < totalPages) ? '<li><a href="#" class="page-link" data-page="' + (pages[pages.length - 1].nbr + 1) + '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>' : '';
                }
                $('#pager-container').append('<ul id="pager" class="pagination">' + prev_item + pager.html() + next_item + '</ul>');

                $('body').ajax_loader('stop');

            },
            error: function (jqXHR, textStatus, errorThrown) {
                $('body').ajax_loader('stop');
                ajaxError(jqXHR, textStatus, errorThrown);
            }
        });
    }
};
