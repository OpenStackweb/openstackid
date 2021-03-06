jQuery(document).ready(function($){

    var form = $("#form-application-security");

    $.validator.addMethod("must_use_alg", function(value, element, options) {
        if(value === 'none') return true;
        return $(options.alg_element_id).val() !== 'none';
    },"You must select an Encrypted Key Algorithm");

    $.validator.addMethod("must_use_enc", function(value, element, options) {
        if(value === 'none') return true;
        return $(options.enc_element_id).val() !== 'none';
    },"You must select an Encrypted Content Algorithm");


    var validator = form.validate({
        rules: {
            "default_max_age"                 : {integer : true},
            "jwks_uri"                        : {ssl_uri : true},
            "userinfo_encrypted_response_enc" : { must_use_alg: {alg_element_id:'#userinfo_encrypted_response_alg'}},
            "id_token_encrypted_response_enc" : { must_use_alg: {alg_element_id:'#id_token_encrypted_response_alg'}},
            "userinfo_encrypted_response_alg" : { must_use_enc: {enc_element_id:'#userinfo_encrypted_response_enc'}},
            "id_token_encrypted_response_alg" : { must_use_enc: {enc_element_id:'#id_token_encrypted_response_enc'}}
        }
    });

    if($("#otp_enabled") .is(":checked")){
        $(".otp_controls").removeClass("hidden");
        $("#otp_length").rules("add", {required:true, min:4, max:8});
        $("#otp_lifetime").rules("add", {required:true, min:60, max:600});
    }
    else {
        $(".otp_controls").addClass("hidden");
        $("#otp_length").rules("remove");
        $("#otp_lifetime").rules("remove");
    }


    $("#otp_enabled").change(function() {
        if(this.checked) {
           $(".otp_controls").removeClass("hidden");
           $("#otp_length").rules("add", {required:true, min:4, max:8});
           $("#otp_lifetime").rules("add", {required:true, min:60, max:600});
           return true;
        }
        $(".otp_controls").addClass("hidden");
        $("#otp_length").rules("remove");
        $("#otp_lifetime").rules("remove");
        return true;
    });

    $('#token_endpoint_auth_method').change(function() {
        var auth_method = $(this).val();

        if(auth_method === 'private_key_jwt' || auth_method === 'client_secret_jwt')
        {
            var signing_alg_select = $('#token_endpoint_auth_signing_alg')
            $('#token_endpoint_auth_signing_alg_group').show();
            signing_alg_select.empty();
            var result = [];

            if(auth_method === 'private_key_jwt')
            {
                result = oauth2_supported_algorithms.sig_algorihtms.rsa;
            }
            else
            {
                result = oauth2_supported_algorithms.sig_algorihtms.mac;
            }

            $.each(result, function(index, item) {
                var key = item === 'none' ? '' : item;
                signing_alg_select.append($("<option />").val(key).text(item));
            });
        }
        else
        {
            $('#token_endpoint_auth_signing_alg_group').hide();
        }
    });

    $('#token_endpoint_auth_method').trigger('change');

    form.submit(function(e){
        var is_valid = $(this).valid();
        if (is_valid) {
            $('#save-application-security').attr('disabled','disabled');
            var application_data = $(this).serializeForm();

            $.ajax(
                {
                    type: "PUT",
                    url: dataClientUrls.update,
                    data: JSON.stringify(application_data),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    timeout: 60000,
                    success: function (data, textStatus, jqXHR) {
                        $('#save-application-security').removeAttr('disabled');
                        displaySuccessMessage('Data saved successfully.', form);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        $('#save-application-security').removeAttr('disabled');
                        ajaxError(jqXHR, textStatus, errorThrown);
                    }
                }
            );
        }
        e.preventDefault();
        return false;
    });

});
