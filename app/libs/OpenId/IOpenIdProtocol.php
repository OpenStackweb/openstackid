<?php namespace OpenId;
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

/**
 * Interface IOpenIdProtocol
 * @package OpenId
 */
interface IOpenIdProtocol
{

    const OpenIdXRDSModeUser = "OpenIdXRDSModeUser";
    const OpenIdXRDSModeIdp = "OpenIdXRDSModeIdp";

    /**
     * With OpenID 2.0, the relying party discovers the OpenID provider URL by requesting
     * the XRDS document (also called the Yadis document) with the content type application/xrds+xml;
     * this document may be available at the target URL and is always available for a target XRI.
     * @param $mode
     * @param null $canonical_id
     * @return mixed
     */
    public function getXRDSDiscovery($mode, $canonical_id = null);

    /**
     * @param OpenIdMessage $openIdMessage
     * @return responses\OpenIdResponse response
     */
    public function handleOpenIdMessage(OpenIdMessage $openIdMessage);
}