
var regex_ipv4 = /^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/i;

var regex_ipv6 = /^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/i;

jQuery.validator.addMethod("ipV4", function(value, element) {
    return this.optional(element) || regex_ipv4.test(value);
}, "Please enter a valid IPV4 address");

jQuery.validator.addMethod("ipv4V6Set", function(value, element) {
   var ips = value.split(',');
   for(var ip of ips)
   {
       var valid  = regex_ipv4.test(ip);
       if(!valid)
           valid  = regex_ipv6.test(ip);
       if(!valid) return false;
   }
   return true
}, "Please enter a valid IP (V4/V6) address");

jQuery.validator.addMethod("ssl_uri", function(value, element) {
    var regex = /^https:\/\/([\w@][\w.:@]+)\/?[\w\.?=%&=\-@/$,]*$/ig;
    return this.optional(element) || regex.test(value);
}, "Uri must under https schema.");


jQuery.validator.addMethod("scopename", function(value, element) {
    var regex = /^[a-zA-Z0-9\-\.\,\:\_\/]+$/ig;
    return this.optional(element) || regex.test(value);
}, "Please enter a valid scope name.");

jQuery.validator.addMethod("free_text", function(value, element) {
    return this.optional(element) || /^[a-zA-Z0-9\-_.,()'"\s\:\/]+$/i.test(value);
}, "Letters or punctuation only please");

jQuery.validator.addMethod("endpointroute", function(value, element) {
    return this.optional(element) || /^\/([\w@][\w.:@]+)\/?[\w\.?=%&=\-@/$,{}]*$/ig.test(value);
}, "Please enter a valid endpoint route");

jQuery.validator.addMethod("pem_public_key", function(value, element, options) {
    if(!options) return true;
    var res1 = value.indexOf('-----BEGIN PUBLIC KEY-----');
    var res2 = value.indexOf('-----BEGIN RSA PUBLIC KEY-----');
    var res3 = value.indexOf('-----END PUBLIC KEY-----');
    var res4 = value.indexOf('-----END RSA PUBLIC KEY-----');
    var pck1 = (res1 != -1 && res3 != -1);
    var pck8 = (res2 != -1 && res4 != -1);
    return pck1 || pck8;
}, "Please enter a valid PEM Public Key format(PCK#8/PCK#1)");

jQuery.validator.addMethod("pem_private_key", function(value, element, options) {
    if(!options) return true;
    var res1 = value.indexOf('-----BEGIN PRIVATE KEY-----');
    var res2 = value.indexOf('-----BEGIN RSA PRIVATE KEY-----');
    var res3 = value.indexOf('-----END PRIVATE KEY-----');
    var res4 = value.indexOf('-----END RSA PRIVATE KEY-----');
    var pck1 = (res1 != -1 && res3 != -1);
    var pck8 = (res2 != -1 && res4 != -1);
    return pck1 || pck8;
}, "Please enter a valid PEM Private Key format(PCK#8/PCK#1)");

jQuery.validator.addMethod("private_key_password_required", function(value, element, options) {
    var private_key_pem = $(options.pem_content_id).val();
    return !(private_key_pem.indexOf('ENCRYPTED') != -1 && value == '');
}, "Please enter a password for Private Key (PCK#8/PCK#1)");

$.validator.addMethod("dateUS", function (value, element,options) {
    //mm/dd/yyyy
    var check = false,
        regex = /^\d{1,2}\/\d{1,2}\/\d{4}$/,
        adata, gg, mm, aaaa, xdata;
    if ( regex.test(value)) {
        adata = value.split("/");
        mm   = parseInt(adata[0], 10);
        gg   = parseInt(adata[1], 10);
        aaaa = parseInt(adata[2], 10);
        xdata = new Date(Date.UTC(aaaa, mm - 1, gg, 12, 0, 0, 0));
        if ( ( xdata.getUTCFullYear() === aaaa ) && ( xdata.getUTCMonth () === mm - 1 ) && ( xdata.getUTCDate() === gg ) ) {
            check = true;
        } else {
            check = false;
        }
    } else {
        check = false;
    }
    return this.optional(element) || check;
}, "Please enter a valid date.");

$.validator.addMethod("custom_email", function(value, element) {
    if(!value) return true;
    value = value.trim();
    if(value == '') return true;
    return /^\S+@\S+(\.\S+)*$/.test( value )
}, "field should be a valid email.");

// override jquery validate plugin defaults
$.validator.setDefaults({
    ignore: [],
    highlight: function(element) {
        $(element).closest('.form-group').addClass('has-error');
    },
    unhighlight: function(element) {
        $(element).closest('.form-group').removeClass('has-error');
    },
    errorElement: 'span',
    errorClass: 'help-block',
    errorPlacement: function(error, element) {
        if(element.parent('.input-group').length) {
            error.insertAfter(element.parent());
        } else {
            error.insertAfter(element);
        }
    }
});