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