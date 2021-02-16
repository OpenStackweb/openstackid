<?php namespace OAuth2\GrantTypes\Strategies;
/**
 * Copyright 2020 OpenStack Foundation
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

/**
 * Class PKCEBaseValidator
 * @package OAuth2\GrantTypes\Strategies
 */
abstract class PKCEBaseValidator
{
    /**
     * @var string
     */
    protected $code_challenge;

    /**
     * @var string
     */
    protected $code_verifier;

    /**
     * PKCEBaseValidator constructor.
     * @param string $code_challenge
     * @param string $code_verifier
     */
    public function __construct(string $code_challenge, string $code_verifier)
    {
        $this->code_challenge = $code_challenge;
        $this->code_verifier = $code_verifier;
    }

}