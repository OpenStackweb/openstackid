(function( $ ){
    $('body').ajax_loader();

    $(document).ready(function($){

        var redirect = $('#redirect_url');
        if(redirect.length > 0){
            var href = $(redirect).attr('href');
            setTimeout(function(){ window.location = href; }, 10000);
        }

        $('body').ajax_loader('stop');
    });

// End of closure.
}( jQuery ));