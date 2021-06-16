<?php


return [
    // in seconds
    "lifetime" => env("OTP_DEFAULT_LIFETIME", 600),
    "length" => env("OTP_DEFAULT_LENGTH", 6)
];