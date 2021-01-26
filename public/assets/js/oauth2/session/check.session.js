(function( $ ){

    /**
     * @param string message
     * @returns string
     */
    function hash(message)
    {
        var hash = CryptoJS.SHA256(message).toString();
        console.log('calculated hash '+hash+' from message '+message);
        return hash;
    }

    /**
     *
     * @param string clientId
     * @param string origin
     * @param string opbs
     * @param string salt
     * @returns string
     */
    function computeSessionStateHash(clientId, origin, opbs, salt)
    {
        return hash(clientId + origin + opbs + salt);
    }

    /**
     *
     * @param origin
     * @param message
     * @returns string
     */
    function calculateSessionStateResult(origin, message) {
        try
        {

            if (!origin || !message)
            {
                console.log("IDP::calculateSessionStateResult !origin || !message. return error");
                return "error";
            }

            var messageParts = message.split(' ');
            if (messageParts.length !== 2)
            {
                return "error";
            }

            var clientId     = messageParts[0];
            var sessionState = messageParts[1];

            if (!clientId || !sessionState)
            {
                console.log("IDP::calculateSessionStateResult !clientId || !sessionState. return error");
                return "error";
            }

            var sessionStateParts = sessionState.split('.');
            if (sessionStateParts.length !== 2)
            {
                console.log("IDP::calculateSessionStateResult sessionStateParts.length !== 2. return error");
                return "error";
            }

            var clientHash = sessionStateParts[0];
            var salt       = sessionStateParts[1];
            //console.log("clientHash "+clientHash);
            //console.log("salt "+salt);

            if (!clientHash || !salt)
            {
                console.log("IDP::calculateSessionStateResult missing clientHash or salt. return error");
                return "error";
            }

            var opbs = $.cookie('op_bs');
            // posible cookies not enabled or third party cookies not enabled
            if (opbs == "undefined" || typeof(opbs) == "undefined") {
                console.log("IDP::calculateSessionStateResult missing op_bs cookie. return error");
                return "error";
            }
            console.log("IDP::calculateSessionStateResult opbs " + opbs)
            var expectedHash = computeSessionStateHash(clientId, origin, opbs, salt);
            var res = clientHash === expectedHash ? "unchanged" : "changed";
            console.log("IDP::calculateSessionStateResult res "+ res);
            return res;
        }
        catch(e)
        {
            console.log("IDP::calculateSessionStateResult exception "+ e);
            return "error";
        }
    }

    if (window.parent !== window)
    {
        window.addEventListener("message", function (e)
        {
            if(e.origin == window.origin){
                return;
            }
            var result = calculateSessionStateResult(e.origin, e.data);
            e.source.postMessage(result, e.origin);
        }, false);
    }


}( jQuery ));