(function( $ ){

    $('body').ajax_loader();

    $(document).ready(function($){

        $('.glyphicon-info-sign').popover({html:true,placement:'bottom'});

        $(':not(#anything)').on('click', function (e) {
            $('.glyphicon-info-sign').each(function () {
                //the 'is' for buttons that trigger popups
                //the 'has' for icons and other elements within a button that triggers a popup
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                    $(this).popover('hide');
                    return;
                }
            });
        });

        $("body").on('click',"#cancel-authorization",function(event){
            var $form = $('#authorization_form');
            $('#trust').attr('value','DenyOnce');
            $form.submit();
            event.preventDefault();
            return false;
        });

        $("body").on('click',"#approve-authorization",function(event){
            var $form = $('#authorization_form');
            $('#trust').attr('value','AllowOnce');
            $form.submit();
            event.preventDefault();
            return false;
        });

        $('#authorization_form').submit(function(){
            $('.btn-consent-action').attr('disabled', 'disabled');
            return true;
        });

        $('body').ajax_loader('stop');
    });

// End of closure.
}( jQuery ));