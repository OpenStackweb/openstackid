<?php namespace Services\OpenId;
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

use App\libs\Utils\URLUtils;
use App\Services\AbstractService;
use Auth\User;
use Exception;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use Models\OpenId\OpenIdTrustedSite;
use OpenId\Repositories\IOpenIdTrustedSiteRepository;
use OpenId\Exceptions\OpenIdInvalidRealmException;
use OpenId\Helpers\OpenIdUriHelper;
use OpenId\Services\ITrustedSitesService;
use Utils\Db\ITransactionService;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;
/**
 * Class TrustedSitesService
 * @package Services\OpenId
 */
final class TrustedSitesService extends AbstractService implements ITrustedSitesService
{
	/**
	 * @var IOpenIdTrustedSiteRepository
	 */
	private $repository;
	/**
	 * @var ILogService
	 */
	private $log_service;

    /**
     * TrustedSitesService constructor.
     * @param IOpenIdTrustedSiteRepository $repository
     * @param ILogService $log_service
     * @param ITransactionService $tx_service
     */
	public function __construct
    (
        IOpenIdTrustedSiteRepository $repository,
        ILogService $log_service,
        ITransactionService $tx_service
    )
	{
	    parent::__construct($tx_service);
		$this->repository  = $repository;
		$this->log_service = $log_service;
	}

    /**
     * @param User $user
     * @param string $realm
     * @param string $policy
     * @param array $data
     * @return OpenIdTrustedSite
     */
    public function addTrustedSite(User $user, string $realm, string $policy, $data = []):OpenIdTrustedSite
	{

	    return $this->tx_service->transaction(function() use($user, $realm, $policy, $data){
            $site = null;
            try {

                if (!OpenIdUriHelper::isValidRealm($realm))
                    throw new OpenIdInvalidRealmException(sprintf('realm %s is invalid', $realm));

                $site  = new OpenIdTrustedSite;
                $site->setRealm($realm);
                $site->setPolicy($policy);
                $site->setOwner($user);
                $site->setData(json_encode($data));
                $user->addTrustedSite($site);
            }
            catch (Exception $ex) {
                Log::error($ex);
                throw $ex;
            }
            return $site;
        });

	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function delete(int $id):void
	{
        $this->tx_service->transaction(function() use($id){
            $site = $this->repository->getById($id);
            if(is_null($site))
                throw new EntityNotFoundException();

            $this->repository->delete($site);
        });

	}

    /**
     * @param User $user
     * @param string $realm
     * @param array $data
     * @return array
     * @throws Exception
     */
	public function getTrustedSites(User $user, string $realm, $data = []):array
	{
		$res = [];
		try {
			if (!OpenIdUriHelper::isValidRealm($realm))
				throw new OpenIdInvalidRealmException(sprintf('realm %s is invalid', $realm));
			//get all possible sub-domains
			$sub_domains = URLUtils::getSubDomains($realm);
			$sites       = $this->repository->getMatchingOnesByUserId($user->getId(), $sub_domains, $data);
			//iterate over all retrieved sites and check the set policies by user
			foreach ($sites as $site) {
			    if(!$site instanceof OpenIdTrustedSite) continue;
				$policy = $site->getAuthorizationPolicy();
				//if denied then break
				if ($policy == IAuthService::AuthorizationResponse_DenyForever) {
					$res[] = $site;
					break;
				}
				$trusted_data = $site->getData();
				$diff = array_diff($data, $trusted_data);
				//if pre approved data is contained or equal than a former one
				if (count($diff) == 0) {
					$res[] = $site;
					break;
				}
			}
		} catch (Exception $ex) {
			Log::error($ex);
			throw $ex;
		}
		return $res;
	}

}