(function( $ ){

    $('body').ajax_loader();

    $(document).ready(function($){

        var redirect = $('#redirect_url');

        if(redirect.length > 0){
            var href = $(redirect).attr('href');
            if(email != '')
                href = href +'#login=1&email=' + email;

            setTimeout(function(){ window.location = href; }, 3000);
            return;
        }

        setTimeout(function(){ window.location = '/accounts/user/profile'; }, 3000);

        $('body').ajax_loader('stop');
    });

// End of closure.
}( jQuery ));