$(document).ready(function() {

    $('body').ajax_loader();

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

    var form = $('#user-form');

    var validator = form.validate({
        rules: {
            "first_name"   : {required: true},
            "last_name"    : {required: true},
            "identifier"   : {required: true},
            "email"        : {required: true, email: true},
            "second_email" : {email: true},
            "third_email"  : {email: true},
        }
    });

    $('#bio').summernote();
    $('#statement_of_interest').summernote();
    $('#birthday').datepicker();

    $('#country_iso_code').chosen({width: '100%', height: '34px'});
    $("#country_iso_code").val(current_country);
    $("#country_iso_code").trigger("chosen:updated");

    $('#country_iso_code',form).change(function () {
        validator.resetForm();
    });

    $('#language').chosen({width: '100%', height: '34px'});
    $('#language',form).change(function () {
        validator.resetForm();
    });

    $("#language").val(current_language);
    $("#language").trigger("chosen:updated");

    if(current_gender != '') {
        $("#gender").val(current_gender);
        if(current_gender == 'Specify'){
            $('#gender_specify').removeClass('hide').fadeIn();
        } else {
            $('#gender_specify').fadeOut();
            $('#gender_specify').val('');
        }
    }

    $('#gender', form).change(function () {
        var value = $(this).val();
        if(value == 'Specify'){
            $('#gender_specify').removeClass('hide').fadeIn();
        } else {
            $('#gender_specify').fadeOut();
            $('#gender_specify').val('');
        }
    });

    form.submit(function( event ) {
        var is_valid = validator.valid();
        if (is_valid){
            $('body').ajax_loader();
            validator.resetForm();
            var user     = form.serializeForm();
            var birthday = user.birthday;
            delete user.birthday;
            if(typeof birthday != "undefined" && birthday != '') {
                user.birthday = moment(birthday).unix();
            }
            else
            {
                user.birthday = '';
            }

            var href = $(this).attr('action');

            $.ajax(
                {
                    type: "PUT",
                    url: href,
                    data: JSON.stringify(user),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    timeout:60000,
                    success: function (data,textStatus,jqXHR) {
                        $('body').ajax_loader('stop');
                        swal({
                            title: "Success!",
                            type: "success",
                            text: "User info updated successfully!",
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

    $("#password_container").hide();

    $("body").on("click", ".change-password-link", function(event){
        $(this).hide();
        $("#password_container").show();
        event.preventDefault();
        return false;
    });
    $('#sidebar').removeClass('hide');
    $('body').ajax_loader('stop');
});