import Swal from "sweetalert2";

export const handleErrorResponse = (err) => {
    if(err.status === 412){
        // validation error
        let msg= '';
        for (let [key, value] of Object.entries(err.response.body.errors)) {
            if (isNaN(key)) {
                msg += key + ': ';
            }

            msg += value + '<br>';
        }
        return Swal("Validation error", msg, "warning");
    }
    return Swal("Something went wrong!", null, "error");
}

/**
 * 
 * @param {string} provider 
 * @returns a text string of either Sign in or Login depending on which provider
 */
export const handleThirdPartyProvidersVerbiage = (provider) => {
    // we can edit text if things change in the future with these providers
    let text = '';
    const signin = 'Sign in';
    const login = 'Login';
    switch(provider?.toLocaleLowerCase()) {
        case 'facebook':
            text = login;
            break;
        case 'linkedin':
            text = signin;
            break;
        case 'apple':
            text = signin;
            break;
        case 'twitter':
            text = signin;
            break;
        default: 
            text = signin;
    }
    return text;
}