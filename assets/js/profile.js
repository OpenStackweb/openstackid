(function ($) {

    $('body').ajax_loader();

    $(document).ready(function ($) {


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
                "first_name": { required: true},
                "last_name": { required: true},
                "identifier": { required: true},
                "email": { required: true, custom_email: true},
                "second_email": { custom_email: true},
                "third_email": { custom_email: true},
            }
        });

        //$('#bio').summernote();
        //$('#statement_of_interest').summernote();

        var simplemdeBio = new SimpleMDE({element: $("#bio")[0]});
        var simplemdeStatement = new SimpleMDE({element: $("#statement_of_interest")[0]});

        $('#birthday').datepicker();

        $('#img-pic', form).data('original-src', $('#img-pic', form).attr('src'));

        $('#country_iso_code').chosen({width: '100%', height: '34px'});
        $("#country_iso_code").val(current_country);
        $("#country_iso_code").trigger("chosen:updated");

        $('#country_iso_code', form).change(function () {
            validator.resetForm();
        });

        $('#language').chosen({width: '100%', height: '34px'});
        $('#language', form).change(function () {
            validator.resetForm();
        });

        $("#language").val(current_language);
        $("#language").trigger("chosen:updated");

        if (current_gender != '') {
            $("#gender").val(current_gender);
            if (current_gender == 'Specify') {
                $('#gender_specify').removeClass('hide').fadeIn();
            } else {
                $('#gender_specify').fadeOut();
                $('#gender_specify').val('');
            }
        }

        $('#gender', form).change(function () {
            var value = $(this).val();
            if (value == 'Specify') {
                $('#gender_specify').removeClass('hide').fadeIn();
            } else {
                $('#gender_specify').fadeOut();
                $('#gender_specify').val('');
            }
        });

        form.submit(function (event) {
            var is_valid = validator.valid();
            if (is_valid) {
                $('body').ajax_loader();
                validator.resetForm();
                var user = form.serializeForm();
                var birthday = user.birthday;
                delete user.birthday;
                if (typeof birthday != "undefined" && birthday != '') {
                    user.birthday = moment(birthday).unix();
                } else {
                    user.birthday = '';
                }

                // get values
                user.bio = simplemdeBio.value();
                user.statement_of_interest = simplemdeStatement.value();

                var href = $(this).attr('action');
                var data = new FormData();

                data.append('user', JSON.stringify(user));

                if ($('#pic', form)[0].files.length > 0)
                    data.append('pic', $('#pic', form)[0].files[0]);

                $.ajax(
                    {
                        type: "PUT",
                        url: href,
                        data: data,
                        cache: false,
                        contentType: false,
                        processData: false,
                        timeout: 60000,
                        success: function (data, textStatus, jqXHR) {
                            $('body').ajax_loader('stop');
                            swal({
                                title: "Success!",
                                type: "success",
                                text: "User info updated successfully!",
                            });
                            // reset password form
                            $("#password_container").hide();
                            $("#current_password").val('');
                            $("#password").val('');
                            $("#current_password").val('');
                            $('.change-password-link').show();
                            location.reload(true);
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

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#img-pic').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function fileValidation(input) {

            var filePath = input.value;

            // Allowing file type
            var allowedExtensions =
                /(\.png|\.jpeg|\.jpg)$/i;

            if (!allowedExtensions.exec(filePath)) {
                swal({
                    title: "Validation Error",
                    type: "warning",
                    text: "Invalid file type",
                });
                var imgSrc = $('#img-pic', form).data('original-src');
                $(input).val('');
                $('#img-pic', form).attr('src', imgSrc);
                return false;
            }
            return true;
        }

        $("#pic", form).change(function (evt) {
            if (fileValidation(this)) {
                readURL(this);
                return true;
            }
            evt.preventDefault();
            return false;
        });

        $("#password_container").hide();

        $("body").on("click", ".change-password-link", function (event) {
            $(this).hide();
            $("#password_container").show();
            event.preventDefault();
            return false;
        });

        $('#sidebar').removeClass('hide');

        $('body').ajax_loader('stop');
    });


// End of closure.
}(jQuery));