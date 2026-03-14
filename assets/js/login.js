jQuery(document).ready(function($){

   $('#login_form').submit(function(event){
        if(!navigator.cookieEnabled){
            event.preventDefault();
            checkCookiesEnabled();
            return false;
        }
        $('.btn-primary').attr('disabled', 'disabled');
        return true;
   });

    $(".toggle-password").click(function() {

        $(this).toggleClass("fa-eye-slash");
        var currentTitle = $(this).attr("title");
        if(currentTitle == "Show Password")
            currentTitle = "Hide Password";
        else
            currentTitle = "Show Password";
        $(this).attr("title", currentTitle);
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
            input.attr("type", "text");
        } else {
            input.attr("type", "password");
        }
    });
    
    checkCookiesEnabled();
});

function checkCookiesEnabled(){
    var cookieEnabled = navigator.cookieEnabled;

    return cookieEnabled || showCookieFail();
}

function showCookieFail(){
    $('#cookies-disabled-dialog').show();
}