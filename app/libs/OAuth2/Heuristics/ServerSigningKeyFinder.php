<?php namespace OAuth2\Heuristics;
/**
 * Copyright 2015 OpenStack Foundation
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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use jwa\cryptographic_algorithms\ICryptoAlgorithm;
use jwa\cryptographic_algorithms\macs\MAC_Algorithm;
use jwk\IJWK;
use jwk\impl\OctetSequenceJWKFactory;
use jwk\impl\OctetSequenceJWKSpecification;
use jwk\JSONWebKeyPublicKeyUseValues;
use jwk\JSONWebKeyTypes;
use OAuth2\Exceptions\InvalidClientType;
use OAuth2\Exceptions\ServerKeyNotFoundException;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IServerPrivateKeyRepository ;
use Utils\Db\ITransactionService;
/**
 * Class ServerSigningKeyFinder
 * @package OAuth2\Heuristics
 */
final class ServerSigningKeyFinder implements IKeyFinder
{

    /**
     * @var IServerPrivateKeyRepository
     */
    private $server_private_key_repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @param IServerPrivateKeyRepository $server_private_key_repository
     */
    public function __construct(
        IServerPrivateKeyRepository $server_private_key_repository
    )
    {
        $this->server_private_key_repository = $server_private_key_repository;
        // @todo Refactor IOC
        $this->tx_service = App::make(ITransactionService::class);
    }

    /**
     * @param IClient $client
     * @param ICryptoAlgorithm $alg
     * @param string|null $kid_hint
     * @return IJWK|mixed
     * @throws \Exception
     */
    public function find(IClient $client, ICryptoAlgorithm $alg, ?string $kid_hint = null)
    {
        return $this->tx_service->transaction(function() use($client, $alg, $kid_hint ){
            $jwk = null;

            if ($alg instanceof MAC_Algorithm) {
                // use secret
                if ($client->getClientType() !== IClient::ClientType_Confidential) {
                    throw new InvalidClientType;
                }

                $jwk = OctetSequenceJWKFactory::build
                (
                    new OctetSequenceJWKSpecification
                    (
                        $client->getClientSecret(),
                        $alg->getName()
                    )
                );

                $jwk->setId('shared_secret');

                return $jwk;
            }

            $key = null;

            if (!is_null($kid_hint))
            {
                Log::debug(sprintf("ServerSigningKeyFinder::find: trying to get key kid_hint %s", $kid_hint));
                $key = $this->server_private_key_repository->getByKeyIdentifier($kid_hint);
                if (!is_null($key) && !$key->isActive())
                {
                    $key = null;
                }
                if (!is_null($key) && $key->getAlg()->getName() !== $alg->getName())
                {
                    $key = null;
                }
            }

            if(is_null($key))
            {
                $key = $this->server_private_key_repository->getActiveByCriteria
                (
                    JSONWebKeyTypes::RSA,
                    JSONWebKeyPublicKeyUseValues::Signature,
                    $alg->getName()
                );
            }

            if (is_null($key))
            {
                throw new ServerKeyNotFoundException
                (
                    sprintf('sig key not found  - client id %s - requested alg %s', $client->getClientId(), $alg->getName())
                );
            }

            $jwk = $key->toJWK();

            $key->markAsUsed();

            return $jwk;
        });

    }
}