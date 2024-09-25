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
    switch(provider?.toLowerCase()) {
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

export const formatTime = (timeInSeconds) => {
    let res = ''

    const days = Math.floor(timeInSeconds / (24 * 3600));
    timeInSeconds %= (24 * 3600);
    const hours = Math.floor(timeInSeconds / 3600);
    timeInSeconds %= 3600;
    const minutes = Math.floor(timeInSeconds / 60);
    timeInSeconds %= 60;

    if (days > 0) res += `${days} day${(days > 1 ? 's' : '')}, `;
    if (hours > 0) res += `${hours} hour${(hours > 1 ? 's' : '')}, `;
    if (minutes > 0) res += `${minutes} minute${(minutes > 1 ? 's' : '')}, `;
    if (timeInSeconds > 0 || res === '') {
        res += `${timeInSeconds} second${(timeInSeconds !== 1 ? 's' : '')}`;
    } else {
        res = res.slice(0, -2);
    }
    return res;
}