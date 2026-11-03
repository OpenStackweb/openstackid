<?php namespace Services\OAuth2;
/**
 * Copyright 2016 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use jwk\IJWKSet;
use jwk\impl\JWKSet;
use OAuth2\Models\IClient;
use OAuth2\Services\IClientJWKSetReader;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
/**
 * Class HttpIClientJWKSetReader
 * @package Services\OAuth2
 */
final class HttpIClientJWKSetReader implements IClientJWKSetReader
{
    /**
     * @see https://www.rfc-editor.org/rfc/rfc7517#section-8.5.1
     */
    private const JsonMediaTypes = ['application/json', 'application/jwk-set+json'];

    /**
     * @param IClient $client
     * @return IJWKSet|null
     * @throws \jwk\exceptions\InvalidJWKAlgorithm
     * @throws \jwk\exceptions\JWKInvalidIdentifierException
     */
    public function read(IClient $client)
    {
        try {
            $jwk_set_uri = $client->getJWKSUri();
            if (empty($jwk_set_uri)) return null;

            $client = new HttpClient([
                'timeout'         => Config::get('curl.timeout', 60),
                'allow_redirects' => Config::get('curl.allow_redirects', false),
                'verify'          => Config::get('curl.verify_ssl_cert', true)
            ]);

            $response  = $client->get($jwk_set_uri);
            if ($response->getStatusCode() !== 200) return null;
            if (!self::isJson($response->getHeaderLine('content-type'))) return null;
            return JWKSet::fromJson((string)$response->getBody());
        }
        catch (GuzzleException $ex)
        {
            Log::warning($ex->getMessage());
            return null;
        }
    }

    /**
     * @param string $content_type
     * @return bool
     */
    private static function isJson(string $content_type): bool
    {
        $media_type = strtolower(trim(explode(';', $content_type)[0]));
        return in_array($media_type, self::JsonMediaTypes, true);
    }
}