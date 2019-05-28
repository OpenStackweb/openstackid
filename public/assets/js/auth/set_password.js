(function( $ ){

    $(document).ready(function($){


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

        var form = $('#form-password-set');

        var validator = form.validate({
            rules: {
                password: {
                    required: true,
                    minlength: 8
                },
                "password_confirmation" : {
                    minlength: 8,
                    equalTo : "#password"
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

    });

// End of closure.
}( jQuery ));