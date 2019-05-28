(function( $ ){

    $(document).ready(function($){

        var redirect = $('#redirect_url');
        if(redirect.length > 0){
            var href = $(redirect).attr('href');
            setTimeout(function(){ window.location = href; }, 3000);
        }

    });

// End of closure.
}( jQuery ));