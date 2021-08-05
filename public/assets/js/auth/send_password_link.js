(function( $ ){

    $('body').ajax_loader();

    $(document).ready(function($){

        var form = $('#form-send-password-reset-link');

        var validator = form.validate({
            rules: {
                email: {
                    required: true
                },
                'g_recaptcha_hidden': {required: true}
            },
            messages: {
                'g_recaptcha_hidden': { required: 'Please confirm that you are not a robot.'}
            }
        });

        form.submit(function(e){
            var is_valid = $(this).valid();
            if (!is_valid) {
                e.preventDefault();
                return false;
            }
            $('.btn-primary').attr('disabled', 'disabled');
            return true;
        });

        $(document).ready(function($){

            var redirect = $('#redirect_url');
            if(redirect.length > 0){
                var href = $(redirect).attr('href');
                setTimeout(function(){ window.location = href; }, 3000);
            }

        });

        $('body').ajax_loader('stop');

    });

// End of closure.
}( jQuery ));