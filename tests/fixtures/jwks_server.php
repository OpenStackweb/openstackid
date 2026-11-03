<?php
/**
 * Router for "php -S", started by Tests\unit\ClientJWKSetReaderTest.
 * Plays the part of a client's jwks_uri. The key set comes from the file in JWKS_FILE.
 */
$respond = function (string $content_type, int $status = 200) {
    http_response_code($status);
    header('Content-Type: ' . $content_type);
    echo file_get_contents(getenv('JWKS_FILE'));
};

switch (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    case '/json':
        $respond('application/json');
        break;
    case '/json-charset':
        $respond('application/json; charset=utf-8');
        break;
    case '/idp-style':
        $respond('application/json;charset=UTF-8');
        break;
    case '/jwk-set':
        $respond('application/jwk-set+json');
        break;
    case '/uppercase':
        $respond('APPLICATION/JSON');
        break;
    case '/html':
        $respond('text/html');
        break;
    case '/no-content-type':
        ini_set('default_mimetype', '');
        echo file_get_contents(getenv('JWKS_FILE'));
        break;
    case '/not-found':
        $respond('application/json', 404);
        break;
    case '/error':
        $respond('application/json', 500);
        break;
    case '/redirect':
        http_response_code(302);
        header('Location: /json');
        break;
    case '/slow':
        sleep(3);
        $respond('application/json');
        break;
    default:
        http_response_code(404);
}
