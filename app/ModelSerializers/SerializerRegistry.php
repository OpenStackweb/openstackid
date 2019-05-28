<?php namespace App\ModelSerializers;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\libs\Auth\Models\UserRegistrationRequest;
use App\ModelSerializers\Auth\PrivateUserSerializer;
use App\ModelSerializers\Auth\PublicGroupSerializer;
use App\ModelSerializers\Auth\PublicUserSerializer;
use App\ModelSerializers\Auth\UserRegistrationRequestSerializer;
use App\ModelSerializers\OAuth2\AccessTokenSerializer;
use App\ModelSerializers\OAuth2\ApiEndpointSerializer;
use App\ModelSerializers\OAuth2\ApiScopeGroupSerializer;
use App\ModelSerializers\OAuth2\ApiScopeSerializer;
use App\ModelSerializers\OAuth2\ApiSerializer;
use App\ModelSerializers\OAuth2\ClientSerializer;
use App\ModelSerializers\OAuth2\RefreshTokenSerializer;
use App\ModelSerializers\OAuth2\ResourceServerSerializer;
use App\ModelSerializers\OAuth2\ServerPrivateKeySerializer;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\App;
use Models\OAuth2\AccessToken;
use Models\OAuth2\Api;
use Models\OAuth2\ApiEndpoint;
use Models\OAuth2\ApiScope;
use Models\OAuth2\ApiScopeGroup;
use Models\OAuth2\Client;
use Models\OAuth2\RefreshToken;
use Models\OAuth2\ResourceServer;
use Models\OAuth2\ServerPrivateKey;
use OAuth2\IResourceServerContext;
use ReflectionClass;
/**
 * Class SerializerRegistry
 * @package App\ModelSerializers
 */
final class SerializerRegistry
{
    /**
     * @var SerializerRegistry
     */
    private static $instance;

    const SerializerType_Public  = 'PUBLIC';
    const SerializerType_Private = 'PRIVATE';

    private function __clone(){}

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    /**
     * @return SerializerRegistry
     */
    public static function getInstance()
    {
        if (!is_object(self::$instance)) {
            self::$instance = new SerializerRegistry();
        }
        return self::$instance;
    }

    /**
     * @var array
     */
    private $registry = [];

    /**
     * SerializerRegistry constructor.
     */
    private function __construct()
    {

        $this->resource_server_context = App::make(IResourceServerContext::class);

        // auth mappings
        $this->registry["User"] = [
            self::SerializerType_Public  => PublicUserSerializer::class,
            self::SerializerType_Private => PrivateUserSerializer::class,
        ];

        $this->registry["UserRegistrationRequest"] = UserRegistrationRequestSerializer::class;

        $this->registry["Group"] = [
            self::SerializerType_Public  => PublicGroupSerializer::class,
            self::SerializerType_Private => PublicGroupSerializer::class,
        ];

        // oauth2 mappings
        $this->registry["ResourceServer"]   = ResourceServerSerializer::class;
        $this->registry["Api"]              = ApiSerializer::class;
        $this->registry["ApiScope"]         = ApiScopeSerializer::class;
        $this->registry["ApiEndpoint"]      = ApiEndpointSerializer::class;
        $this->registry["Client"]           = ClientSerializer::class;
        $this->registry["AccessToken"]      = AccessTokenSerializer::class;
        $this->registry["RefreshToken"]     = RefreshTokenSerializer::class;
        $this->registry["ApiScopeGroup"]    = ApiScopeGroupSerializer::class;
        $this->registry["ServerPrivateKey"] = ServerPrivateKeySerializer::class;
    }

    /**
     * @param object $object
     * @param string $type
     * @return IModelSerializer
     */
    public function getSerializer($object, $type = self::SerializerType_Public){
        if(is_null($object)) return null;
        $reflect = new ReflectionClass($object);
        $class   = $reflect->getShortName();
        if(!isset($this->registry[$class]))
            throw new \InvalidArgumentException('Serializer not found for '.$class);

        $serializer_class = $this->registry[$class];

        if(is_array($serializer_class)){
            if(!isset($serializer_class[$type]))
                throw new \InvalidArgumentException(sprintf('Serializer not found for %s , type %s', $class, $type));
            $serializer_class = $serializer_class[$type];
        }

        return new $serializer_class($object, $this->resource_server_context);
    }
}