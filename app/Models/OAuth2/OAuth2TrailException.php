<?php namespace Models\OAuth2;
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
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package Models
 */
#[ORM\Table(name: 'oauth2_exception_trail')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineOAuth2TrailExceptionRepository::class)]
class OAuth2TrailException extends BaseEntity
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'from_ip', type: 'string')]
    private $from_ip;

    /**
     * @var string
     */
    #[ORM\Column(name: 'exception_type', type: 'string')]
    private $exception_type;

    /**
     * @var Client
     */
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Models\OAuth2\Client::class, cascade: ['persist'])]
    private $client;

    /**
     * @return string
     */
    public function getFromIp(): string
    {
        return $this->from_ip;
    }

    /**
     * @param string $from_ip
     */
    public function setFromIp(string $from_ip): void
    {
        $this->from_ip = $from_ip;
    }

    /**
     * @return string
     */
    public function getExceptionType(): string
    {
        return $this->exception_type;
    }

    /**
     * @param string $exception_type
     */
    public function setExceptionType(string $exception_type): void
    {
        $this->exception_type = $exception_type;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }
}