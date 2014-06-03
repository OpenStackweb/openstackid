<?php

namespace auth\exceptions;

use Exception;

class AuthenticationInvalidPasswordAttemptException extends Exception
{

    private $identifier;

    public function __construct($identifier,$message = "")
    {
        $message = "Invalid Password Attempt: " . $message;
        $this->identifier = $identifier;
        parent::__construct($message, 0, null);
    }

    public function getIdentifier(){
        return $this->identifier;
    }

}